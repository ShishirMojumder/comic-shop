CREATE VIEW product_rating_summary AS
SELECT
    product_id,
    AVG(rating) AS average_rating,
    COUNT(review_id) AS total_reviews
FROM Reviews
GROUP BY product_id;
