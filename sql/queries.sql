-- ============================================================================
-- GUISED UP - PART D: SQL CHALLENGE
-- Raw PostgreSQL queries designed and optimized for the Guised Up Database Schema
-- ============================================================================

-- ----------------------------------------------------------------------------
-- D1: Top 10 Most Active Users in the Last 7 Days
-- Returns the top 10 most active users in the last 7 days, ranked by total 
-- interactions (views + replies + reactions).
-- ----------------------------------------------------------------------------
SELECT 
    u.id AS user_id,
    u.name,
    u.email,
    u.avatar_url,
    COUNT(i.id) AS total_interactions,
    COUNT(CASE WHEN i.interaction_type = 'view' THEN 1 END) AS view_count,
    COUNT(CASE WHEN i.interaction_type = 'reply' THEN 1 END) AS reply_count,
    COUNT(CASE WHEN i.interaction_type = 'reaction' THEN 1 END) AS reaction_count
FROM users u
JOIN interactions i ON u.id = i.user_id
WHERE i.created_at >= NOW() - INTERVAL '7 days'
GROUP BY u.id, u.name, u.email, u.avatar_url
ORDER BY total_interactions DESC
LIMIT 10;


-- ----------------------------------------------------------------------------
-- D2: Personalized Feed Candidates by Relationship Depth
-- For a given user_id (e.g., :current_user_id), return all posts from users 
-- they interact with most, ordered by interaction frequency descending, 
-- limited to posts from the last 30 days.
-- ----------------------------------------------------------------------------
-- Note: Replace :current_user_id with the target user ID (e.g., 1)
WITH UserRelationshipScores AS (
    SELECT 
        p.author_id,
        COUNT(i.id) AS interaction_frequency
    FROM interactions i
    JOIN posts p ON i.post_id = p.id
    WHERE i.user_id = :current_user_id
      AND p.author_id != :current_user_id
      AND i.created_at >= NOW() - INTERVAL '30 days'
    GROUP BY p.author_id
)
SELECT 
    p.id AS post_id,
    p.author_id,
    u.name AS author_name,
    u.avatar_url AS author_avatar,
    p.content,
    p.image_url,
    p.authenticity_score,
    p.view_count,
    p.created_at,
    COALESCE(urs.interaction_frequency, 0) AS author_interaction_frequency
FROM posts p
JOIN users u ON p.author_id = u.id
JOIN UserRelationshipScores urs ON p.author_id = urs.author_id
WHERE p.created_at >= NOW() - INTERVAL '30 days'
ORDER BY urs.interaction_frequency DESC, p.created_at DESC;


-- ----------------------------------------------------------------------------
-- D3: High-View, Zero-Reaction Posts (Anomalies / Low-Resonance Content)
-- Find any posts that have been viewed more than 100 times but have zero 
-- reactions. Return post_id, author_id, view_count, and created_at.
-- ----------------------------------------------------------------------------
SELECT 
    p.id AS post_id,
    p.author_id,
    p.view_count,
    p.created_at
FROM posts p
WHERE p.view_count > 100
  AND NOT EXISTS (
      SELECT 1 
      FROM interactions i 
      WHERE i.post_id = p.id 
        AND i.interaction_type = 'reaction'
  )
ORDER BY p.view_count DESC;


-- ----------------------------------------------------------------------------
-- D4: Spam Detection Query
-- Write a query that would help detect potential spam — users who have created 
-- more than 20 posts in the last 24 hours. Include their email and post count.
-- ----------------------------------------------------------------------------
SELECT 
    u.id AS user_id,
    u.name,
    u.email,
    COUNT(p.id) AS post_count,
    MIN(p.created_at) AS first_post_in_window,
    MAX(p.created_at) AS latest_post_in_window
FROM users u
JOIN posts p ON u.id = p.author_id
WHERE p.created_at >= NOW() - INTERVAL '24 hours'
GROUP BY u.id, u.name, u.email
HAVING COUNT(p.id) > 20
ORDER BY post_count DESC;
