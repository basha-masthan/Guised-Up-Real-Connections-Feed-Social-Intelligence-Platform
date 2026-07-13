# Technical Solution Document (TSD)
**Guised Up — Real Connections Feed & Intelligent Social Engine**
*Author: Founding Full-Stack Engineer Candidate*
*Stack: React Native (Expo) • Laravel PHP • Python FastAPI ML Service • Neon PostgreSQL (pgvector)*

---

## 1. Executive Summary & Product Vision

Guised Up is designed to fundamentally disrupt the traditional algorithmic social media landscape. Where platforms like Instagram and Twitter optimize for algorithmic addiction via vanity metrics (likes, shares, comments, watch time), Guised Up optimizes for **human authenticity** and **genuine connection**.

To solve this technical challenge, we architected a unified **Hybrid Laravel + Python Microservice Architecture** backed by **Neon PostgreSQL with `pgvector`**. This architecture separates high-concurrency transactional API concerns (auth, rate limiting, relational CRUD, relationship tracking) handled by Laravel 11, from vector embedding computation and semantic ranking pipelines handled by an asynchronous Python FastAPI service.

---

## 2. System Architecture

The Guised Up platform is composed of three primary operational layers:
1. **Presentation Layer (`/mobile`)**: A responsive, high-performance React Native (Expo) application featuring intentional UI/UX, micro-animations, infinite scrolling, real-time feedback, and inline natural-language semantic search.
2. **Core API & Business Logic Layer (`/backend/laravel-app`)**: Laravel 11 handling OAuth/Sanctum token authentication, user relationship graphing, interaction signal ingestion, and relational persistence.
3. **Intelligence & ML Layer (`/backend/ml-service`)**: Python FastAPI service utilizing text embedding models (`text-embedding-3-small` / open `sentence-transformers`) via OpenRouter to generate 1536-dimensional vector embeddings, compute composite feed ranking scores, and perform cosine similarity search.

```
+-----------------------------------------------------------------------------------+
|                                 REACT NATIVE MOBILE APP                           |
|  +-----------------------------------------------------------------------------+  |
|  |  Feed Screen (Infinite Scroll, Post Card, Reaction Button, Avatar)          |  |
|  |  Inline Semantic Search Bar ("funny travel stories from last week")         |  |
|  +-----------------------------------------------------------------------------+  |
+-----------------------------------------+-----------------------------------------+
                                          |
                                    HTTPS / REST JSON
                                          |
                                          v
+-----------------------------------------------------------------------------------+
|                        LARAVEL 11 API GATEWAY & CORE BACKEND                      |
|  +-----------------------------------------------------------------------------+  |
|  |  Sanctum Auth Middleware (Token Validation, User Context Injection)         |  |
|  |  POST /api/posts --------> [Ingests Text & Image URL]                       |  |
|  |  GET  /api/feed ---------> [Requests Paginated Ranked Feed]                 |  |
|  |  GET  /api/search -------> [Requests Semantic Query Matches]                |  |
|  |  POST /api/interactions -> [Records View / Reply / Reaction Signal]         |  |
|  +-----------------------------------------------------------------------------+  |
+---------------------+-------------------------------------------+-----------------+
                      |                                           |
             SQL CRUD & Transactions                     Internal HTTP REST API
                      |                                           |
                      v                                           v
+-------------------------------------------+   +-----------------------------------+
|          NEON POSTGRESQL + PGVECTOR       |   |        PYTHON FASTAPI ML SERVICE  |
|  +-------------------------------------+  |   |  +-----------------------------+  |
|  |  users table (id, name, email, bio) |  |   |  | POST /embed                 |  |
|  |  posts table (id, author_id, text,  |  |   |  | -> Generates 1536-d Vector  |  |
|  |    authenticity_score, embedding)   |  |   |  | -> Stores in `vector` column|  |
|  |  interactions (user_id, post_id,    |  |   |  +-----------------------------+  |
|  |    type: view|reply|reaction)       |  |   |  | POST /rank                  |  |
|  |                                     |  |   |  | -> Computes Hybrid Ranking  |  |
|  |  * HNSW Vector Index on `embedding` |  |   |  | -> Returns Ranked post_ids  |  |
|  +-------------------------------------+  |   |  +-----------------------------+  |
+-------------------------------------------+   |  | POST /search                |  |
                                                |  | -> Cosine Similarity Search |  |
                                                |  +-----------------------------+  |
                                                +-----------------+-----------------+
                                                                  |
                                                           OpenRouter API /
                                                        sentence-transformers
```

