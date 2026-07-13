<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Interaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    protected string $mlServiceUrl;

    public function __construct()
    {
        $this->mlServiceUrl = rtrim(env('ML_SERVICE_URL', 'http://127.0.0.1:8001'), '/');
    }

    /**
     * POST /api/posts
     * Create a new post, auto-generate vector embedding via Python service/fallback, and store it.
     */
    public function createPost(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'image_url' => 'nullable|url|max:1024',
        ]);

        $user = $request->user();
        $content = $validated['content'];
        $imageUrl = $validated['image_url'] ?? null;

        // Calculate a base authenticity score based on raw text qualities
        // Higher score for genuine thoughts, lower for promotional text / excessive links
        $authScore = 1.5;
        if (preg_match('/(http|www|link in bio|subscribe|buy now|discount|% off)/i', $content)) {
            $authScore = 0.6;
        } elseif (strlen($content) > 60 && !preg_match('/#\w+/i', $content)) {
            $authScore = 1.85;
        }

        // Generate 1536-d Vector Embedding
        $embedding = null;
        try {
            $response = Http::timeout(3)->post("{$this->mlServiceUrl}/embed", [
                'text' => $content,
                'model' => 'text-embedding-3-small'
            ]);
            if ($response->successful()) {
                $embedding = $response->json()['embedding'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("ML Service offline or unreachable during /embed: " . $e->getMessage());
        }

        // Fallback deterministic embedding if ML service returned null
        if (!$embedding) {
            $embedding = $this->generateDeterministicHashEmbedding($content);
        }

        $post = Post::create([
            'author_id' => $user->id,
            'content' => $content,
            'image_url' => $imageUrl,
            'authenticity_score' => $authScore,
            'view_count' => 0,
            'embedding' => is_string($embedding) ? $embedding : json_encode($embedding),
        ]);

        $post->load('author');

        return response()->json([
            'success' => true,
            'data' => $this->formatPost($post, $user->id)
        ], 201);
    }

    /**
     * GET /api/feed
     * Return personalized feed for the authenticated user (20 per page).
     */
    public function getFeed(Request $request)
    {
        $user = $request->user();
        $page = (int) $request->query('page', 1);
        $perPage = 20;

        // Get all candidate posts from the last 14 days (excluding own posts)
        $candidates = Post::with('author', 'interactions')
            ->where('author_id', '!=', $user->id)
            ->where('created_at', '>=', now()->subDays(14))
            ->get();

        // Calculate relationship depth scores
        $relationshipScores = [];
        $userInteractions = Interaction::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        foreach ($userInteractions as $interaction) {
            $weight = match ($interaction->interaction_type) {
                'reply' => 5.0,
                'reaction' => 3.0,
                'view' => 1.0,
                default => 0.0,
            };
            $relationshipScores[$interaction->author_id] = ($relationshipScores[$interaction->author_id] ?? 0.0) + $weight;
        }

        // Try Python ML Service /rank endpoint first
        $rankedIds = null;
        try {
            $candidatePayload = $candidates->map(function ($p) {
                $emb = $p->embedding;
                if (is_string($emb)) {
                    $decoded = json_decode($emb, true);
                    if (is_array($decoded)) $emb = $decoded;
                }
                return [
                    'id' => $p->id,
                    'author_id' => $p->author_id,
                    'content' => $p->content,
                    'authenticity_score' => (float) $p->authenticity_score,
                    'created_at' => $p->created_at->toIso8601String(),
                    'embedding' => is_array($emb) ? $emb : null,
                ];
            })->values()->toArray();

            $response = Http::timeout(3)->post("{$this->mlServiceUrl}/rank", [
                'user_id' => $user->id,
                'page' => $page,
                'per_page' => $perPage,
                'candidates' => $candidatePayload,
            ]);

            if ($response->successful() && isset($response->json()['data'])) {
                $rankedIds = $response->json()['data'];
            }
        } catch (\Exception $e) {
            Log::warning("ML Service offline or unreachable during /rank: " . $e->getMessage());
        }

        // Native PHP multi-factor ranking fallback if Python service is offline
        if (!$rankedIds) {
            $maxRel = !empty($relationshipScores) ? max(array_merge(array_values($relationshipScores), [1.0])) : 1.0;
            $halfLifeHours = 36.0;
            $lambdaDecay = 0.693147 / $halfLifeHours;

            $scored = [];
            foreach ($candidates as $post) {
                // 1. Authenticity
                $sAuth = min(max($post->authenticity_score / 2.0, 0.0), 1.0);
                
                // 2. Relationship
                $rawRel = $relationshipScores[$post->author_id] ?? 0.0;
                $sRel = log(1.0 + $rawRel) / log(1.0 + $maxRel);
                
                // 3. Semantic (fallback default 0.5)
                $sSem = 0.5;
                
                // 4. Time decay
                $hoursElapsed = max(0.0, now()->diffInSeconds($post->created_at) / 3600.0);
                $sTime = exp(-$lambdaDecay * $hoursElapsed);

                $finalScore = ((0.30 * $sAuth) + (0.40 * $sRel) + (0.30 * $sSem)) * $sTime;
                $scored[] = ['post' => $post, 'score' => $finalScore];
            }

            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
            $offset = ($page - 1) * $perPage;
            $slice = array_slice($scored, $offset, $perPage);
            $paginatedPosts = array_map(fn ($item) => $item['post'], $slice);
        } else {
            // Map ranked IDs back to Post instances preserving order
            $postsMap = $candidates->keyBy('id');
            $paginatedPosts = [];
            foreach ($rankedIds as $pid) {
                if (isset($postsMap[$pid])) {
                    $paginatedPosts[] = $postsMap[$pid];
                }
            }
        }

        $formatted = array_map(fn ($p) => $this->formatPost($p, $user->id), $paginatedPosts);

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'has_more' => count($formatted) >= $perPage,
            ]
        ]);
    }

    /**
     * GET /api/search?q={query}
     * Natural language search across posts using vector similarity (top 10).
     */
    public function searchPosts(Request $request)
    {
        $query = $request->query('q');
        if (!$query || trim($query) === '') {
            return response()->json(['success' => false, 'message' => 'Query parameter q is required.'], 400);
        }

        $user = $request->user();

        // Try Python ML Service /search endpoint first
        try {
            $response = Http::timeout(3)->post("{$this->mlServiceUrl}/search", [
                'query' => $query,
                'limit' => 10
            ]);

            if ($response->successful() && isset($response->json()['data'])) {
                $results = $response->json()['data'];
                $postIds = array_column($results, 'post_id');
                $simScores = array_column($results, 'similarity_score', 'post_id');

                $postsMap = Post::with('author', 'interactions')->whereIn('id', $postIds)->get()->keyBy('id');
                $formatted = [];
                foreach ($postIds as $pid) {
                    if (isset($postsMap[$pid])) {
                        $f = $this->formatPost($postsMap[$pid], $user ? $user->id : 0);
                        $f['similarity_score'] = $simScores[$pid] ?? 0.85;
                        $formatted[] = $f;
                    }
                }
                return response()->json(['success' => true, 'data' => $formatted]);
            }
        } catch (\Exception $e) {
            Log::warning("ML Service offline during /search: " . $e->getMessage());
        }

        // Native PHP keyword & semantic-hash fallback search if ML service is offline
        $queryLower = strtolower(trim($query));
        $allPosts = Post::with('author', 'interactions')->get();
        $queryVec = $this->generateDeterministicHashEmbedding($query);

        $scored = [];
        foreach ($allPosts as $post) {
            $score = 0.0;
            if (str_contains(strtolower($post->content), $queryLower)) {
                $score += 0.6; // exact keyword match boost
            }
            // Check cosine similarity if vector exists
            $emb = $post->embedding;
            if (is_string($emb)) $emb = json_decode($emb, true);
            if (is_array($emb) && count($emb) === 1536) {
                $dot = 0.0;
                for ($i = 0; $i < 1536; $i++) {
                    $dot += ($queryVec[$i] ?? 0.0) * ($emb[$i] ?? 0.0);
                }
                $score += max(0.0, $dot) * 0.4;
            }
            if ($score > 0.05) {
                $scored[] = ['post' => $post, 'score' => $score];
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $slice = array_slice($scored, 0, 10);

        $formatted = array_map(function ($item) use ($user) {
            $f = $this->formatPost($item['post'], $user ? $user->id : 0);
            $f['similarity_score'] = round($item['score'], 4);
            return $f;
        }, $slice);

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    /**
     * POST /api/interactions
     * Log user interaction (view, reply, reaction) against a post.
     */
    public function logInteraction(Request $request)
    {
        $validated = $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
            'interaction_type' => 'required|string|in:view,reply,reaction',
        ]);

        $user = $request->user();
        $post = Post::findOrFail($validated['post_id']);
        $type = $validated['interaction_type'];

        Interaction::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'author_id' => $post->author_id,
            'interaction_type' => $type,
        ]);

        if ($type === 'view') {
            $post->increment('view_count');
        }

        return response()->json([
            'success' => true,
            'message' => 'Interaction recorded successfully.'
        ]);
    }

    /**
     * Helper to format post with user interaction flags, time ago, and reaction counts.
     */
    protected function formatPost(Post $post, int $currentUserId): array
    {
        $reactionsCount = $post->interactions()->where('interaction_type', 'reaction')->count();
        $hasReacted = $post->interactions()
            ->where('user_id', $currentUserId)
            ->where('interaction_type', 'reaction')
            ->exists();

        return [
            'id' => $post->id,
            'author_id' => $post->author_id,
            'author' => [
                'id' => $post->author->id ?? $post->author_id,
                'name' => $post->author->name ?? 'Anonymous',
                'avatar_url' => $post->author->avatar_url ?? 'https://i.pravatar.cc/150?u=' . $post->author_id,
            ],
            'content' => $post->content,
            'image_url' => $post->image_url,
            'authenticity_score' => (float) $post->authenticity_score,
            'view_count' => (int) $post->view_count,
            'reaction_count' => $reactionsCount,
            'has_reacted' => $hasReacted,
            'created_at' => $post->created_at ? $post->created_at->toIso8601String() : now()->toIso8601String(),
            'time_ago' => $post->created_at ? $post->created_at->diffForHumans() : 'Just now',
        ];
    }

    /**
     * Deterministic hash embedding fallback helper inside PHP.
     */
    protected function generateDeterministicHashEmbedding(string $text, int $dimensions = 1536): array
    {
        $cleaned = strtolower(trim($text));
        $vector = array_fill(0, $dimensions, 0.0);
        
        $keywords = [
            "coffee" => 0, "friend" => 10, "conversation" => 20, "authentic" => 30, "phone" => 40,
            "burnout" => 50, "work" => 60, "startup" => 70, "filter" => 80, "real" => 90,
            "travel" => 100, "jaipur" => 110, "lost" => 120, "chai" => 130, "funny" => 140,
            "music" => 150, "night" => 160, "weekend" => 170, "peace" => 180, "happy" => 190
        ];
        
        foreach ($keywords as $word => $offset) {
            if (str_contains($cleaned, $word)) {
                for ($i = 0; $i < 10; $i++) {
                    $vector[($offset + $i) % $dimensions] += 0.5;
                }
            }
        }
        
        $words = explode(' ', $cleaned);
        foreach ($words as $w) {
            if (trim($w) === '') continue;
            $h = crc32($w);
            $idx = abs($h) % $dimensions;
            $sign = ($h % 2 === 0) ? 1.0 : -1.0;
            $vector[$idx] += $sign * 0.1;
        }
        
        $sumSq = 0.0;
        foreach ($vector as $v) $sumSq += $v * $v;
        $norm = sqrt($sumSq);
        if ($norm == 0) {
            $vector[0] = 1.0;
            $norm = 1.0;
        }
        
        $result = [];
        foreach ($vector as $v) {
            $result[] = round($v / $norm, 6);
        }
        return $result;
    }
}
