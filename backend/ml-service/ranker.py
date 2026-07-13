import math
from datetime import datetime, timezone
from typing import List, Dict, Any, Optional, Tuple
from models import PostCandidate, ScoredPost

# Type alias must be defined BEFORE use in function signature
Tuple_output = Tuple[List[int], Dict[int, ScoredPost]]

def compute_cosine_similarity(vec_a: List[float], vec_b: List[float]) -> float:
    if not vec_a or not vec_b or len(vec_a) != len(vec_b):
        return 0.0
    dot_product = sum(a * b for a, b in zip(vec_a, vec_b))
    norm_a = math.sqrt(sum(a * a for a in vec_a))
    norm_b = math.sqrt(sum(b * b for b in vec_b))
    if norm_a == 0 or norm_b == 0:
        return 0.0
    return dot_product / (norm_a * norm_b)

def rank_posts(
    user_id: int,
    candidates: List[PostCandidate],
    user_profile_vector: Optional[List[float]] = None,
    relationship_scores: Optional[Dict[int, float]] = None,
    page: int = 1,
    per_page: int = 20
) -> Tuple_output:
    """
    Ranks candidate posts using the multi-factor Guised Up ranking formula:
    FinalScore = (0.30 * S_auth + 0.40 * S_rel + 0.30 * S_sem) * S_time
    """
    if not relationship_scores:
        relationship_scores = {}

    # Normalize relationship scores via log scaling
    rel_values = list(relationship_scores.values())
    max_rel = max(rel_values + [1.0]) if rel_values else 1.0

    now = datetime.now(timezone.utc)
    half_life_hours = 36.0
    lambda_decay = 0.693147 / half_life_hours  # ln(2) / 36.0

    scored_items: List[ScoredPost] = []

    for candidate in candidates:
        # 1. Authenticity Score (Normalized 0 to 1)
        s_auth = min(max(candidate.authenticity_score / 2.0, 0.0), 1.0)

        # 2. Relationship Depth Score (0 to 1)
        raw_rel = relationship_scores.get(candidate.author_id, 0.0)
        s_rel = math.log(1.0 + raw_rel) / math.log(1.0 + max_rel) if max_rel > 0 else 0.0

        # 3. Semantic Similarity Score (0 to 1)
        if user_profile_vector and candidate.embedding:
            cosine_sim = compute_cosine_similarity(user_profile_vector, candidate.embedding)
            s_sem = max(0.0, cosine_sim)
        else:
            s_sem = 0.5  # Neutral baseline if vectors not present

        # 4. Time Decay Factor
        post_time = candidate.created_at
        if post_time.tzinfo is None:
            post_time = post_time.replace(tzinfo=timezone.utc)

        hours_elapsed = max(0.0, (now - post_time).total_seconds() / 3600.0)
        s_time = math.exp(-lambda_decay * hours_elapsed)

        # Composite Weighted Score
        base_score = (0.30 * s_auth) + (0.40 * s_rel) + (0.30 * s_sem)
        final_score = base_score * s_time

        scored_items.append(ScoredPost(
            post_id=candidate.id,
            final_score=round(final_score, 6),
            breakdown={
                "authenticity": round(s_auth, 4),
                "relationship": round(s_rel, 4),
                "semantic": round(s_sem, 4),
                "time_decay": round(s_time, 4),
                "base_score": round(base_score, 4)
            }
        ))

    # Sort descending by final_score
    scored_items.sort(key=lambda x: x.final_score, reverse=True)

    # Paginate
    offset = (page - 1) * per_page
    paginated = scored_items[offset : offset + per_page]

    ranked_ids = [item.post_id for item in paginated]
    scores_dict = {item.post_id: item for item in paginated}

    return ranked_ids, scores_dict
