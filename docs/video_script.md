# Guised Up - Video Presentation Script

This is a complete, step-by-step script for your recorded video. You can read it directly like a teleprompter or use it as detailed talking points while you share your screen. 

**Pro-tip for recording:** Have your iOS/Android emulator, your IDE (with a large font), and your database client open in different virtual desktops or tabs so you can switch between them smoothly.

---

## 1. Introduction (0:00 - 0:30)
**[Visual: Show the Mobile App running on your emulator, on the Main Feed screen]**

"Hi! I'm excited to walk you through my submission for Guised Up — a social platform designed for real connections rather than just engagement farming. 

Instead of ranking posts by traditional metrics like likes or shares, this application ranks content based on authenticity, relationship depth, and semantic relevance. 

This is a full-stack implementation featuring a React Native mobile frontend, a Laravel PHP API gateway, and a Python FastAPI machine learning service, all backed by PostgreSQL with the `pgvector` extension."

## 2. Architecture Overview & AI Workflow (0:30 - 1:00)
**[Visual: Switch to your Technical Solution Document (TSD) showing the architecture diagram]**

"Before diving into the app, let's briefly look at the architecture. 
- **The Mobile App** handles the UI and user interactions.
- **The Laravel API** acts as the central gateway. It manages authentication using Sanctum, handles CRUD operations, and logs interactions.
- **The Python ML Service** handles the heavy lifting. It generates vector embeddings and runs the core multi-factor ranking algorithm.
- **PostgreSQL** stores everything, including 1536-dimensional vectors using `pgvector`.

I also want to mention that I built this rapidly using an AI-augmented workflow. By leveraging AI agentic tools like Cursor and Claude, I was able to scaffold the boilerplate instantly, optimize the `pgvector` queries, and focus my time on the complex ranking logic and system architecture."

## 3. Mobile App — The Main Feed (1:00 - 1:45)
**[Visual: Switch back to the Mobile App emulator]**

"Let's look at the product. This is the Real Connections Feed. 
As you can see, the UI is intentional and modern, focusing on the content itself rather than metrics. 

Each card displays the post, the author, and an authenticity badge. 
*(Scroll down slowly to demonstrate infinite scrolling)*
The feed is paginated and implements infinite scrolling to pull the next set of posts seamlessly from the backend. 

*(Tap the reaction button on a post)*
When I react to a post, the app logs this interaction via the Laravel API. This is critical because our ranking algorithm heavily weights relationship depth. By reacting to this user's post, I am signaling a genuine interaction, which will cause their future content to rank higher in my feed."

## 4. Semantic Search (1:45 - 2:15)
**[Visual: Scroll to the top and click on the search bar in the app]**

"The app also supports natural language search. Let's say I'm looking for a specific topic. 
*(Type "funny travel stories" into the search bar and hit enter)*

When I search this, it doesn't just do a keyword match. The query is sent to our Python service, converted into a vector embedding, and compared against all post embeddings in PostgreSQL using a cosine similarity search. 

As you can see, it returns semantically relevant posts that match the *intent* of my search, even if the exact keywords aren't present."

## 5. Backend — Laravel API & DB Schema (2:15 - 3:00)
**[Visual: Open your IDE (e.g., VS Code/Cursor) to `routes/api.php` and then your database schema/migrations]**

"Let's jump into the code. 
Here in Laravel, we have a clean API layer. The endpoints handle post creation, fetching the personalized feed, semantic search, and logging interactions.

If we look at the database schema *(open migration files)*, we have three core tables: `users`, `posts`, and `interactions`. 
Notice that both `users` and `posts` have a vector column. We use `pgvector` for this, which allows us to natively store and query high-dimensional embeddings right alongside our relational data, removing the need for a separate vector database and simplifying the architecture."

## 6. The Python ML Service & Ranking Algorithm (3:00 - 3:45)
**[Visual: Switch to the Python backend directory, specifically `ranker.py` and `embedding.py` if you have them]**

"The true brain of the application is the Python ML Service. 
When Laravel requests the feed, Python executes our custom ranking algorithm. 

The score is a composite of four pillars:
1. **Authenticity Signals**: Higher scores for genuine, less-polished content.
2. **Relationship Depth**: We analyze the `interactions` table. Users you've genuinely interacted with get a significant boost.
3. **Semantic Similarity**: We compare the user's interest embedding with the post's embedding.
4. **Time Decay**: We apply a time decay function so newer content surfaces, but not at the expense of highly relevant older content.

For the embeddings themselves, we utilize an open model *(mention the model you used, e.g., OpenAI or sentence-transformers)*."

## 7. SQL Analytics & Testing (3:45 - 4:15)
**[Visual: Open the `sql/queries.sql` file, then briefly open a test file like `GuisedUpApiTest.php`]**

"As part of the requirements, I've also included the raw SQL analytics queries in the `sql` folder. These handle complex aggregations like finding the top active users, calculating interaction frequencies using CTEs, and detecting potential spam.

Finally, reliability is key, so I've written automated tests. *(Show the terminal and quickly run the Laravel or Python tests)*. We have feature tests in Laravel ensuring the API contracts hold, and unit tests in Python verifying that the mathematical ranking logic works exactly as intended."

## 8. Wrap-up (4:15 - 4:30)
**[Visual: Bring up the App emulator one last time]**

"To summarize, this is a complete, scalable solution built for Guised Up. It successfully integrates a React Native frontend with a dual-backend architecture and vector search, perfectly aligning with the goal of prioritizing real, authentic connections. 

All the code, the detailed Technical Solution Document, and setup instructions are in the GitHub repo. Thank you for your time!"
