<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Interaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private function pseudoVector(int $seed, string $content = '', int $authorId = 0): string
    {
        $vec = [];
        for ($i = 0; $i < 1536; $i++) {
            $val = sin($seed * 100 + $i) * 0.1;
            $val += ($authorId % 5) * 0.01;
            if (str_contains(strtolower($content), 'travel') || str_contains(strtolower($content), 'jaipur') || str_contains(strtolower($content), 'wander')) {
                if ($i < 20) $val += 0.5;
            }
            if (str_contains(strtolower($content), 'food') || str_contains(strtolower($content), 'chai') || str_contains(strtolower($content), 'recipe')) {
                if ($i >= 20 && $i < 40) $val += 0.5;
            }
            if (str_contains(strtolower($content), 'tech') || str_contains(strtolower($content), 'code') || str_contains(strtolower($content), 'startup')) {
                if ($i >= 40 && $i < 60) $val += 0.5;
            }
            if (str_contains(strtolower($content), 'music') || str_contains(strtolower($content), 'guitar') || str_contains(strtolower($content), 'song')) {
                if ($i >= 60 && $i < 80) $val += 0.5;
            }
            $vec[] = round($val, 6);
        }
        return json_encode($vec);
    }

    public function run(): void
    {
        // ====================================================================
        // 1. USERS — 15 diverse personas
        // ====================================================================
        $usersData = [
            ['name' => 'Aarav Sharma',       'email' => 'aarav@guisedup.io',       'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300'],
            ['name' => 'Diya Patel',          'email' => 'diya@guisedup.io',        'avatar' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=300'],
            ['name' => 'Rohan Gupta',         'email' => 'rohan@guisedup.io',       'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300'],
            ['name' => 'Maya Lin',            'email' => 'maya@guisedup.io',        'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=300'],
            ['name' => 'Vikram Singh',        'email' => 'vikram@guisedup.io',      'avatar' => 'https://images.unsplash.com/photo-1568602471122-7832951cc4c5?w=300'],
            ['name' => 'Priya Kapoor',        'email' => 'priya@guisedup.io',       'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=300'],
            ['name' => 'Arjun Mehta',         'email' => 'arjun@guisedup.io',       'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300'],
            ['name' => 'Sara Ali',            'email' => 'sara@guisedup.io',        'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=300'],
            ['name' => 'Karan Joshi',         'email' => 'karan@guisedup.io',       'avatar' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=300'],
            ['name' => 'Ananya Reddy',        'email' => 'ananya@guisedup.io',      'avatar' => 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=300'],
            ['name' => 'Rahul Verma',         'email' => 'rahul@guisedup.io',       'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300'],
            ['name' => 'Ishita Nair',         'email' => 'ishita@guisedup.io',      'avatar' => 'https://images.unsplash.com/photo-1489424731084-a5d8b219a5bb?w=300'],
            ['name' => 'Dev Thakur',          'email' => 'dev@guisedup.io',         'avatar' => 'https://images.unsplash.com/photo-1504257432389-52343af06ae3?w=300'],
            ['name' => 'Naina Pillai',        'email' => 'naina@guisedup.io',       'avatar' => 'https://images.unsplash.com/photo-1488426862026-3ea34d5e5955?w=300'],
            ['name' => 'Kabir Bhatia',        'email' => 'kabir@guisedup.io',       'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300'],
        ];

        $users = [];
        foreach ($usersData as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password123'),
                    'avatar_url' => $u['avatar'],
                ]
            );
            $user->tokens()->delete();
            $user->createToken($user->name . '-token')->plainTextToken;
            $users[] = $user;
        }

        [$aarav, $diya, $rohan, $maya, $vikram, $priya, $arjun, $sara, $karan, $ananya, $rahul, $ishita, $dev, $naina, $kabir] = $users;

        echo "Seeded " . count($users) . " users.\n";

        // ====================================================================
        // 2. POSTS — 48 diverse posts across all users
        // ====================================================================
        $postsData = [
            // Aarav (travel, daily life)
            ['author' => $aarav, 'content' => 'Just had coffee with an old friend without checking my phone once. Real conversations hit different.',                        'image' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800', 'auth' => 1.85, 'views' => 14,  'hours' => 1],
            ['author' => $aarav, 'content' => 'Quick tip: always double check your passport before leaving for the airport. Almost learned that the hard way this morning!',       'image' => null, 'auth' => 1.40, 'views' => 120, 'hours' => 48],
            ['author' => $aarav, 'content' => 'Hiked to Triund at sunrise. The snow-capped Dhauladhar range looked unreal. No filter needed.',                                        'image' => 'https://images.unsplash.com/photo-1585409677983-0f6c41ca9c3b?w=800', 'auth' => 1.90, 'views' => 67,  'hours' => 6],
            ['author' => $aarav, 'content' => 'Lost my wallet in a rickshaw in Old Delhi. The driver tracked me down on Facebook to return it. Humanity restored.',                     'image' => null, 'auth' => 1.75, 'views' => 210, 'hours' => 18],
            ['author' => $aarav, 'content' => 'Booked a spontaneous train ticket to somewhere I have never been. Will decide where to get off along the way.',                          'image' => 'https://images.unsplash.com/photo-1474487548417-781cb71495f7?w=800', 'auth' => 1.95, 'views' => 89,  'hours' => 4],
            ['author' => $aarav, 'content' => 'Just realized the best part of traveling isn\'t the destination, it\'s the weird conversations you have with strangers on the train.',    'image' => null, 'auth' => 1.80, 'views' => 140, 'hours' => 2],
            ['author' => $aarav, 'content' => 'Unplugged from the internet for 24 hours. The world didn\'t end. Highly recommend.',                                                     'image' => 'https://images.unsplash.com/photo-1499591934245-40b55745b905?w=800', 'auth' => 1.92, 'views' => 310, 'hours' => 12],

            // Diya (startup, writing, burnout)
            ['author' => $diya, 'content' => 'Raw reflections on startup burnout after a 14-hour workday. No filters today, just tired eyes and a big dream.',                          'image' => null, 'auth' => 1.95, 'views' => 52,  'hours' => 3],
            ['author' => $diya, 'content' => 'Enjoying the quiet morning breeze with some fresh green tea before the inbox chaos begins.',                                              'image' => 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=800', 'auth' => 1.60, 'views' => 45,  'hours' => 72],
            ['author' => $diya, 'content' => 'Just turned down a 2Cr funding offer because the VC didnt align with our mission. Short term money vs long term impact — chose impact.',   'image' => null, 'auth' => 1.85, 'views' => 340, 'hours' => 10],
            ['author' => $diya, 'content' => 'Three years ago I was coding from a coffee shop. Today my team is 12 people. Here is what I have learned about leadership the hard way.',  'image' => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=800', 'auth' => 1.80, 'views' => 156, 'hours' => 20],
            ['author' => $diya, 'content' => 'Cried in the office bathroom yesterday. Then got back up and closed the deal. Being a founder is weirdly lonely even in a crowded room.','image' => null, 'auth' => 1.98, 'views' => 510, 'hours' => 5],
            ['author' => $diya, 'content' => 'A reminder that it\'s okay to log off at 6 PM. Your startup needs a rested founder more than it needs a burnt-out one.',                  'image' => null, 'auth' => 1.90, 'views' => 240, 'hours' => 1],
            ['author' => $diya, 'content' => 'Just spent 3 hours debating the color of a button with my co-founder. Startup life is 10% vision and 90% absurd arguments.',              'image' => null, 'auth' => 1.75, 'views' => 185, 'hours' => 14],

            // Rohan (food, exploration)
            ['author' => $rohan, 'content' => 'Got completely lost in the side streets of Jaipur because Google Maps decided a pedestrian staircase was a road. Ended up finding the best chai shop ever!', 'image' => 'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=800', 'auth' => 1.70, 'views' => 88,  'hours' => 12],
            ['author' => $rohan, 'content' => 'Found a 50-year-old family recipe book in my grandmothers attic. Going to cook every single recipe this year. Starting with her biryani.',              'image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800', 'auth' => 1.92, 'views' => 134, 'hours' => 8],
            ['author' => $rohan, 'content' => 'Street food crawl in Mumbai: vada pav, pani puri, kebabs, and a mysterious yellow drink I still cannot identify. Worth it.',                      'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800', 'auth' => 1.65, 'views' => 95,  'hours' => 15],
            ['author' => $rohan, 'content' => 'Made Dal Makhani from scratch for the first time. Took 6 hours. Tasted like home. Mom approved.',                                                'image' => null, 'auth' => 1.75, 'views' => 72,  'hours' => 2],
            ['author' => $rohan, 'content' => 'Found a hidden bakery in the city that makes the most incredible sourdough. Kept the location secret so it doesn\'t get ruined.',                'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800', 'auth' => 1.80, 'views' => 145, 'hours' => 9],
            ['author' => $rohan, 'content' => 'Hot take: Filter coffee is vastly superior to any 6-dollar iced latte. Don\'t @ me.',                                                            'image' => null, 'auth' => 1.65, 'views' => 280, 'hours' => 18],

            // Maya (music, art)
            ['author' => $maya, 'content' => 'Practicing acoustic guitar in my messy bedroom at 2 AM. Mistakes included. Music feels pure when nobodies watching.',                          'image' => null, 'auth' => 1.90, 'views' => 30,  'hours' => 24],
            ['author' => $maya, 'content' => 'Finished my first oil painting in 3 years. It is imperfect and full of flaws, just like me. I love it.',                                     'image' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=800', 'auth' => 1.88, 'views' => 47,  'hours' => 14],
            ['author' => $maya, 'content' => 'Performed at an open mic after months of stage fear. My hands were shaking but I finished my song. The applause felt surreal.',               'image' => null, 'auth' => 1.96, 'views' => 115, 'hours' => 7],
            ['author' => $maya, 'content' => 'Spent Sunday afternoon at a vinyl record store. Found a 1978 pressing of Rumours. This is what happiness sounds like.',                      'image' => 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=800', 'auth' => 1.72, 'views' => 38,  'hours' => 30],

            // Vikram (fitness)
            ['author' => $vikram, 'content' => '300 consecutive days of working out. Not because I love it every day, but because discipline beats motivation.',                              'image' => 'https://images.unsplash.com/photo-1534258936925-c58bed479fcb?w=800', 'auth' => 1.80, 'views' => 142, 'hours' => 4],
            ['author' => $vikram, 'content' => 'Your body keeps score. Take a walk, stretch, drink water. The small habits compound into big changes.',                                      'image' => null, 'auth' => 1.55, 'views' => 280, 'hours' => 9],
            ['author' => $vikram, 'content' => 'Completed my first marathon in Bangalore humidity. Nearly gave up at 32km. A random stranger ran alongside me and said "you got this." I finished.','image' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800', 'auth' => 1.93, 'views' => 198, 'hours' => 16],
            ['author' => $vikram, 'content' => 'Pre-workout vs post-workout: same person, different mindset. The hard part is showing up.',                                                'image' => null, 'auth' => 1.40, 'views' => 65,  'hours' => 22],

            // Priya (food, recipes)
            ['author' => $priya, 'content' => 'My grandmothers paneer recipe finally perfected after 17 attempts. The secret ingredient? Patience. And a pinch of hing.',                   'image' => 'https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=800', 'auth' => 1.82, 'views' => 93,  'hours' => 5],
            ['author' => $priya, 'content' => 'Tried a 3-Michelin-star restaurant last night. Honestly? The street-side momos near my office hit harder.',                                    'image' => null, 'auth' => 1.78, 'views' => 410, 'hours' => 11],
            ['author' => $priya, 'content' => 'Baking sourdough at midnight because insomnia + flour = therapy. This loaf has my tears and 3AM energy in every bite.',                        'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800', 'auth' => 1.87, 'views' => 76,  'hours' => 3],

            // Arjun (tech, coding)
            ['author' => $arjun, 'content' => 'Deployed a feature at 2AM, broke production at 2:05AM, fixed it by 2:30AM. The adrenaline of being a solo founder is something else.',      'image' => null, 'auth' => 1.75, 'views' => 167, 'hours' => 6],
            ['author' => $arjun, 'content' => 'Open-sourced our internal testing framework today. 500 stars in 6 hours. The dev community is incredible when you give back.',              'image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800', 'auth' => 1.70, 'views' => 203, 'hours' => 13],
            ['author' => $arjun, 'content' => 'The best code I wrote this week? A script that automatically sends thank-you notes to users who refer friends. Tech meets humanity.',       'image' => null, 'auth' => 1.85, 'views' => 58,  'hours' => 2],

            // Sara (poetry, writing)
            ['author' => $sara, 'content' => 'She asked me when I knew I loved her. I said: when your silence felt louder than anyone elses words.',                                         'image' => null, 'auth' => 1.94, 'views' => 520, 'hours' => 8],
            ['author' => $sara, 'content' => 'Published my first poetry collection today. 42 pages of vulnerable honesty. If even one person reads it and feels less alone, it was worth it.', 'image' => 'https://images.unsplash.com/photo-1474932430478-367dbb6832c1?w=800', 'auth' => 1.92, 'views' => 140, 'hours' => 19],
            ['author' => $sara, 'content' => 'Some friendships are seasons. Others are decades. Learning to tell the difference has been my hardest lesson this year.',                       'image' => null, 'auth' => 1.88, 'views' => 185, 'hours' => 1],

            // Karan (gaming, streaming)
            ['author' => $karan, 'content' => 'Streamed for 12 hours straight. Peak 2,300 viewers. Fell asleep on stream at the end. My chat just watched me nap for an hour. Legends.',    'image' => null, 'auth' => 1.60, 'views' => 750, 'hours' => 9],
            ['author' => $karan, 'content' => 'Building my own gaming PC from scratch. Cable management is the real final boss.',                                                             'image' => 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?w=800', 'auth' => 1.45, 'views' => 88,  'hours' => 20],
            ['author' => $karan, 'content' => 'Finally hit Grandmaster in Valorant. Solo queue only. Took 8 months and countless mental breakdowns but we made it.',                      'image' => null, 'auth' => 1.68, 'views' => 230, 'hours' => 14],

            // Ananya (student, books)
            ['author' => $ananya, 'content' => 'Finished 52 books this year. Here is the thing: reading is not about the number. It is about the one book that changes how you see the world.','image' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=800', 'auth' => 1.78, 'views' => 105, 'hours' => 7],
            ['author' => $ananya, 'content' => 'Exam season survival mode. If you need me, I will be in the library, 3rd floor, corner seat, crying into my highlighter.',                'image' => null, 'auth' => 1.72, 'views' => 390, 'hours' => 4],
            ['author' => $ananya, 'content' => 'Just discovered my professor follows me on Instagram. Time to delete 3 years of questionable content.',                                         'image' => null, 'auth' => 1.55, 'views' => 620, 'hours' => 2],

            // Rahul (photography, wanderlust)
            ['author' => $rahul, 'content' => 'Captured the Milky Way over Spiti Valley at -5°C. My fingers went numb but the photograph will last forever.',                                'image' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800', 'auth' => 1.90, 'views' => 175, 'hours' => 10],
            ['author' => $rahul, 'content' => 'The best camera is the one you have with you. Shot an entire wedding on my iPhone and nobody noticed the difference.',                        'image' => null, 'auth' => 1.50, 'views' => 84,  'hours' => 25],
            ['author' => $rahul, 'content' => 'Varanasi at dawn from a boat on the Ganges. If you have never seen the sun rise over those ghats, put it on your bucket list.',             'image' => 'https://images.unsplash.com/photo-1566837945700-30057527ade0?w=800', 'auth' => 1.85, 'views' => 220, 'hours' => 17],

            // Ishita (yoga, wellness)
            ['author' => $ishita, 'content' => 'Trained 200 hours for my yoga teacher certification. The real learning was not the poses — it was sitting with my own discomfort.',         'image' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800', 'auth' => 1.88, 'views' => 63,  'hours' => 6],
            ['author' => $ishita, 'content' => 'Mornings without my phone for the first 30 minutes. Try it for a week and tell me your anxiety levels don not drop.',                       'image' => null, 'auth' => 1.70, 'views' => 280, 'hours' => 3],
            ['author' => $ishita, 'content' => 'Gratitude is not toxic positivity. It is a survival tool. Some days "I am grateful I got out of bed" is enough.',                          'image' => null, 'auth' => 1.82, 'views' => 145, 'hours' => 11],

            // Dev (software engineer)
            ['author' => $dev, 'content' => 'Pair programmed with a junior dev for 4 hours today. Watching someone have their "aha" moment is more satisfying than shipping any feature.',  'image' => null, 'auth' => 1.76, 'views' => 34,  'hours' => 4],
            ['author' => $dev, 'content' => 'My terminal setup is basically 90% aesthetic and 10% productivity. And yes, I use neovim btw.',                                               'image' => 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=800', 'auth' => 1.30, 'views' => 95,  'hours' => 16],
            ['author' => $dev, 'content' => 'Told my manager I need 2 weeks to refactor the legacy codebase. He laughed. I was not joking.',                                                'image' => null, 'auth' => 1.65, 'views' => 430, 'hours' => 8],

            // Naina (fashion, design)
            ['author' => $naina, 'content' => 'Designed and stitched my own outfit for Diwali this year. It does not fit perfectly but it is 100% mine. That makes it beautiful.',          'image' => 'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?w=800', 'auth' => 1.85, 'views' => 78,  'hours' => 12],
            ['author' => $naina, 'content' => 'Sustainable fashion is not a trend, it is a responsibility. Here is my thrift haul from last weekend: 7 pieces, 1200 rupees total.',       'image' => 'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=800', 'auth' => 1.78, 'views' => 112, 'hours' => 5],
            ['author' => $naina, 'content' => 'Spent 3 hours draping a saree for a wedding. Totally worth it. Traditional wear has a different kind of elegance.',                          'image' => null, 'auth' => 1.50, 'views' => 55,  'hours' => 24],

            // Kabir (journalist, podcast)
            ['author' => $kabir, 'content' => 'Interviewed a 90-year-old freedom fighter today. History is not in textbooks — it is in the wrinkles of people who lived it.',                'image' => null, 'auth' => 1.95, 'views' => 265, 'hours' => 7],
            ['author' => $kabir, 'content' => 'My podcast just crossed 100K downloads. We started in a closet with a 2000-rupee microphone. Consistency beats perfection.',               'image' => 'https://images.unsplash.com/photo-1478737270239-2f02b77fc618?w=800', 'auth' => 1.72, 'views' => 180, 'hours' => 15],
            ['author' => $kabir, 'content' => 'Journalism tip: the best stories are not the ones with the loudest voices. They are the ones whispered in quiet moments of trust.',        'image' => null, 'auth' => 1.88, 'views' => 97,  'hours' => 2],
        ];

        $createdPosts = [];
        foreach ($postsData as $idx => $item) {
            $post = Post::create([
                'author_id' => $item['author']->id,
                'content' => $item['content'],
                'image_url' => $item['image'],
                'authenticity_score' => $item['auth'],
                'view_count' => $item['views'],
                'embedding' => $this->pseudoVector($idx, $item['content'], $item['author']->id),
                'created_at' => now()->subHours($item['hours']),
                'updated_at' => now()->subHours($item['hours']),
            ]);
            $createdPosts[] = $post;
        }

        echo "Seeded " . count($createdPosts) . " posts.\n";

        // ====================================================================
        // 3. INTERACTIONS — Build a rich social graph
        // ====================================================================
        $interactionTypes = ['view', 'reaction', 'reply'];
        $totalInteractions = 0;

        // Helper: create interaction with random-ish time
        $interact = function (User $user, Post $post, string $type, int $hoursAgo) use (&$totalInteractions) {
            Interaction::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'author_id' => $post->author_id,
                'interaction_type' => $type,
                'created_at' => now()->subHours($hoursAgo),
            ]);
            $totalInteractions++;
        };

        // --- Aarav's interactions ---
        // Reacts/replies to Diya's posts (strong bond)
        foreach ([$createdPosts[5], $createdPosts[7], $createdPosts[8], $createdPosts[9]] as $p) {
            $interact($aarav, $p, 'reaction', rand(1, 6));
            $interact($aarav, $p, 'reply', rand(1, 6));
        }
        // Views Diya's posts
        foreach ([$createdPosts[5], $createdPosts[6], $createdPosts[7], $createdPosts[8], $createdPosts[9]] as $p) {
            $interact($aarav, $p, 'view', rand(1, 48));
        }
        // Interacts with Rohan's food posts
        $interact($aarav, $createdPosts[10], 'reaction', rand(1, 10));
        $interact($aarav, $createdPosts[11], 'reply', rand(1, 10));
        $interact($aarav, $createdPosts[12], 'reaction', rand(1, 10));

        // --- Diya's interactions ---
        // Reacts/replies to Aarav's travel posts
        foreach ([$createdPosts[0], $createdPosts[2], $createdPosts[4]] as $p) {
            $interact($diya, $p, 'reaction', rand(1, 5));
            $interact($diya, $p, 'reply', rand(1, 5));
        }
        // Views many posts
        foreach ([$createdPosts[0], $createdPosts[1], $createdPosts[2], $createdPosts[3], $createdPosts[10], $createdPosts[11], $createdPosts[19], $createdPosts[20]] as $p) {
            $interact($diya, $p, 'view', rand(1, 72));
        }
        // Interacts with Sara's poetry
        $interact($diya, $createdPosts[27], 'reaction', rand(1, 8));
        $interact($diya, $createdPosts[28], 'reply', rand(1, 8));

        // --- Rohan's interactions ---
        $interact($rohan, $createdPosts[0], 'reaction', rand(1, 3));
        $interact($rohan, $createdPosts[3], 'reply', rand(1, 3));
        $interact($rohan, $createdPosts[7], 'reaction', rand(1, 8));
        $interact($rohan, $createdPosts[10], 'reaction', rand(1, 12));
        $interact($rohan, $createdPosts[11], 'reply', rand(1, 12));
        $interact($rohan, $createdPosts[21], 'reaction', rand(1, 10));
        foreach ([$createdPosts[0], $createdPosts[2], $createdPosts[5], $createdPosts[7], $createdPosts[11], $createdPosts[12], $createdPosts[21]] as $p) {
            $interact($rohan, $p, 'view', rand(1, 72));
        }

        // --- Vikram (fitness) interactions ---
        $interact($vikram, $createdPosts[0], 'view', rand(1, 5));
        $interact($vikram, $createdPosts[5], 'reaction', rand(1, 8));
        $interact($vikram, $createdPosts[19], 'reaction', rand(1, 10));
        $interact($vikram, $createdPosts[20], 'reply', rand(1, 10));
        foreach ([$createdPosts[5], $createdPosts[7], $createdPosts[19], $createdPosts[20], $createdPosts[33], $createdPosts[34]] as $p) {
            $interact($vikram, $p, 'view', rand(1, 48));
        }

        // --- Priya interactions ---
        $interact($priya, $createdPosts[10], 'reaction', rand(1, 10));
        $interact($priya, $createdPosts[11], 'reply', rand(1, 10));
        $interact($priya, $createdPosts[23], 'reaction', rand(1, 5));
        $interact($priya, $createdPosts[24], 'reply', rand(1, 5));
        foreach ([$createdPosts[10], $createdPosts[11], $createdPosts[12], $createdPosts[23], $createdPosts[24]] as $p) {
            $interact($priya, $p, 'view', rand(1, 48));
        }

        // --- Arjun interactions ---
        $interact($arjun, $createdPosts[5], 'reaction', rand(1, 10));
        $interact($arjun, $createdPosts[7], 'reply', rand(1, 10));
        $interact($arjun, $createdPosts[8], 'reaction', rand(1, 10));
        $interact($arjun, $createdPosts[26], 'reply', rand(1, 5));
        foreach ([$createdPosts[5], $createdPosts[7], $createdPosts[8], $createdPosts[25], $createdPosts[26]] as $p) {
            $interact($arjun, $p, 'view', rand(1, 72));
        }

        // --- Sara interactions ---
        $interact($sara, $createdPosts[5], 'reaction', rand(1, 10));
        $interact($sara, $createdPosts[9], 'reply', rand(1, 10));
        $interact($sara, $createdPosts[27], 'reaction', rand(1, 8));
        $interact($sara, $createdPosts[28], 'reply', rand(1, 8));
        $interact($sara, $createdPosts[29], 'reaction', rand(1, 4));
        foreach ([$createdPosts[5], $createdPosts[9], $createdPosts[27], $createdPosts[28], $createdPosts[29]] as $p) {
            $interact($sara, $p, 'view', rand(1, 48));
        }

        // --- Karan interactions ---
        $interact($karan, $createdPosts[30], 'reaction', rand(1, 8));
        $interact($karan, $createdPosts[32], 'reply', rand(1, 8));
        foreach ([$createdPosts[30], $createdPosts[31], $createdPosts[32], $createdPosts[19], $createdPosts[20]] as $p) {
            $interact($karan, $p, 'view', rand(1, 48));
        }

        // --- Ananya interactions ---
        $interact($ananya, $createdPosts[27], 'reaction', rand(1, 10));
        $interact($ananya, $createdPosts[28], 'reply', rand(1, 10));
        $interact($ananya, $createdPosts[33], 'reaction', rand(1, 10));
        $interact($ananya, $createdPosts[18], 'reaction', rand(1, 10));
        foreach ([$createdPosts[27], $createdPosts[28], $createdPosts[33], $createdPosts[34], $createdPosts[35], $createdPosts[16]] as $p) {
            $interact($ananya, $p, 'view', rand(1, 48));
        }

        // --- Rahul interactions ---
        $interact($rahul, $createdPosts[2], 'reaction', rand(1, 8));
        $interact($rahul, $createdPosts[4], 'reply', rand(1, 8));
        $interact($rahul, $createdPosts[36], 'reaction', rand(1, 10));
        $interact($rahul, $createdPosts[38], 'reply', rand(1, 10));
        foreach ([$createdPosts[2], $createdPosts[4], $createdPosts[36], $createdPosts[37], $createdPosts[38], $createdPosts[10]] as $p) {
            $interact($rahul, $p, 'view', rand(1, 48));
        }

        // --- Ishita interactions ---
        $interact($ishita, $createdPosts[0], 'reaction', rand(1, 8));
        $interact($ishita, $createdPosts[33], 'reaction', rand(1, 10));
        $interact($ishita, $createdPosts[39], 'reply', rand(1, 8));
        $interact($ishita, $createdPosts[41], 'reaction', rand(1, 8));
        foreach ([$createdPosts[0], $createdPosts[33], $createdPosts[39], $createdPosts[40], $createdPosts[41], $createdPosts[19]] as $p) {
            $interact($ishita, $p, 'view', rand(1, 48));
        }

        // --- Dev interactions ---
        $interact($dev, $createdPosts[25], 'reaction', rand(1, 5));
        $interact($dev, $createdPosts[26], 'reply', rand(1, 5));
        $interact($dev, $createdPosts[7], 'reaction', rand(1, 10));
        $interact($dev, $createdPosts[42], 'reply', rand(1, 4));
        $interact($dev, $createdPosts[44], 'reaction', rand(1, 4));
        foreach ([$createdPosts[25], $createdPosts[26], $createdPosts[7], $createdPosts[42], $createdPosts[43], $createdPosts[44]] as $p) {
            $interact($dev, $p, 'view', rand(1, 48));
        }

        // --- Naina interactions ---
        $interact($naina, $createdPosts[33], 'view', rand(1, 24));
        $interact($naina, $createdPosts[45], 'reaction', rand(1, 12));
        $interact($naina, $createdPosts[46], 'reply', rand(1, 12));
        foreach ([$createdPosts[45], $createdPosts[46], $createdPosts[47]] as $p) {
            $interact($naina, $p, 'view', rand(1, 24));
        }
        $interact($naina, $createdPosts[15], 'reaction', rand(1, 12));
        $interact($naina, $createdPosts[17], 'reaction', rand(1, 12));

        // --- Kabir interactions ---
        $interact($kabir, $createdPosts[27], 'reaction', rand(1, 10));
        $interact($kabir, $createdPosts[28], 'reply', rand(1, 10));
        $interact($kabir, $createdPosts[48], 'reaction', rand(1, 8));
        $interact($kabir, $createdPosts[49], 'reply', rand(1, 8));
        $interact($kabir, $createdPosts[3], 'reaction', rand(1, 10));
        foreach ([$createdPosts[27], $createdPosts[28], $createdPosts[48], $createdPosts[49], $createdPosts[3], $createdPosts[7], $createdPosts[50]] as $p) {
            $interact($kabir, $p, 'view', rand(1, 72));
        }

        // --- Cross-network views (everyone viewing popular posts) ---
        $popularPosts = [$createdPosts[9], $createdPosts[27], $createdPosts[21], $createdPosts[24], $createdPosts[30], $createdPosts[35], $createdPosts[44], $createdPosts[34], $createdPosts[49]];
        foreach ($users as $viewer) {
            foreach ($popularPosts as $pp) {
                if (rand(0, 3) === 0) { // 25% chance per viewer-popular-post pair
                    $interact($viewer, $pp, 'view', rand(1, 72));
                }
            }
        }

        echo "Seeded {$totalInteractions} interactions.\n";
    }
}
