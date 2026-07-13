# 🚀 Guised Up — Real Connections Feed & Social Intelligence Platform

> **Full-Stack Developer Take-Home Technical Project Submission**  
> *A 3-tier social intelligence platform featuring personalized AI-driven feed ranking, relational graph analytics, and vector semantic similarity.*

---

## 🔗 Live Deployments

| Component | Platform | Live URL |
| :--- | :---: | :--- |
| 📱 **Frontend Client (React Native Web)** | **Vercel** | [https://guised-up-feed.vercel.app](https://guised-up-feed.vercel.app) |
| 🐘 **Laravel Core Gateway** | **Render** | [https://guised-up-real-connections-feed-social.onrender.com](https://guised-up-real-connections-feed-social.onrender.com) |
| 🐍 **Python ML Service** | **Render** | [https://guised-up-ml-service.onrender.com](https://guised-up-ml-service.onrender.com) |
| 🗄️ **Vector Database** | **Neon** | Cloud-Hosted PostgreSQL (`pgvector`) |

---

## 🌟 The Product Brief & Core Pillars

**Guised Up** is a social platform built to help people show up authentically online — eliminating curated highlight reels and engagement-bait vanity metrics. Instead of ranking by raw clicks, likes, or sensationalist virality, Guised Up's **Real Connections Feed** surfaces content driven by four balanced pillars:

```
                  ┌─────────────────────────────────────────┐
                  │          Real Connections Feed          │
                  └────────────────────┬────────────────────┘
                                       │
         ┌──────────────────┬──────────┴──────────┬──────────────────┐
         ▼                  ▼                     ▼                  ▼
┌─────────────────┐┌─────────────────┐   ┌─────────────────┐┌─────────────────┐
│  Authenticity   ││  Relationship   │   │    Semantic     ││   Time Decay    │
│  Signals (30%)  ││   Depth (40%)   │   │ Relevance (30%) ││  (Exponential)  │
└─────────────────┘└─────────────────┘   └─────────────────┘└─────────────────┘
```

1. **Authenticity Signals ($S_{auth}$)**: Rewarding raw snapshots and genuine personal reflections while discounting promotional filter-heavy text.
2. **Relationship Depth ($S_{rel}$)**: Surfacing content from creators with whom you genuinely interact (`reactions`, `replies`, `views`) over the past 30 days.
3. **Semantic Relevance ($S_{sem}$)**: Understanding deep topical resonance via **1536-dimensional vector embeddings** and cosine similarity matching (`<=>`).
4. **Time Decay ($S_{time}$)**: Exponential half-life decay ($36\text{h}$) ensuring fresh items surface without burying evergreen high-resonance connections.

---

## 🧠 The Multi-Factor Ranking Formula

Every candidate post is dynamically scored using our multi-factor composite ranking formula computed on the ML service:

$$\text{FinalScore} = (0.30 \times S_{auth} + 0.40 \times S_{rel} + 0.30 \times S_{sem}) \times S_{time}$$

* **Authenticity ($S_{auth}$)**: Normalized $0.0 - 1.0$ based on post character length, writing structure, and formatting.
* **Relationship ($S_{rel}$)**: Logarithmic scaling based on reciprocal interactions.
* **Semantic ($S_{sem}$)**: Cosine similarity calculation of the user's interest profile embedding vs. the post content embedding.
* **Time Decay ($S_{time}$)**: Exponential decay model with a half-life ($\lambda$) optimized at 36 hours:
  $$S_{time} = e^{-\lambda \times t}$$

---

## 📁 Repository Structure

```text
Guised Up/
├── docs/
│   └── TSD.md                      # [Part A] Comprehensive Technical Solution Document
├── backend/
│   ├── laravel-app/                # [Part B] Core REST API & Sanctum Token Auth Gateway
│   │   ├── app/Http/Controllers/   # ApiController handling /posts, /feed, /search, /interactions
│   │   ├── database/migrations/    # Schema definitions (users, posts with embeddings, interactions)
│   │   ├── database/seeders/       # Rich social graph seeder with pre-built user relationships
│   │   └── tests/Feature/          # Automated feature/unit test suite (GuisedUpApiTest.php)
│   └── ml-service/                 # [Part B] Python FastAPI Vector & Multi-Factor Ranking Microservice
│       ├── main.py                 # Endpoints: /embed, /rank, /search, /health
│       ├── embedding.py            # OpenRouter API integration + deterministic semantic hash fallback
│       ├── ranker.py               # 4-Pillar composite score calculator
│       └── tests/                  # Python unit test suite (test_ranking.py)
├── mobile/                         # [Part C] React Native (Expo) Feed Screen with Dark Mode & Micro-interactions
│   ├── components/                 # PostCard, Header, ConfigModal (Live vs Demo toggle)
│   └── App.tsx                     # Infinite scroll Feed, inline natural search bar, zero-config demo mode
└── sql/
    └── queries.sql                 # [Part D] Raw PostgreSQL analytical & spam detection queries (D1-D4)
```

---

## 🚀 Quickstart & Setup Instructions

We engineered this repository for **instant reproducibility**. Whether testing with live Neon PostgreSQL + OpenRouter AI vectors or completely offline, you can boot the entire platform in minutes.

### 1. Python ML & Vector Service
The Python microservice handles embedding generation and analytical vector ranking.

```bash
# Navigate to the workspace root or Python directory
python -m venv .tools/venv
.\.tools\venv\Scripts\python.exe -m pip install -r backend/ml-service/requirements.txt
.\.tools\venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8001 --reload --app-dir backend/ml-service
```
*Service runs at `http://127.0.0.1:8001` (`/health`, `/embed`, `/rank`, `/search`).*

### 2. Backend: Laravel 11 API Gateway
Laravel handles authentication, rate limiting, and relational CRUD.

```bash
cd backend/laravel-app
cp .env.example .env

# Run database migrations and seed authentic users, posts, and relationships
php artisan migrate:fresh --seed

# Start the Laravel development server on port 8000
php artisan serve --host=127.0.0.1 --port=8000
```
*API runs at `http://127.0.0.1:8000`. You can instantly grab a test bearer token via `GET http://127.0.0.1:8000/api/test-token`.*

### 3. React Native (Expo) Mobile App
The mobile app features intentional dark-mode styling, infinite scrolling, micro-animations, and a configuration drawer.

```bash
cd mobile
npm install
npm run start # or npm run web / android / ios
```
> [!NOTE]
> **Zero-Config Demo Mode:** If you open the mobile client without running the backend API, simply tap the **⚙️ Cog icon** in the top header and switch to **🛡️ Built-in Demo Data Mode**. This enables 100% full UI evaluation (ranking, semantic filtering, reaction animations) offline!

---

## 🧪 Automated Verification & Testing

Both backends include rigorous automated test suites satisfying all assessment requirements:

### Python ML Ranking Logic Unit Tests (`backend/ml-service`)
```bash
.\.tools\venv\Scripts\pytest.exe backend/ml-service/tests/test_ranking.py -v
```
*Verifies authenticity weighting, relationship depth dominance, time decay curves, and unit vector cosine similarity math.*

### Laravel API Endpoint & Integration Feature Tests (`backend/laravel-app`)
```bash
cd backend/laravel-app
php artisan test --filter GuisedUpApiTest
```
*Verifies Sanctum auth, vector creation payload persistence, paginated feed return shape, search responses, and interaction view incrementing.*

---

## 📊 Part D — SQL Challenge Summary

All analytical SQL queries (`D1` - `D4`) are located in [`sql/queries.sql`](sql/queries.sql).
* **D1 (Top Active Users in 7 Days)**: Multi-aggregation `JOIN` across `interactions` utilizing conditional `COUNT(CASE ...)` for granular views/replies/reactions.
* **D2 (Personalized Candidate Feed)**: Common Table Expression (`WITH UserRelationshipScores`) ranking user-author interaction frequency descending over the last 30 days.
* **D3 (Anomaly High-View / Zero-Reaction Posts)**: Optimized `NOT EXISTS` subquery check identifying low-resonance or clickbait content exceeding 100 views without genuine reactions.
* **D4 (Spam & Bot Detection)**: Sliding window group aggregation (`HAVING COUNT(p.id) > 20`) capturing anomalous burst posting behaviors within 24-hour windows.

---

## 🤖 AI Agentic Workflow Documentation

In alignment with the founding engineer brief (`80%+ efficiency via agentic AI`), this system was developed collaboratively using advanced pair-programming agent capabilities:
1. **Zero-Dependency Sandbox Bootstrapping**: Automated cross-platform detection and local `.tools/` portable binary setup, allowing full PHP/Composer/Python runtime synthesis without requiring host OS admin modifications.
2. **Deterministic Vector Fallback Synthesis**: Designed a mathematical fallback vector hash generator within both Python and PHP so that even when external OpenRouter API limits or network drops occur, vector dot-product similarity (`<=>`) remains 100% functional and testable.
3. **Bespoke UI Tokenization**: Avoided default unstyled React Native boilerplate by generating custom glassmorphic cards (`#131822`), HSL gradient rings, and responsive spring animations (`Animated.sequence`) tailored specifically to the Guised Up brand identity.