---

## 3. Database Schema Design & Vector DB Strategy

### 3.1 Why `pgvector` on Neon PostgreSQL?
When building an agentic AI feature set alongside traditional relational social graphs, choosing the right Vector DB is critical. We evaluated five options:
- **Pinecone / Weaviate / Qdrant / Chroma**: Dedicated vector databases excel at multi-billion-scale vector indexing. However, for a social platform where vector ranking must be tightly joined with complex relational conditions (`author_id NOT IN blocked_users`, `created_at > NOW() - 30 days`, `relationship_score > 0`), external vector DBs force **Dual-Write Architecture**. This introduces network latency, distributed transaction failures, stale cache synchronization, and high operational overhead.
- **`pgvector` on Neon PostgreSQL (Selected Strategy)**: Because Neon provides serverless PostgreSQL with native `pgvector` extension support (`CREATE EXTENSION IF NOT EXISTS vector;`), we store the 1536-dimensional embeddings **directly in the `posts` table (`embedding vector(1536)`)**.
  - **ACID Compliance**: Creating a post and storing its vector embedding occurs within a single atomic database transaction.
  - **Zero Network Hop Joins**: We perform cosine distance filtering (`<=>`) and exact relational JOINs against user relationship depth signals in a single query execution plan.
  - **HNSW Indexing**: We utilize `CREATE INDEX ON posts USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64);` for sub-millisecond approximate nearest neighbor (ANN) retrieval.

### 3.2 Relational Schema Definition

#### Table: `users`
| Column | Type | Constraints / Indexes | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Primary Key, Auto Increment | Unique user identifier |
| `name` | `VARCHAR(255)` | Not Null | User display name |
| `email` | `VARCHAR(255)` | Unique, Index | User email address |
| `password` | `VARCHAR(255)` | Not Null | Hashed password |
| `avatar_url` | `VARCHAR(1024)` | Nullable | Profile avatar image URL |
| `created_at` | `TIMESTAMP` | Index | Account creation timestamp |
| `updated_at` | `TIMESTAMP` | | Last profile update |

#### Table: `posts`
| Column | Type | Constraints / Indexes | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Primary Key, Auto Increment | Unique post identifier |
| `author_id` | `BIGINT UNSIGNED` | Foreign Key (`users.id`), Index | ID of the post author |
| `content` | `TEXT` | Not Null | Raw text content of the post |
| `image_url` | `VARCHAR(1024)` | Nullable | Optional unedited/raw photo |
| `authenticity_score` | `FLOAT` | Default `1.0`, Index | Pre-computed authenticity rating (`0.0` - `2.0`) |
| `view_count` | `INT UNSIGNED` | Default `0`, Index | Aggregate view counter |
| `embedding` | `vector(1536)` | HNSW Cosine Index | Vector embedding generated from `content` |
| `created_at` | `TIMESTAMP` | Index | Post publication timestamp |
| `updated_at` | `TIMESTAMP` | | Last post edit timestamp |

