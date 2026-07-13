import os
import json
from datetime import datetime, timezone
from typing import List, Dict, Any, Optional
from models import PostCandidate, SearchResultItem
from ranker import compute_cosine_similarity

DATABASE_URL = os.environ.get(
    "DATABASE_URL", 
    "postgresql://neondb_owner:npg_5VS0xBqGUPgE@ep-raspy-truth-atmgit1k-pooler.c-9.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
)

# Optional SQLAlchemy / psycopg2 pool if DB is online
engine = None
try:
    from sqlalchemy import create_engine, text
    engine = create_engine(DATABASE_URL, pool_size=5, max_overflow=10, pool_pre_ping=True, pool_timeout=3)
except Exception:
    engine = None

def get_candidate_posts_from_db(user_id: int) -> List[PostCandidate]:
    """
    Fetches candidate posts from PostgreSQL DB (`posts` table).
    Falls back gracefully if DB is unavailable or table not yet migrated.
    """
    if engine:
        try:
            with engine.connect() as conn:
                query = text("""
                    SELECT id, author_id, content, authenticity_score, created_at, embedding
                    FROM posts
                    WHERE author_id != :uid AND created_at >= NOW() - INTERVAL '14 days'
                """)
                rows = conn.execute(query, {"uid": user_id}).fetchall()
                candidates = []
                for r in rows:
                    emb = None
                    if r.embedding is not None:
                        if isinstance(r.embedding, str):
                            try:
                                emb = json.loads(r.embedding)
                            except Exception:
                                emb = [float(x) for x in r.embedding.strip('[]').split(',')]
                        elif isinstance(r.embedding, (list, tuple)):
                            emb = list(r.embedding)
                    candidates.append(PostCandidate(
                        id=r.id,
                        author_id=r.author_id,
                        content=r.content,
                        authenticity_score=float(r.authenticity_score or 1.0),
                        created_at=r.created_at if r.created_at else datetime.now(timezone.utc),
                        embedding=emb
                    ))
                return candidates
        except Exception as e:
            pass
    return []

def get_user_relationship_scores_from_db(user_id: int) -> Dict[int, float]:
    """
    Fetches aggregated relationship depth scores for a given user against all authors.
    """
    if engine:
        try:
            with engine.connect() as conn:
                query = text("""
                    SELECT p.author_id, 
                           SUM(CASE WHEN i.interaction_type = 'reply' THEN 5.0
                                    WHEN i.interaction_type = 'reaction' THEN 3.0
                                    WHEN i.interaction_type = 'view' THEN 1.0 ELSE 0 END) as raw_score
                    FROM interactions i
                    JOIN posts p ON i.post_id = p.id
                    WHERE i.user_id = :uid AND i.created_at >= NOW() - INTERVAL '30 days'
                    GROUP BY p.author_id
                """)
                rows = conn.execute(query, {"uid": user_id}).fetchall()
                return {int(r.author_id): float(r.raw_score) for r in rows}
        except Exception as e:
            pass
    return {}

def search_posts_in_db(query_embedding: List[float], limit: int = 10) -> List[SearchResultItem]:
    """
    Performs vector cosine similarity search.
    First tries native pgvector `<=>` operator if PostgreSQL vector extension is active.
    Otherwise computes exact cosine similarity across all stored vectors in python.
    """
    if engine:
        try:
            with engine.connect() as conn:
                # Check if pgvector is enabled and embedding column is vector type
                try:
                    query = text("""
                        SELECT id, 1 - (embedding <=> :emb) as sim
                        FROM posts
                        WHERE embedding IS NOT NULL
                        ORDER BY embedding <=> :emb
                        LIMIT :lim
                    """)
                    rows = conn.execute(query, {"emb": str(query_embedding), "lim": limit}).fetchall()
                    return [SearchResultItem(post_id=r.id, similarity_score=round(float(r.sim), 4)) for r in rows]
                except Exception:
                    # Fallback if pgvector operator <=> isn't available or stored as json text
                    query = text("SELECT id, embedding FROM posts WHERE embedding IS NOT NULL")
                    rows = conn.execute(query).fetchall()
                    scored = []
                    for r in rows:
                        emb = None
                        if isinstance(r.embedding, str):
                            try:
                                emb = json.loads(r.embedding)
                            except Exception:
                                emb = [float(x) for x in r.embedding.strip('[]').split(',')]
                        elif isinstance(r.embedding, (list, tuple)):
                            emb = list(r.embedding)
                        if emb:
                            sim = compute_cosine_similarity(query_embedding, emb)
                            scored.append(SearchResultItem(post_id=r.id, similarity_score=round(sim, 4)))
                    scored.sort(key=lambda x: x.similarity_score, reverse=True)
                    return scored[:limit]
        except Exception as e:
            pass
    return []
