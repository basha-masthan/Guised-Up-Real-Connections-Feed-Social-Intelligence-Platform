from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from typing import List, Dict, Any

from models import (
    EmbedRequest, EmbedResponse,
    RankRequest, RankResponse,
    SearchRequest, SearchResponse, SearchResultItem
)
from embedding import generate_embedding
from ranker import rank_posts
from database import get_candidate_posts_from_db, get_user_relationship_scores_from_db, search_posts_in_db

app = FastAPI(
    title="Guised Up ML & Vector Intelligence Service",
    description="Python FastAPI Microservice for 1536-d Vector Embeddings, Hybrid Multi-Factor Feed Ranking, and Semantic Search",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/health")
def health_check():
    return {
        "status": "healthy",
        "service": "Guised Up ML Service",
        "version": "1.0.0"
    }

@app.post("/embed", response_model=EmbedResponse)
def create_embedding(req: EmbedRequest):
    if not req.text or not req.text.strip():
        raise HTTPException(status_code=400, detail="Text cannot be empty.")
        
    vec, model_used = generate_embedding(req.text, model=req.model or "text-embedding-3-small")
    return EmbedResponse(
        success=True,
        embedding=vec,
        dimensions=len(vec),
        model_used=model_used
    )

@app.post("/rank", response_model=RankResponse)
def compute_ranking(req: RankRequest):
    try:
        candidates = req.candidates
        if not candidates:
            candidates = get_candidate_posts_from_db(req.user_id)
            
        rel_scores = get_user_relationship_scores_from_db(req.user_id)
        
        # If user has an interest profile vector, we can compute it from interactions
        # For candidate list or fallback, we pass `user_profile_vector=None` or compute average
        ranked_ids, scores_dict = rank_posts(
            user_id=req.user_id,
            candidates=candidates,
            user_profile_vector=None,
            relationship_scores=rel_scores,
            page=req.page,
            per_page=req.per_page
        )
        
        return RankResponse(
            success=True,
            data=ranked_ids,
            scores=scores_dict
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/search", response_model=SearchResponse)
def perform_semantic_search(req: SearchRequest):
    if not req.query or not req.query.strip():
        raise HTTPException(status_code=400, detail="Search query cannot be empty.")
        
    query_vec, _ = generate_embedding(req.query)
    results = search_posts_in_db(query_vec, limit=req.limit)
    
    return SearchResponse(
        success=True,
        data=results
    )
