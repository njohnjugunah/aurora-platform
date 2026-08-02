<?php
/**
 * Admin Dashboard Metrics
 * GET /ajax/admin/get-dashboard.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/admin/AdminAnalyticsService.php';

use GlamByMariga\Admin\AdminAnalyticsService;

try {
    // Check admin authorization
    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized'
        ]);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $analytics = new AdminAnalyticsService($db);

    $metrics = $analytics->getDashboardMetrics();
    $statusDist = $analytics->getOrderStatusDistribution();
    $customerAnalytics = $analytics->getCustomerAnalytics();
    $inventoryReport = $analytics->getInventoryReport();

    if ($metrics['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => [
                'metrics' => $metrics['metrics'],
                'order_status' => $statusDist,
                'customer_analytics' => $customerAnalytics['analytics'] ?? [],
                'inventory' => $inventoryReport['report'] ?? []
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode($metrics);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
