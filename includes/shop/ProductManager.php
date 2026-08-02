<?php

namespace GlamByMariga\Shop;

use PDO;
use Exception;

/**
 * Product Manager
 * Handles product catalog, inventory, and related product operations
 */
class ProductManager
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get product with all details
     */
    public function getProduct($productId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*,
                        GROUP_CONCAT(DISTINCT pi.image_url) as images,
                        AVG(pr.rating) as avg_rating,
                        COUNT(DISTINCT pr.id) as total_reviews
                 FROM products p
                 LEFT JOIN product_images pi ON p.id = pi.product_id
                 LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 'approved'
                 WHERE p.id = ?
                 GROUP BY p.id
                 LIMIT 1"
            );
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                return null;
            }

            // Get variants
            $stmt = $this->db->prepare(
                "SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order, id"
            );
            $stmt->execute([$productId]);
            $product['variants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Parse images
            if ($product['images']) {
                $product['images'] = explode(',', $product['images']);
            } else {
                $product['images'] = [];
            }

            return $product;

        } catch (Exception $e) {
            error_log('Get product error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get products with filters and pagination
     */
    public function getProducts($filters = [], $limit = 20, $offset = 0)
    {
        try {
            $query = "SELECT p.* FROM products p WHERE p.is_active = TRUE";
            $params = [];

            // Category filter
            if (!empty($filters['category_id'])) {
                $query .= " AND p.category_id = ?";
                $params[] = $filters['category_id'];
            }

            // Search filter
            if (!empty($filters['search'])) {
                $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }

            // Price range filter
            if (!empty($filters['min_price'])) {
                $query .= " AND p.price >= ?";
                $params[] = $filters['min_price'];
            }
            if (!empty($filters['max_price'])) {
                $query .= " AND p.price <= ?";
                $params[] = $filters['max_price'];
            }

            // Featured filter
            if (!empty($filters['featured'])) {
                $query .= " AND p.is_featured = TRUE";
            }

            // Sort
            $sortMap = [
                'newest' => 'p.created_at DESC',
                'price_low' => 'p.price ASC',
                'price_high' => 'p.price DESC',
                'rating' => 'p.avg_rating DESC',
                'popular' => 'p.total_sales DESC'
            ];
            $sort = $sortMap[$filters['sort'] ?? 'newest'] ?? 'p.created_at DESC';
            $query .= " ORDER BY " . $sort;

            // Pagination
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get products error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get related products
     */
    public function getRelatedProducts($productId, $limit = 4)
    {
        try {
            // Get related by category
            $stmt = $this->db->prepare(
                "SELECT p.* FROM products p
                 WHERE p.category_id = (SELECT category_id FROM products WHERE id = ?)
                 AND p.id != ?
                 AND p.is_active = TRUE
                 LIMIT ?"
            );
            $stmt->execute([$productId, $productId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get related products error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get product reviews
     */
    public function getProductReviews($productId, $limit = 10, $offset = 0)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT pr.*, c.name as customer_name
                 FROM product_reviews pr
                 JOIN customers c ON pr.customer_id = c.id
                 WHERE pr.product_id = ? AND pr.status = 'approved'
                 ORDER BY pr.created_at DESC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$productId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get reviews error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Add product review
     */
    public function addReview($productId, $customerId, $rating, $title, $comment, $verifiedPurchase = false)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO product_reviews
                (product_id, customer_id, rating, title, comment, verified_purchase)
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$productId, $customerId, $rating, $title, $comment, $verifiedPurchase]);
            $reviewId = $this->db->lastInsertId();

            // Update product average rating
            $this->updateProductRating($productId);

            return [
                'success' => true,
                'review_id' => $reviewId,
                'message' => 'Review submitted successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update product average rating
     */
    private function updateProductRating($productId)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE products p
                 SET p.avg_rating = (
                     SELECT AVG(rating) FROM product_reviews
                     WHERE product_id = ? AND status = 'approved'
                 ),
                 p.total_reviews = (
                     SELECT COUNT(*) FROM product_reviews
                     WHERE product_id = ? AND status = 'approved'
                 )
                 WHERE p.id = ?"
            );
            $stmt->execute([$productId, $productId, $productId]);

        } catch (Exception $e) {
            error_log('Update rating error: ' . $e->getMessage());
        }
    }

    /**
     * Add to wishlist
     */
    public function addToWishlist($customerId, $productId)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO wishlist (customer_id, product_id)
                 VALUES (?, ?)"
            );
            $stmt->execute([$customerId, $productId]);

            return [
                'success' => true,
                'message' => 'Added to wishlist'
            ];

        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return [
                    'success' => false,
                    'error' => 'Already in your wishlist',
                    'error_code' => 'DUPLICATE'
                ];
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove from wishlist
     */
    public function removeFromWishlist($customerId, $productId)
    {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM wishlist WHERE customer_id = ? AND product_id = ?"
            );
            $stmt->execute([$customerId, $productId]);

            return [
                'success' => true,
                'message' => 'Removed from wishlist'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer wishlist
     */
    public function getWishlist($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.* FROM products p
                 JOIN wishlist w ON p.id = w.product_id
                 WHERE w.customer_id = ?
                 ORDER BY w.created_at DESC"
            );
            $stmt->execute([$customerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get wishlist error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get product stock
     */
    public function getProductStock($productId, $variantId = null)
    {
        try {
            if ($variantId) {
                $stmt = $this->db->prepare(
                    "SELECT stock FROM product_variants WHERE id = ? AND product_id = ?"
                );
                $stmt->execute([$variantId, $productId]);
            } else {
                $stmt = $this->db->prepare(
                    "SELECT SUM(stock) as total_stock FROM product_variants WHERE product_id = ?"
                );
                $stmt->execute([$productId]);
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['stock'] ?? $result['total_stock'] ?? 0;

        } catch (Exception $e) {
            error_log('Get stock error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check stock availability
     */
    public function isInStock($productId, $quantity = 1, $variantId = null)
    {
        $stock = $this->getProductStock($productId, $variantId);
        return $stock >= $quantity;
    }

    /**
     * Update stock
     */
    public function updateStock($productId, $variantId, $newStock, $reason, $referenceId = null, $userId = null)
    {
        try {
            // Get old stock
            $stmt = $this->db->prepare("SELECT stock FROM product_variants WHERE id = ?");
            $stmt->execute([$variantId]);
            $oldStock = $stmt->fetch(PDO::FETCH_ASSOC)['stock'] ?? 0;

            // Update stock
            $stmt = $this->db->prepare(
                "UPDATE product_variants SET stock = ? WHERE id = ? AND product_id = ?"
            );
            $stmt->execute([$newStock, $variantId, $productId]);

            // Log change
            $changeAmount = $newStock - $oldStock;
            $stmt = $this->db->prepare(
                "INSERT INTO stock_history
                (product_id, variant_id, old_stock, new_stock, change_amount, reason, reference_id, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$productId, $variantId, $oldStock, $newStock, $changeAmount, $reason, $referenceId, $userId]);

            // Check for low stock alert
            $this->checkLowStockAlert($productId, $variantId, $newStock);

            return [
                'success' => true,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change' => $changeAmount
            ];

        } catch (Exception $e) {
            error_log('Update stock error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check and create low stock alert
     */
    private function checkLowStockAlert($productId, $variantId, $currentStock)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT alert_threshold FROM low_stock_alerts
                 WHERE product_id = ? AND variant_id = ?"
            );
            $stmt->execute([$productId, $variantId]);
            $alert = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($alert && $currentStock <= $alert['alert_threshold'] && $currentStock > 0) {
                $stmt = $this->db->prepare(
                    "UPDATE low_stock_alerts
                     SET current_stock = ?, is_sent = FALSE
                     WHERE product_id = ? AND variant_id = ?"
                );
                $stmt->execute([$currentStock, $productId, $variantId]);
            }

        } catch (Exception $e) {
            error_log('Low stock alert error: ' . $e->getMessage());
        }
    }

    /**
     * Get featured products
     */
    public function getFeaturedProducts($section = 'homepage', $limit = 6)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.* FROM products p
                 JOIN featured_products fp ON p.id = fp.product_id
                 WHERE fp.section = ? AND (fp.featured_until IS NULL OR fp.featured_until > NOW())
                 AND p.is_active = TRUE
                 ORDER BY fp.sort_order, p.id
                 LIMIT ?"
            );
            $stmt->execute([$section, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get featured products error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search products
     */
    public function searchProducts($query, $limit = 20)
    {
        try {
            $search = '%' . $query . '%';
            $stmt = $this->db->prepare(
                "SELECT p.*,
                        MATCH(p.name, p.description) AGAINST(? IN BOOLEAN MODE) as relevance
                 FROM products p
                 WHERE p.is_active = TRUE
                 AND (p.name LIKE ? OR p.description LIKE ?)
                 ORDER BY relevance DESC, p.id DESC
                 LIMIT ?"
            );
            $stmt->execute([$query, $search, $search, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Search products error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get trending products
     */
    public function getTrendingProducts($days = 30, $limit = 6)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*, COUNT(oi.id) as sales_count
                 FROM products p
                 LEFT JOIN order_items oi ON p.id = oi.product_id
                 LEFT JOIN orders o ON oi.order_id = o.id
                 WHERE p.is_active = TRUE
                 AND (o.created_at IS NULL OR o.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY))
                 GROUP BY p.id
                 ORDER BY sales_count DESC, p.total_sales DESC
                 LIMIT ?"
            );
            $stmt->execute([$days, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get trending products error: ' . $e->getMessage());
            return [];
        }
    }
}
