<?php

namespace GlamByMariga\Shop;

use PDO;
use Exception;

/**
 * Cart Manager
 * Handles shopping cart operations and checkout processing
 */
class CartManager
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Add item to cart
     */
    public function addToCart($customerId, $productId, $quantity = 1, $variantId = null)
    {
        try {
            $this->db->beginTransaction();

            // Check if item already in cart
            $stmt = $this->db->prepare(
                "SELECT id, quantity FROM cart_items
                 WHERE customer_id = ? AND product_id = ? AND variant_id <=> ?"
            );
            $stmt->execute([$customerId, $productId, $variantId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update quantity
                $newQuantity = $existing['quantity'] + $quantity;
                $stmt = $this->db->prepare(
                    "UPDATE cart_items SET quantity = ?, expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
                     WHERE id = ?"
                );
                $stmt->execute([$newQuantity, $existing['id']]);
                $cartId = $existing['id'];
            } else {
                // Insert new item
                $stmt = $this->db->prepare(
                    "INSERT INTO cart_items
                    (customer_id, product_id, variant_id, quantity, expires_at)
                    VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))"
                );
                $stmt->execute([$customerId, $productId, $variantId, $quantity]);
                $cartId = $this->db->lastInsertId();
            }

            $this->db->commit();

            return [
                'success' => true,
                'cart_item_id' => $cartId,
                'message' => 'Item added to cart'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Add to cart error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItem($customerId, $cartItemId, $quantity)
    {
        try {
            if ($quantity <= 0) {
                return $this->removeFromCart($customerId, $cartItemId);
            }

            $stmt = $this->db->prepare(
                "UPDATE cart_items SET quantity = ?, expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
                 WHERE id = ? AND customer_id = ?"
            );
            $stmt->execute([$quantity, $cartItemId, $customerId]);

            return [
                'success' => true,
                'message' => 'Cart updated'
            ];

        } catch (Exception $e) {
            error_log('Update cart error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($customerId, $cartItemId)
    {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM cart_items WHERE id = ? AND customer_id = ?"
            );
            $stmt->execute([$cartItemId, $customerId]);

            return [
                'success' => true,
                'message' => 'Item removed from cart'
            ];

        } catch (Exception $e) {
            error_log('Remove from cart error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get cart items
     */
    public function getCart($customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT ci.*, p.name, p.price, p.image_url, pv.size, pv.color, pv.sku
                 FROM cart_items ci
                 JOIN products p ON ci.product_id = p.id
                 LEFT JOIN product_variants pv ON ci.variant_id = pv.id
                 WHERE ci.customer_id = ? AND (ci.expires_at IS NULL OR ci.expires_at > NOW())
                 ORDER BY ci.added_at DESC"
            );
            $stmt->execute([$customerId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'items' => $items,
                'count' => count($items),
                'total' => $this->calculateCartTotal($customerId, $items)
            ];

        } catch (Exception $e) {
            error_log('Get cart error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'items' => [],
                'count' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Calculate cart total
     */
    private function calculateCartTotal($customerId, $items = null)
    {
        try {
            if (!$items) {
                $stmt = $this->db->prepare(
                    "SELECT SUM(ci.quantity * p.price) as total
                     FROM cart_items ci
                     JOIN products p ON ci.product_id = p.id
                     WHERE ci.customer_id = ? AND (ci.expires_at IS NULL OR ci.expires_at > NOW())"
                );
                $stmt->execute([$customerId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['total'] ?? 0;
            } else {
                $total = 0;
                foreach ($items as $item) {
                    $price = $item['price_at_time'] ?? $item['price'] ?? 0;
                    $total += $price * $item['quantity'];
                }
                return $total;
            }

        } catch (Exception $e) {
            error_log('Calculate total error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear cart
     */
    public function clearCart($customerId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM cart_items WHERE customer_id = ?");
            $stmt->execute([$customerId]);

            return [
                'success' => true,
                'message' => 'Cart cleared'
            ];

        } catch (Exception $e) {
            error_log('Clear cart error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate coupon
     */
    public function validateCoupon($code, $subtotal, $cartItems = [])
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM coupons
                 WHERE code = ? AND is_active = TRUE
                 AND (start_date IS NULL OR start_date <= NOW())
                 AND (end_date IS NULL OR end_date > NOW())
                 AND (max_uses IS NULL OR current_uses < max_uses)"
            );
            $stmt->execute([$code]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$coupon) {
                return [
                    'success' => false,
                    'error' => 'Invalid or expired coupon code'
                ];
            }

            // Check minimum order amount
            if ($coupon['min_order_amount'] && $subtotal < $coupon['min_order_amount']) {
                return [
                    'success' => false,
                    'error' => 'Minimum order amount not met',
                    'min_amount' => $coupon['min_order_amount']
                ];
            }

            // Check applicable products/categories
            if ($coupon['applicable_products'] || $coupon['applicable_categories']) {
                if (!$this->isApplicable($coupon, $cartItems)) {
                    return [
                        'success' => false,
                        'error' => 'Coupon not applicable to items in cart'
                    ];
                }
            }

            // Calculate discount
            $discountAmount = 0;
            if ($coupon['discount_type'] === 'percentage') {
                $discountAmount = ($subtotal * $coupon['discount_value']) / 100;
            } else {
                $discountAmount = $coupon['discount_value'];
            }

            return [
                'success' => true,
                'coupon' => $coupon,
                'discount_amount' => $discountAmount
            ];

        } catch (Exception $e) {
            error_log('Validate coupon error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if coupon is applicable to cart items
     */
    private function isApplicable($coupon, $cartItems)
    {
        if (!$coupon['applicable_products'] && !$coupon['applicable_categories']) {
            return true;
        }

        $applicableProducts = [];
        $applicableCategories = [];

        if ($coupon['applicable_products']) {
            $applicableProducts = json_decode($coupon['applicable_products'], true) ?? [];
        }

        if ($coupon['applicable_categories']) {
            $applicableCategories = json_decode($coupon['applicable_categories'], true) ?? [];
        }

        foreach ($cartItems as $item) {
            if ($applicableProducts && in_array($item['product_id'], $applicableProducts)) {
                return true;
            }

            if ($applicableCategories && in_array($item['category_id'], $applicableCategories)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create order from cart
     */
    public function createOrder($customerId, $orderData, $couponCode = null)
    {
        try {
            $this->db->beginTransaction();

            // Get cart items
            $cartResult = $this->getCart($customerId);
            if (!$cartResult['success'] || empty($cartResult['items'])) {
                return [
                    'success' => false,
                    'error' => 'Cart is empty'
                ];
            }

            // Calculate totals
            $subtotal = $cartResult['total'];
            $discountAmount = 0;
            $shippingCost = $orderData['shipping_cost'] ?? 0;

            // Apply coupon if provided
            if ($couponCode) {
                $couponResult = $this->validateCoupon($couponCode, $subtotal, $cartResult['items']);
                if ($couponResult['success']) {
                    $discountAmount = $couponResult['discount_amount'];
                    // Update coupon usage
                    $stmt = $this->db->prepare(
                        "UPDATE coupons SET current_uses = current_uses + 1 WHERE code = ?"
                    );
                    $stmt->execute([$couponCode]);
                }
            }

            $total = $subtotal - $discountAmount + $shippingCost;

            // Create order
            $stmt = $this->db->prepare(
                "INSERT INTO orders
                (customer_id, status, subtotal_amount, discount_amount, shipping_cost, total_amount,
                 coupon_code, shipping_method, shipping_address, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $customerId,
                'pending',
                $subtotal,
                $discountAmount,
                $shippingCost,
                $total,
                $couponCode,
                $orderData['shipping_method'] ?? 'standard',
                json_encode($orderData['shipping_address'] ?? []),
                $orderData['notes'] ?? null
            ]);

            $orderId = $this->db->lastInsertId();

            // Add order items
            foreach ($cartResult['items'] as $item) {
                $stmt = $this->db->prepare(
                    "INSERT INTO order_items
                    (order_id, product_id, variant_id, quantity, price_per_item, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );
                $price = $item['price_at_time'] ?? $item['price'] ?? 0;
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['variant_id'],
                    $item['quantity'],
                    $price,
                    $price * $item['quantity']
                ]);

                // Update product sales count
                $stmt = $this->db->prepare(
                    "UPDATE products SET total_sales = total_sales + ? WHERE id = ?"
                );
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Clear cart
            $stmt = $this->db->prepare("DELETE FROM cart_items WHERE customer_id = ?");
            $stmt->execute([$customerId]);

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'order' => [
                    'id' => $orderId,
                    'subtotal' => $subtotal,
                    'discount' => $discountAmount,
                    'shipping' => $shippingCost,
                    'total' => $total
                ],
                'message' => 'Order created successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Create order error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order details
     */
    public function getOrder($orderId, $customerId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM orders WHERE id = ? AND customer_id = ?"
            );
            $stmt->execute([$orderId, $customerId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return [
                    'success' => false,
                    'error' => 'Order not found'
                ];
            }

            // Get order items
            $stmt = $this->db->prepare(
                "SELECT oi.*, p.name, p.image_url
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?"
            );
            $stmt->execute([$orderId]);
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'order' => $order
            ];

        } catch (Exception $e) {
            error_log('Get order error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer orders
     */
    public function getCustomerOrders($customerId, $limit = 10, $offset = 0)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, created_at, status, total_amount, payment_status
                 FROM orders
                 WHERE customer_id = ?
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$customerId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get orders error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get shipping options
     */
    public function getShippingOptions()
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM shipping_options WHERE is_active = TRUE ORDER BY sort_order"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Get shipping options error: ' . $e->getMessage());
            return [];
        }
    }
}
