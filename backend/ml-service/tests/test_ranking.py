import pytest
from datetime import datetime, timezone, timedelta
from models import PostCandidate
from ranker import rank_posts, compute_cosine_similarity
from embedding import generate_deterministic_hash_embedding

def test_authenticity_score_ranking():
    """
    Test that higher authenticity signals rank above lower authenticity signals
    when all other factors are equal.
    """
    now = datetime.now(timezone.utc)
    candidates = [
        PostCandidate(id=1, author_id=10, content="Polished promotional post", authenticity_score=0.5, created_at=now),
        PostCandidate(id=2, author_id=11, content="Raw unedited thought on burnout", authenticity_score=1.8, created_at=now),
    ]
    ranked_ids, scores = rank_posts(user_id=1, candidates=candidates)
    
    assert ranked_ids[0] == 2
    assert scores[2].final_score > scores[1].final_score

def test_relationship_depth_ranking():
    """
    Test that posts from authors with higher relationship depth rank higher.
    """
    now = datetime.now(timezone.utc)
    candidates = [
        PostCandidate(id=10, author_id=100, content="Post from stranger", authenticity_score=1.0, created_at=now),
        PostCandidate(id=20, author_id=200, content="Post from close friend", authenticity_score=1.0, created_at=now),
    ]
    rel_scores = {200: 15.0, 100: 0.0}
    ranked_ids, scores = rank_posts(user_id=1, candidates=candidates, relationship_scores=rel_scores)
    
    assert ranked_ids[0] == 20
    assert scores[20].breakdown["relationship"] > scores[10].breakdown["relationship"]

def test_time_decay_ranking():
    """
    Test that newer posts receive a higher time decay score than older posts.
    """
    now = datetime.now(timezone.utc)
    candidates = [
        PostCandidate(id=100, author_id=5, content="Fresh post", authenticity_score=1.0, created_at=now - timedelta(hours=1)),
        PostCandidate(id=200, author_id=6, content="Old post from 3 days ago", authenticity_score=1.0, created_at=now - timedelta(hours=72)),
    ]
    ranked_ids, scores = rank_posts(user_id=1, candidates=candidates)
    
    assert ranked_ids[0] == 100
    assert scores[100].breakdown["time_decay"] > scores[200].breakdown["time_decay"]

def test_cosine_similarity():
    """
    Test unit vector cosine similarity math.
    """
    vec_a = [1.0, 0.0, 0.0]
    vec_b = [1.0, 0.0, 0.0]
    vec_c = [0.0, 1.0, 0.0]
    
    assert compute_cosine_similarity(vec_a, vec_b) == 1.0
    assert compute_cosine_similarity(vec_a, vec_c) == 0.0

def test_deterministic_hash_embedding():
    """
    Test that deterministic hash embedding generates consistent 1536-dimensional unit vectors.
    """
    vec1 = generate_deterministic_hash_embedding("Real conversations hit different")
    vec2 = generate_deterministic_hash_embedding("Real conversations hit different")
    assert len(vec1) == 1536
    assert vec1 == vec2
