<?php
/**
 * Customer Lifecycle API
 * POST/GET /ajax/communication/lifecycle.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/communication/LifecycleService.php';

use GlamByMariga\Communication\LifecycleService;

try {
    $adminId = $_SESSION['admin_id'] ?? null;
    $customerId = $_SESSION['customer_id'] ?? $_GET['customer_id'] ?? null;

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $lifecycleService = new LifecycleService($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'distribution';

        if ($action === 'distribution' && $adminId) {
            $distribution = $lifecycleService->getStageDistribution();
            http_response_code(200);
            echo json_encode(['success' => true, 'distribution' => $distribution]);

        } elseif ($action === 'by_stage' && $adminId) {
            $stage = $_GET['stage'] ?? 'active';
            $limit = $_GET['limit'] ?? 100;
            $customers = $lifecycleService->getCustomersByStage($stage, $limit);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'stage' => $stage,
                'customers' => $customers,
                'count' => count($customers)
            ]);

        } elseif ($action === 'progression' && $customerId) {
            $result = $lifecycleService->getStageProgression($customerId);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'health_metrics' && $adminId) {
            $metrics = $lifecycleService->getStageHealthMetrics();
            http_response_code(200);
            echo json_encode(['success' => true, 'metrics' => $metrics]);

        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $data['action'] ?? null;

        switch ($action) {
            case 'initialize_stages':
                if (!$adminId) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'error' => 'Admin authorization required']);
                    exit;
                }
                $result = $lifecycleService->initializeStageDefinitions();
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'calculate_stage':
                if (!$adminId && !$customerId) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'error' => 'Authorization required']);
                    exit;
                }
                $custId = $data['customer_id'] ?? $customerId;
                if (!$custId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id required']);
                    exit;
                }
                $result = $lifecycleService->calculateLifecycleStage($custId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'calculate_all':
                if (!$adminId) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'error' => 'Admin authorization required']);
                    exit;
                }
                // Calculate for all customers (async recommended)
                $stmt = $db->prepare("SELECT id FROM customers LIMIT 500");
                $stmt->execute();
                $customers = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                $calculated = 0;
                foreach ($customers as $custId) {
                    $result = $lifecycleService->calculateLifecycleStage($custId);
                    if ($result['success']) {
                        $calculated++;
                    }
                }

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'calculated' => $calculated,
                    'total' => count($customers)
                ]);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