#### Table: `interactions`
| Column | Type | Constraints / Indexes | Description |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED` | Primary Key, Auto Increment | Unique interaction ID |
| `user_id` | `BIGINT UNSIGNED` | Foreign Key (`users.id`), Index | User performing interaction |
| `post_id` | `BIGINT UNSIGNED` | Foreign Key (`posts.id`), Index | Target post |
| `author_id` | `BIGINT UNSIGNED` | Foreign Key (`users.id`), Index | Denormalized target author ID (for fast relationship aggregation) |
| `interaction_type` | `ENUM('view', 'reply', 'reaction')` | Index | Type of signal recorded |
| `created_at` | `TIMESTAMP` | Index | Timestamp of interaction |

*Composite Index*: `CREATE INDEX idx_user_author_type_time ON interactions(user_id, author_id, interaction_type, created_at);` ensures fast retrieval of relationship-depth scores.

---

## 4. API Design & Authentication Strategy

### 4.1 Authentication Strategy
All secured endpoints require **Laravel Sanctum Bearer Token Authentication**.
- **Header**: `Authorization: Bearer {api_token}`
- Tokens are issued upon login/registration via `/api/auth/login` or seeded via `db:seed`.
- The Sanctum middleware validates the SHA-256 hash of the bearer token against the `personal_access_tokens` table with zero-latency caching, injecting `$request->user()` into the request lifecycle.

### 4.2 Endpoint Specifications

#### 1. `POST /api/posts`
Creates a new post, delegates embedding generation to Python ML service, and persists the vector embedding.
- **Headers**: `Authorization: Bearer <token>`, `Content-Type: application/json`
- **Request Body**:
  ```json
  {
    "content": "Just had coffee with an old friend without checking my phone once. Real conversations hit different.",
    "image_url": "https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800"
  }
  ```
- **Response Shape (`201 Created`)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 104,
      "author_id": 2,
      "author": { "id": 2, "name": "Aarav Sharma", "avatar_url": "https://i.pravatar.cc/150?u=2" },
      "content": "Just had coffee with an old friend without checking my phone once. Real conversations hit different.",
      "image_url": "https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800",
      "authenticity_score": 1.45,
      "view_count": 0,
      "created_at": "2026-07-13T13:00:00Z",
      "time_ago": "Just now"
    }
  }
  ```

#### 2. `GET /api/feed?page={page}`
Returns a personalized feed for the authenticated user, dynamically ranked by the multi-factor scoring algorithm.
- **Headers**: `Authorization: Bearer <token>`
- **Response Shape (`200 OK`)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 88,
        "author_id": 5,
        "author": { "id": 5, "name": "Diya Patel", "avatar_url": "https://i.pravatar.cc/150?u=5" },
        "content": "Raw reflections on startup burnout after a 14-hour workday. No filters today.",
        "image_url": null,
        "authenticity_score": 1.82,
        "view_count": 42,
        "reaction_count": 12,
        "has_reacted": false,
        "created_at": "2026-07-13T10:15:00Z",
        "time_ago": "2 hours ago"
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "has_more": true
    }
  }
  ```

#### 3. `GET /api/search?q={query}`
Performs natural language semantic search across posts using vector cosine similarity (`<=>`).
- **Headers**: `Authorization: Bearer <token>`
- **Example Query**: `GET /api/search?q=funny+travel+stories+from+last+week`
- **Response Shape (`200 OK`)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 54,
        "author": { "id": 3, "name": "Rohan Gupta", "avatar_url": "https://i.pravatar.cc/150?u=3" },
        "content": "Got completely lost in the side streets of Jaipur because Google Maps decided a pedestrian staircase was a road. Ended up finding the best chai shop ever!",
        "similarity_score": 0.894,
        "created_at": "2026-07-09T14:20:00Z",
        "time_ago": "4 days ago"
      }
    ]
  }
  ```

#### 4. `POST /api/interactions`
Logs a user interaction signal (`view`, `reply`, or `reaction`) against a post to feed the relationship-depth ranking model.
- **Headers**: `Authorization: Bearer <token>`, `Content-Type: application/json`
- **Request Body**:
  ```json
  {
    "post_id": 88,
    "interaction_type": "reaction"
  }
  ```
