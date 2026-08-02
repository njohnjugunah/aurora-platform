<?php
/**
 * Predictive Analytics API
 * POST/GET /ajax/communication/predictive.php
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/communication/PredictiveService.php';
require_once '../../includes/communication/ContentGenerationService.php';

use GlamByMariga\Communication\PredictiveService;
use GlamByMariga\Communication\ContentGenerationService;

try {
    $adminId = $_SESSION['admin_id'] ?? null;
    $customerId = $_SESSION['customer_id'] ?? $_GET['customer_id'] ?? null;

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $predictiveService = new PredictiveService($db);
    $contentService = new ContentGenerationService($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'dashboard';

        if ($action === 'dashboard') {
            $metrics = $predictiveService->getDashboardMetrics();
            http_response_code(200);
            echo json_encode($metrics);

        } elseif ($action === 'optimal_send_time' && $customerId) {
            $result = $predictiveService->getOptimalSendTime($customerId);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'subject_analytics' && $adminId) {
            $limit = $_GET['limit'] ?? 20;
            $analytics = $contentService->getSubjectLineAnalytics($limit);
            http_response_code(200);
            echo json_encode(['success' => true, 'analytics' => $analytics]);

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
            case 'calculate_patterns':
                $customerId = $data['customer_id'] ?? null;
                if (!$customerId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id required']);
                    exit;
                }
                $result = $predictiveService->calculateCustomerOpenPatterns($customerId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'segment_send_times':
                $segment = $data['segment'] ?? 'all';
                $result = $predictiveService->calculateSegmentSendTimes($segment);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'predict_campaign':
                $campaignId = $data['campaign_id'] ?? null;
                if (!$campaignId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'campaign_id required']);
                    exit;
                }
                $result = $predictiveService->predictCampaignPerformance($campaignId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'generate_subject_lines':
                $subject = $data['subject'] ?? null;
                if (!$subject) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'subject required']);
                    exit;
                }
                $result = $contentService->generateSubjectLineVariants($subject);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'recommend_subject_line':
                $campaignId = $data['campaign_id'] ?? null;
                $result = $contentService->recommendSubjectLine(
                    $campaignId,
                    $data['product_name'] ?? null,
                    $data['offer_type'] ?? null
                );
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'cta_recommendations':
                $type = $data['campaign_type'] ?? 'promotional';
                $result = $contentService->generateCTARecommendations($type);
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
