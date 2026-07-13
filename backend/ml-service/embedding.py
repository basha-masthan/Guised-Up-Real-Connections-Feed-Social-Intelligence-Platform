import os
import math
import hashlib
import httpx
from typing import List, Tuple

OPENROUTER_API_KEY = os.environ.get("OPENROUTER_API_KEY", "")
OPENROUTER_URL = "https://openrouter.ai/api/v1/embeddings"

def generate_deterministic_hash_embedding(text: str, dimensions: int = 1536) -> List[float]:
    """
    Generates a deterministic, unit-length 1536-dimensional embedding vector from text.
    Simulates semantic clustering by hashing character n-grams and common topic keywords.
    Ensures zero-downtime offline operation or fallback when API credits are exhausted.
    """
    cleaned = text.lower().strip()
    vector = [0.0] * dimensions
    
    # Keyword semantic boosters for consistent clustering during testing & demo
    keywords = {
        "coffee": 0, "friend": 10, "conversation": 20, "authentic": 30, "phone": 40,
        "burnout": 50, "work": 60, "startup": 70, "filter": 80, "real": 90,
        "travel": 100, "jaipur": 110, "lost": 120, "chai": 130, "funny": 140,
        "music": 150, "night": 160, "weekend": 170, "peace": 180, "happy": 190
    }
    
    for word, offset in keywords.items():
        if word in cleaned:
            for i in range(10):
                vector[(offset + i) % dimensions] += 0.5
                
    # Hash n-grams for general text variance
    words = cleaned.split()
    for w in words:
        h = int(hashlib.md5(w.encode('utf-8')).hexdigest(), 16)
        idx = h % dimensions
        sign = 1.0 if (h % 2 == 0) else -1.0
        vector[idx] += sign * 0.1
        
    # L2 Normalization (Cosine unit sphere)
    norm = math.sqrt(sum(x * x for x in vector))
    if norm == 0:
        vector[0] = 1.0
        norm = 1.0
        
    return [round(x / norm, 6) for x in vector]

def generate_embedding(text: str, model: str = "text-embedding-3-small") -> Tuple[List[float], str]:
    """
    Attempts to generate vector embedding via OpenRouter API.
    If OpenRouter fails or is unavailable, falls back to deterministic hash embedding.
    """
    if OPENROUTER_API_KEY and not OPENROUTER_API_KEY.startswith("mock_"):
        try:
            headers = {
                "Authorization": f"Bearer {OPENROUTER_API_KEY}",
                "Content-Type": "application/json"
            }
            payload = {
                "input": text,
                "model": model
            }
            with httpx.Client(timeout=5.0) as client:
                resp = client.post(OPENROUTER_URL, headers=headers, json=payload)
                if resp.status_code == 200:
                    data = resp.json()
                    if "data" in data and len(data["data"]) > 0:
                        embedding = data["data"][0]["embedding"]
                        return embedding, model
        except Exception as e:
            # Fall back silently/gracefully to deterministic hash embedding
            pass
            
    fallback_vec = generate_deterministic_hash_embedding(text, dimensions=1536)
    return fallback_vec, "mock-deterministic-hash-1536"
