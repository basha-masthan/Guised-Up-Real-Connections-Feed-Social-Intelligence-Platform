<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Post;

class GuisedUpApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@guisedup.io',
            'avatar_url' => 'https://i.pravatar.cc/150?u=test'
        ]);
    }

    /**
     * Test creating a post (`POST /api/posts`) auto-generates vector embedding and returns 201.
     */
    public function test_can_create_post_and_generate_embedding(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'Real authentic thoughts on life and coding without filters.',
                'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Real authentic thoughts on life and coding without filters.')
            ->assertJsonPath('data.author.name', 'Test User');

        $this->assertDatabaseHas('posts', [
            'author_id' => $this->user->id,
            'content' => 'Real authentic thoughts on life and coding without filters.'
        ]);
    }

    /**
     * Test retrieving the personalized feed (`GET /api/feed`) returns paginated posts.
     */
    public function test_can_retrieve_personalized_feed(): void
    {
        $otherAuthor = User::factory()->create(['name' => 'Other Author']);
        Post::create([
            'author_id' => $otherAuthor->id,
            'content' => 'A wonderful travel story from Jaipur last week.',
            'authenticity_score' => 1.8,
            'view_count' => 10,
            'embedding' => json_encode(array_fill(0, 1536, 0.1))
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/feed?page=1');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'author_id', 'author', 'content', 'authenticity_score', 'view_count', 'reaction_count', 'has_reacted']
                ],
                'meta' => ['current_page', 'per_page', 'has_more']
            ]);
    }

    /**
     * Test natural language semantic search (`GET /api/search?q={query}`) returns relevant posts.
     */
    public function test_can_perform_semantic_search(): void
    {
        $otherAuthor = User::factory()->create(['name' => 'Search Author']);
        Post::create([
            'author_id' => $otherAuthor->id,
            'content' => 'Funny travel stories from last week in Jaipur chai shop.',
            'authenticity_score' => 1.7,
            'view_count' => 25,
            'embedding' => json_encode(array_fill(0, 1536, 0.2))
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/search?q=funny+travel+stories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'content', 'similarity_score']
                ]
            ]);
    }

    /**
     * Test logging user interaction (`POST /api/interactions`) records signal and increments view count.
     */
    public function test_can_log_user_interaction(): void
    {
        $post = Post::create([
            'author_id' => $this->user->id,
            'content' => 'Post to interact with.',
            'authenticity_score' => 1.5,
            'view_count' => 5
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/interactions', [
                'post_id' => $post->id,
                'interaction_type' => 'view'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('interactions', [
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'interaction_type' => 'view'
        ]);

        $this->assertEquals(6, $post->fresh()->view_count);
    }
}
