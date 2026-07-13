<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Interaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with rich, authentic social data.
     */
    public function run(): void
    {
        // 1. Create Test Users
        $aarav = User::updateOrCreate(
            ['email' => 'aarav@guisedup.io'],
            [
                'name' => 'Aarav Sharma',
                'password' => Hash::make('password123'),
                'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300&auto=format&fit=crop',
            ]
        );

        $diya = User::updateOrCreate(
            ['email' => 'diya@guisedup.io'],
            [
                'name' => 'Diya Patel',
                'password' => Hash::make('password123'),
                'avatar_url' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=300&auto=format&fit=crop',
            ]
        );

        $rohan = User::updateOrCreate(
            ['email' => 'rohan@guisedup.io'],
            [
                'name' => 'Rohan Gupta',
                'password' => Hash::make('password123'),
                'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&auto=format&fit=crop',
            ]
        );

        $maya = User::updateOrCreate(
            ['email' => 'maya@guisedup.io'],
            [
                'name' => 'Maya Lin',
                'password' => Hash::make('password123'),
                'avatar_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=300&auto=format&fit=crop',
            ]
        );

        // Create API Tokens for testing
        $aarav->tokens()->delete();
        $diya->tokens()->delete();
        $token1 = $aarav->createToken('aarav-token')->plainTextToken;
        $token2 = $diya->createToken('diya-token')->plainTextToken;

        // 2. Create Posts with Mock 1536-d Embeddings (using deterministic values for testing)
        $postsData = [
            [
                'author' => $aarav,
                'content' => 'Just had coffee with an old friend without checking my phone once. Real conversations hit different.',
                'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800&auto=format&fit=crop',
                'authenticity_score' => 1.85,
                'view_count' => 14,
                'time_offset' => 1, // hours ago
            ],
            [
                'author' => $diya,
                'content' => 'Raw reflections on startup burnout after a 14-hour workday. No filters today, just tired eyes and a big dream.',
                'image_url' => null,
                'authenticity_score' => 1.95,
                'view_count' => 52,
                'time_offset' => 3,
            ],
            [
                'author' => $rohan,
                'content' => 'Got completely lost in the side streets of Jaipur because Google Maps decided a pedestrian staircase was a road. Ended up finding the best chai shop ever!',
                'image_url' => 'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=800&auto=format&fit=crop',
                'authenticity_score' => 1.70,
                'view_count' => 88,
                'time_offset' => 12,
            ],
            [
                'author' => $maya,
                'content' => 'Practicing acoustic guitar in my messy bedroom at 2 AM. Mistakes included. Music feels pure when nobodies watching.',
                'image_url' => null,
                'authenticity_score' => 1.90,
                'view_count' => 30,
                'time_offset' => 24,
            ],
            [
                'author' => $aarav,
                'content' => 'Quick tip: always double check your passport before leaving for the airport. Almost learned that the hard way this morning!',
                'image_url' => null,
                'authenticity_score' => 1.40,
                'view_count' => 120,
                'time_offset' => 48,
            ],
            [
                'author' => $diya,
                'content' => 'Enjoying the quiet morning breeze with some fresh green tea before the inbox chaos begins.',
                'image_url' => 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=800&auto=format&fit=crop',
                'authenticity_score' => 1.60,
                'view_count' => 45,
                'time_offset' => 72,
            ],
        ];

        $createdPosts = [];
        foreach ($postsData as $idx => $item) {
            // Generate a simple deterministic pseudo-vector for demo/seeding
            $pseudoVec = [];
            for ($i = 0; $i < 1536; $i++) {
                $val = sin($idx * 100 + $i) * 0.1;
                if ($item['author']->id === $aarav->id) $val += 0.05;
                if (str_contains(strtolower($item['content']), 'travel') || str_contains(strtolower($item['content']), 'jaipur')) {
                    if ($i < 10) $val += 0.5;
                }
                $pseudoVec[] = round($val, 6);
            }

            $post = Post::create([
                'author_id' => $item['author']->id,
                'content' => $item['content'],
                'image_url' => $item['image_url'],
                'authenticity_score' => $item['authenticity_score'],
                'view_count' => $item['view_count'],
                'embedding' => json_encode($pseudoVec),
                'created_at' => now()->subHours($item['time_offset']),
                'updated_at' => now()->subHours($item['time_offset']),
            ]);
            $createdPosts[] = $post;
        }

        // 3. Seed Interactions (to build up Relationship Depth)
        // Aarav interacts with Diya a lot
        foreach ([$createdPosts[1], $createdPosts[5]] as $diyaPost) {
            Interaction::create([
                'user_id' => $aarav->id,
                'post_id' => $diyaPost->id,
                'author_id' => $diya->id,
                'interaction_type' => 'reaction',
                'created_at' => now()->subHours(2),
            ]);
            Interaction::create([
                'user_id' => $aarav->id,
                'post_id' => $diyaPost->id,
                'author_id' => $diya->id,
                'interaction_type' => 'reply',
                'created_at' => now()->subHours(1),
            ]);
        }

        // Diya interacts with Rohan
        Interaction::create([
            'user_id' => $diya->id,
            'post_id' => $createdPosts[2]->id,
            'author_id' => $rohan->id,
            'interaction_type' => 'reaction',
            'created_at' => now()->subHours(5),
        ]);
    }
}