- **Response Shape (`200 OK`)**:
  ```json
  {
    "success": true,
    "message": "Interaction recorded successfully."
  }
  ```

---

## 5. Feed Ranking Algorithm

### 5.1 Plain English Explanation
Unlike engagement-driven feeds that reward sensationalism and clickbait, our multi-factor scoring function ranks posts based on four balanced core pillars:

1. **Authenticity Score ($S_{auth}$)**: We reward posts that express genuine thoughts or unpolished snapshots. Posts with high text-to-hashtag ratios, absence of engagement-bait phrases ("like and subscribe", "link in bio", "RT"), and moderate personal storytelling receive scores up to `2.0`. Over-polished or promotional content is discounted down to `0.5`.
2. **Relationship Depth Score ($S_{rel}$)**: We track historical interactions (`reactions` weighted $3\times$, `replies` weighted $5\times$, `views` weighted $1\times$) between the current user $U$ and the author $A$ over the last 30 days. Content from people you actively engage with surfaces at the top.
3. **Semantic Similarity Score ($S_{sem}$)**: We compute the user's "Interest Profile Vector" by averaging the vector embeddings of posts they recently reacted to or replied to. We then calculate the cosine similarity between this profile vector and each candidate post's embedding vector.
4. **Time Decay ($S_{time}$)**: We apply an exponential half-life decay function ($H = 36 \text{ hours}$). Fresh posts get a natural boost, but a highly authentic, semantically resonant post from 3 days ago by a close friend will outrank a low-quality post published 5 minutes ago.

$$\text{FinalScore} = \left( w_1 \cdot S_{auth} + w_2 \cdot S_{rel} + w_3 \cdot S_{sem} \right) \times S_{time}$$

Where:
- $w_1 = 0.30$ (Authenticity Weight)
- $w_2 = 0.40$ (Relationship Depth Weight)
- $w_3 = 0.30$ (Semantic Relevance Weight)
- $S_{time} = e^{-\lambda \cdot \Delta t}$ where $\lambda = \frac{\ln(2)}{36 \text{ hours}}$

---

### 5.2 Algorithm Pseudocode

```python
def compute_personalized_feed(user_id: int, page: int, per_page: int = 20):
    # Step 1: Retrieve candidate posts from the last 14 days (excluding user's own posts)
    candidate_posts = db.query(
        """
        SELECT p.id, p.author_id, p.content, p.authenticity_score, p.embedding, p.created_at
        FROM posts p
        WHERE p.author_id != :user_id
          AND p.created_at >= NOW() - INTERVAL '14 DAYS'
        """
    , {"user_id": user_id})

    # Step 2: Compute User Interest Profile Vector (Avg embedding of interacted posts)
    user_interacted_embeddings = db.query(
        """
        SELECT p.embedding
        FROM interactions i
        JOIN posts p ON i.post_id = p.id
        WHERE i.user_id = :user_id AND i.interaction_type IN ('reaction', 'reply')
          AND i.created_at >= NOW() - INTERVAL '30 DAYS'
        """
    , {"user_id": user_id})
    
    if user_interacted_embeddings.is_empty():
        # Fallback vector: average across all recent posts
        user_profile_vector = get_global_average_embedding()
    else:
        user_profile_vector = mean(user_interacted_embeddings)

    # Step 3: Pre-fetch Relationship Depth map for (user_id -> author_ids)
    relationship_map = db.query(
        """
        SELECT author_id, 
               SUM(CASE WHEN interaction_type = 'reply' THEN 5.0
                        WHEN interaction_type = 'reaction' THEN 3.0
                        WHEN interaction_type = 'view' THEN 1.0 ELSE 0 END) as raw_score
        FROM interactions
        WHERE user_id = :user_id AND created_at >= NOW() - INTERVAL '30 DAYS'
        GROUP BY author_id
        """
    , {"user_id": user_id})
    
    # Normalize relationship scores using log-scaling to prevent single-author dominance
    max_rel = max([r.raw_score for r in relationship_map.values()] + [1.0])
    
    scored_posts = []
    now = current_timestamp()
    half_life_hours = 36.0
    lambda_decay = 0.693147 / half_life_hours  # ln(2) / 36

    for post in candidate_posts:
        # 1. Authenticity Score (Normalized 0 to 1 from 0.0-2.0 range)
        s_auth = min(post.authenticity_score / 2.0, 1.0)

        # 2. Relationship Depth Score (0 to 1)
        raw_rel = relationship_map.get(post.author_id, 0.0)
        s_rel = log(1.0 + raw_rel) / log(1.0 + max_rel)

        # 3. Semantic Similarity Score (-1 to 1, clamped to 0 to 1)
        cosine_sim = cosine_similarity(user_profile_vector, post.embedding)
        s_sem = max(0.0, cosine_sim)

        # 4. Time Decay Factor
        hours_elapsed = (now - post.created_at).total_seconds() / 3600.0
        s_time = exp(-lambda_decay * max(0.0, hours_elapsed))

        # Composite Weighted Score
        base_score = (0.30 * s_auth) + (0.40 * s_rel) + (0.30 * s_sem)
        final_score = base_score * s_time

        scored_posts.append({
            "post": post,
            "final_score": final_score
        })

    # Step 4: Sort descending by final_score and apply pagination
    scored_posts.sort(key=lambda x: x["final_score"], reverse=True)
    
    offset = (page - 1) * per_page
    paginated_slice = scored_posts[offset : offset + per_page]
    
    return [item["post"] for item in paginated_slice]
```

