<?php
/**
 * Admin Get Analytics
 * GET /ajax/admin/get-analytics.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/admin/AdminAnalyticsService.php';

use GlamByMariga\Admin\AdminAnalyticsService;

try {
    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $analyticsService = new AdminAnalyticsService($db);

    $type = $_GET['type'] ?? 'sales';
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $days = (int)($_GET['days'] ?? 30);

    $data = [];

    if ($type === 'sales') {
        $report = $analyticsService->getSalesReport($startDate, $endDate);
        $data = $report['report'] ?? [];

    } elseif ($type === 'products') {
        $data = [
            'top_products' => $analyticsService->getTopProducts($days, $limit)
        ];

    } elseif ($type === 'customers') {
        $data = [
            'top_customers' => $analyticsService->getTopCustomers($limit),
            'customer_analytics' => $analyticsService->getCustomerAnalytics()
        ];

    } elseif ($type === 'payment_methods') {
        $report = $analyticsService->getPaymentMethodsReport($startDate, $endDate);
        $data = [
            'payment_methods' => $report
        ];

    } elseif ($type === 'status_distribution') {
        $data = [
            'order_status' => $analyticsService->getOrderStatusDistribution()
        ];

    } elseif ($type === 'inventory') {
        $report = $analyticsService->getInventoryReport();
        $data = $report['report'] ?? [];

    } elseif ($type === 'coupons') {
        $data = [
            'coupons' => $analyticsService->getCouponEffectiveness()
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
