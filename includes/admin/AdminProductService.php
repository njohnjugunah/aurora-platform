<?php

namespace GlamByMariga\Admin;

use PDO;
use Exception;

/**
 * Admin Product Service
 * Handles product management operations for admin panel
 */
class AdminProductService
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all products with pagination
     */
    public function getAllProducts($limit = 20, $offset = 0, $filters = [])
    {
        try {
            $query = "SELECT p.*, c.name as category_name, COUNT(DISTINCT pr.id) as review_count
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     LEFT JOIN product_reviews pr ON p.id = pr.product_id
                     WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $query .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }

            if (!empty($filters['category_id'])) {
                $query .= " AND p.category_id = ?";
                $params[] = $filters['category_id'];
            }

            if (!empty($filters['status'])) {
                $query .= " AND p.is_active = ?";
                $params[] = $filters['status'] === 'active' ? 1 : 0;
            }

            $query .= " GROUP BY p.id ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
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
     * Get product count for pagination
     */
    public function getProductCount($filters = [])
    {
        try {
            $query = "SELECT COUNT(*) as total FROM products WHERE 1=1";
            $params = [];

            if (!empty($filters['search'])) {
                $query .= " AND (name LIKE ? OR sku LIKE ?)";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }

            if (!empty($filters['category_id'])) {
                $query .= " AND category_id = ?";
                $params[] = $filters['category_id'];
            }

            if (!empty($filters['status'])) {
                $query .= " AND is_active = ?";
                $params[] = $filters['status'] === 'active' ? 1 : 0;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;

        } catch (Exception $e) {
            error_log('Get product count error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create product
     */
    public function createProduct($data)
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO products
                (category_id, name, description, price, sku, image_url, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['category_id'] ?? null,
                $data['name'],
                $data['description'] ?? null,
                $data['price'],
                $data['sku'],
                $data['image_url'] ?? null,
                $data['is_active'] ?? 1,
                $data['created_by'] ?? 1
            ]);

            $productId = $this->db->lastInsertId();
            $this->db->commit();

            return [
                'success' => true,
                'product_id' => $productId,
                'message' => 'Product created successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Create product error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update product
     */
    public function updateProduct($productId, $data)
    {
        try {
            $updates = [];
            $params = [];

            if (isset($data['name'])) {
                $updates[] = "name = ?";
                $params[] = $data['name'];
            }
            if (isset($data['description'])) {
                $updates[] = "description = ?";
                $params[] = $data['description'];
            }
            if (isset($data['price'])) {
                $updates[] = "price = ?";
                $params[] = $data['price'];
            }
            if (isset($data['sku'])) {
                $updates[] = "sku = ?";
                $params[] = $data['sku'];
            }
            if (isset($data['category_id'])) {
                $updates[] = "category_id = ?";
                $params[] = $data['category_id'];
            }
            if (isset($data['image_url'])) {
                $updates[] = "image_url = ?";
                $params[] = $data['image_url'];
            }
            if (isset($data['is_active'])) {
                $updates[] = "is_active = ?";
                $params[] = $data['is_active'];
            }
            if (isset($data['is_featured'])) {
                $updates[] = "is_featured = ?";
                $params[] = $data['is_featured'];
            }

            if (empty($updates)) {
                return [
                    'success' => false,
                    'error' => 'No fields to update'
                ];
            }

            $updates[] = "updated_at = NOW()";
            $params[] = $productId;

            $query = "UPDATE products SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => 'Product updated successfully'
            ];

        } catch (Exception $e) {
            error_log('Update product error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete product
     */
    public function deleteProduct($productId)
    {
        try {
            $this->db->beginTransaction();

            // Delete related records
            $stmt = $this->db->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt->execute([$productId]);

            $stmt = $this->db->prepare("DELETE FROM product_variants WHERE product_id = ?");
            $stmt->execute([$productId]);

            $stmt = $this->db->prepare("DELETE FROM product_reviews WHERE product_id = ?");
            $stmt->execute([$productId]);

            $stmt = $this->db->prepare("DELETE FROM wishlist WHERE product_id = ?");
            $stmt->execute([$productId]);

            // Delete product
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Product deleted successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Delete product error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk update product status
     */
    public function bulkUpdateStatus($productIds, $isActive)
    {
        try {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $params = array_merge([$isActive], $productIds);

            $stmt = $this->db->prepare(
                "UPDATE products SET is_active = ? WHERE id IN ($placeholders)"
            );
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => 'Products updated successfully'
            ];

        } catch (Exception $e) {
            error_log('Bulk update error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Manage product images
     */
    public function addProductImage($productId, $imageUrl, $altText, $isPrimary = false)
    {
        try {
            if ($isPrimary) {
                $stmt = $this->db->prepare(
                    "UPDATE product_images SET is_primary = FALSE WHERE product_id = ?"
                );
                $stmt->execute([$productId]);
            }

            $stmt = $this->db->prepare(
                "INSERT INTO product_images (product_id, image_url, alt_text, is_primary)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$productId, $imageUrl, $altText, $isPrimary ? 1 : 0]);

            return [
                'success' => true,
                'image_id' => $this->db->lastInsertId()
            ];

        } catch (Exception $e) {
            error_log('Add image error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove product image
     */
    public function removeProductImage($imageId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM product_images WHERE id = ?");
            $stmt->execute([$imageId]);

            return [
                'success' => true,
                'message' => 'Image removed'
            ];

        } catch (Exception $e) {
            error_log('Remove image error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Manage variants
     */
    public function addVariant($productId, $data)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO product_variants
                (product_id, sku, barcode, size, color, stock, price)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $productId,
                $data['sku'],
                $data['barcode'] ?? null,
                $data['size'] ?? null,
                $data['color'] ?? null,
                $data['stock'] ?? 0,
                $data['price'] ?? null
            ]);

            return [
                'success' => true,
                'variant_id' => $this->db->lastInsertId()
            ];

        } catch (Exception $e) {
            error_log('Add variant error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update variant stock
     */
    public function updateVariantStock($variantId, $newStock, $reason)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT stock FROM product_variants WHERE id = ?"
            );
            $stmt->execute([$variantId]);
            $oldStock = $stmt->fetch(PDO::FETCH_ASSOC)['stock'] ?? 0;

            $stmt = $this->db->prepare(
                "UPDATE product_variants SET stock = ? WHERE id = ?"
            );
            $stmt->execute([$newStock, $variantId]);

            return [
                'success' => true,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'change' => $newStock - $oldStock
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
     * Get low stock products
     */
    public function getLowStockProducts($threshold = 10)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT pv.*, p.name, p.sku
                 FROM product_variants pv
                 JOIN products p ON pv.product_id = p.id
                 WHERE pv.stock <= ?
                 ORDER BY pv.stock ASC"
            );
            $stmt->execute([$threshold]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get low stock error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get product performance
     */
    public function getProductPerformance($limit = 20)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*,
                        COUNT(DISTINCT pr.id) as total_reviews,
                        AVG(pr.rating) as avg_rating,
                        SUM(oi.quantity) as units_sold,
                        SUM(oi.subtotal) as revenue
                 FROM products p
                 LEFT JOIN product_reviews pr ON p.id = pr.product_id
                 LEFT JOIN order_items oi ON p.id = oi.product_id
                 GROUP BY p.id
                 ORDER BY revenue DESC
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get performance error: ' . $e->getMessage());
            return [];
        }
    }
}
