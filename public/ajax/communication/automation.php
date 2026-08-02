<?php
/**
 * Behavioral Automation API
 * POST /ajax/communication/automation.php - Create/manage automation rules
 * GET /ajax/communication/automation.php - Get automation status
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/communication/BehavioralAutomationService.php';
require_once '../../includes/communication/EmailService.php';
require_once '../../includes/communication/PushNotificationService.php';
require_once '../../includes/communication/SMSService.php';

use GlamByMariga\Communication\BehavioralAutomationService;
use GlamByMariga\Communication\EmailService;
use GlamByMariga\Communication\PushNotificationService;
use GlamByMariga\Communication\SMSService;

try {
    // Admin authorization
    $adminId = $_SESSION['admin_id'] ?? null;
    $customerId = $_SESSION['customer_id'] ?? $_GET['customer_id'] ?? null;

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $emailService = new EmailService($db);
    $pushService = new PushNotificationService($db);
    $smsService = new SMSService($db);
    $automationService = new BehavioralAutomationService($db, $emailService, $pushService, $smsService);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get automation statistics
        if ($adminId) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) as total_rules, SUM(execution_count) as total_executions,
                        SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_rules
                 FROM automation_rules"
            );
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Admin authorization required']);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $data['action'] ?? null;

        if ($action === 'create_rule' && $adminId) {
            // Create automation rule
            $result = $automationService->createRule($data['rule']);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'track_abandoned_cart' && $customerId) {
            // Track abandoned cart for customer
            $result = $automationService->trackAbandonedCart(
                $customerId,
                $data['cart_items'] ?? [],
                $data['cart_value'] ?? 0
            );
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'send_cart_reminder' && $adminId) {
            // Admin: Send cart reminder for customer
            $customerId = $data['customer_id'] ?? null;
            if (!$customerId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'customer_id required']);
                exit;
            }

            $result = $automationService->sendAbandonedCartReminder($customerId);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'calculate_engagement' && $adminId) {
            // Calculate engagement score for customer
            $customerId = $data['customer_id'] ?? null;
            if (!$customerId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'customer_id required']);
                exit;
            }

            $result = $automationService->calculateEngagementScore($customerId);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'win_back_campaign' && $adminId) {
            // Send win-back campaign
            $customerId = $data['customer_id'] ?? null;
            if (!$customerId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'customer_id required']);
                exit;
            }

            $result = $automationService->sendWinBackCampaign($customerId);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'process_rules' && $adminId) {
            // Process all automation rules
            $result = $automationService->processAutomationRules();
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'at_risk_customers' && $adminId) {
            // Get at-risk customers
            $limit = $data['limit'] ?? 50;
            $customers = $automationService->getAtRiskCustomers($limit);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'customers' => $customers,
                'count' => count($customers)
            ]);

        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Unknown action or insufficient permissions'
            ]);
        }

    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
