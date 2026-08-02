<?php
/**
 * Churn Prediction API
 * POST/GET /ajax/communication/churn.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/communication/ChurnPredictionService.php';

use GlamByMariga\Communication\ChurnPredictionService;

try {
    $adminId = $_SESSION['admin_id'] ?? null;
    $customerId = $_SESSION['customer_id'] ?? $_GET['customer_id'] ?? null;

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $churnService = new ChurnPredictionService($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!$adminId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Admin authorization required']);
            exit;
        }

        $action = $_GET['action'] ?? 'analytics';

        if ($action === 'analytics') {
            $analytics = $churnService->getAnalytics();
            http_response_code(200);
            echo json_encode(['success' => true, 'analytics' => $analytics]);

        } elseif ($action === 'high_risk') {
            $threshold = $_GET['threshold'] ?? 70;
            $limit = $_GET['limit'] ?? 50;
            $customers = $churnService->getHighRiskCustomers($threshold, $limit);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'customers' => $customers,
                'count' => count($customers)
            ]);

        } elseif ($action === 'prediction' && $customerId) {
            $stmt = $db->prepare("SELECT * FROM churn_predictions WHERE customer_id = ?");
            $stmt->execute([$customerId]);
            $prediction = $stmt->fetch(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'prediction' => $prediction
            ]);

        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$adminId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Admin authorization required']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $data['action'] ?? null;

        switch ($action) {
            case 'calculate_churn':
                $customerId = $data['customer_id'] ?? null;
                if (!$customerId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id required']);
                    exit;
                }
                $result = $churnService->calculateChurnProbability($customerId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'calculate_batch':
                // Calculate for all customers (async recommended for production)
                $stmt = $db->prepare("SELECT id FROM customers LIMIT 100");
                $stmt->execute();
                $customers = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                $calculated = 0;
                foreach ($customers as $custId) {
                    $result = $churnService->calculateChurnProbability($custId);
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

            case 'send_intervention':
                $customerId = $data['customer_id'] ?? null;
                $campaignId = $data['campaign_id'] ?? null;
                if (!$customerId || !$campaignId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id and campaign_id required']);
                    exit;
                }
                $result = $churnService->sendInterventionCampaign($customerId, $campaignId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'mark_retained':
                $customerId = $data['customer_id'] ?? null;
                if (!$customerId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id required']);
                    exit;
                }
                $result = $churnService->markAsRetained($customerId);
                http_response_code(200);
                echo json_encode($result);
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