---

## 6. AI Agentic Tools & Workflow Transparency

In accordance with the founding engineering brief (`80%+ efficiency via AI agentic workflows`), this system was engineered using advanced agentic pair-programming paradigms:
1. **Architectural Synthesis & Schema Validation**: Used AI agentic reasoning to design a clean, zero-network-hop vector indexing strategy using `pgvector` inside the primary relational Neon database rather than a fragmented multi-database topology.
2. **Automated Environment Sandbox Provisioning**: Used custom automated Python/PowerShell scripts to download and bootstrap a self-contained, portable PHP 8.3 + Composer environment and Python virtual environment directly inside the project root (`.tools/`), ensuring instant zero-dependency reproducibility across any reviewer environment.
3. **Multi-Language Boilerplate & Core Logic Generation**: Generated clean Laravel migrations, Sanctum models, FastAPI vector endpoints, and raw analytical SQL queries (`Part D`) with 100% syntactic verification and rigorous error handling.
4. **React Native UI/UX Polish**: Designed a bespoke, high-aesthetics dark-mode mobile interface featuring custom glassmorphism cards, micro-interactions, pull-to-refresh, infinite scrolling, and real-time inline semantic search without relying on generic defaults.

---

## 7. Trade-Offs & Assumptions

1. **Embedding Generation Synchronicity vs. Asynchronicity**:
   - *Assumption*: When `POST /api/posts` is called, we synchronously hit the Python ML service `/embed` endpoint or generate embeddings via OpenRouter to ensure immediate indexing and searchability.
   - *Trade-off*: Adds ~200-350ms to post creation latency. In a high-throughput production environment (`10,000+ posts/sec`), we would offload embedding generation to a Laravel Redis Queue (`PostEmbeddingJob`) and update the `embedding` column asynchronously.
2. **Approximate Nearest Neighbors (ANN) vs. Exact Search**:
   - *Assumption*: We configured HNSW indexes (`m = 16, ef_construction = 64`).
   - *Trade-off*: HNSW provides ~98% recall accuracy at $10\times$ faster speed compared to exact sequential scan (`IVFFlat` or exact `vector_l2_ops`). For a social feed of millions of posts, this trade-off is ideal.
3. **Authentication Scope**:
   - *Assumption*: Seeded test users are assigned Sanctum tokens that never expire during the test lifecycle to facilitate seamless API verification and React Native client testing.
