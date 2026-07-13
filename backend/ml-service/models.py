from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
from datetime import datetime

class EmbedRequest(BaseModel):
    text: str = Field(..., description="Text content to embed")
    model: Optional[str] = Field("text-embedding-3-small", description="Model name for OpenRouter or OpenAI")

class EmbedResponse(BaseModel):
    success: bool
    embedding: List[float]
    dimensions: int
    model_used: str

class PostCandidate(BaseModel):
    id: int
    author_id: int
    content: str
    authenticity_score: float = 1.0
    created_at: datetime
    embedding: Optional[List[float]] = None

class RankRequest(BaseModel):
    user_id: int
    page: int = 1
    per_page: int = 20
    candidates: Optional[List[PostCandidate]] = None  # If passed directly, rank these; else fetch from DB

class ScoredPost(BaseModel):
    post_id: int
    final_score: float
    breakdown: Dict[str, float]

class RankResponse(BaseModel):
    success: bool
    data: List[int]  # List of ranked post IDs
    scores: Optional[Dict[int, ScoredPost]] = None

class SearchRequest(BaseModel):
    query: str = Field(..., description="Natural language search query")
    limit: int = Field(10, description="Maximum number of results to return")

class SearchResultItem(BaseModel):
    post_id: int
    similarity_score: float

class SearchResponse(BaseModel):
    success: bool
    data: List[SearchResultItem]
