<?php
/**
 * Advanced Analytics API
 * POST/GET /ajax/analytics/advanced.php
 * LTV, cohorts, attribution, journeys
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/analytics/CustomerValueService.php';
require_once '../../includes/analytics/CohortAnalysisService.php';
require_once '../../includes/analytics/AttributionService.php';
require_once '../../includes/analytics/JourneyService.php';

use GlamByMariga\Analytics\CustomerValueService;
use GlamByMariga\Analytics\CohortAnalysisService;
use GlamByMariga\Analytics\AttributionService;
use GlamByMariga\Analytics\JourneyService;

try {
    $adminId = $_SESSION['admin_id'] ?? null;

    if (!$adminId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Admin authorization required']);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $valueService = new CustomerValueService($db);
    $cohortService = new CohortAnalysisService($db);
    $attributionService = new AttributionService($db);
    $journeyService = new JourneyService($db);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'ltv_distribution';

        switch ($action) {
            case 'ltv_distribution':
                $result = $valueService->getValueDistribution();
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'segment_performance':
                $performance = $valueService->getSegmentPerformance();
                http_response_code(200);
                echo json_encode(['success' => true, 'performance' => $performance]);
                break;

            case 'cohort_trends':
                $trends = $cohortService->getCohortTrends($_GET['limit'] ?? 10);
                http_response_code(200);
                echo json_encode(['success' => true, 'trends' => $trends]);
                break;

            case 'channel_roi':
                $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                $result = $attributionService->calculateChannelROI($startDate, $endDate);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'journey_metrics':
                $metrics = $journeyService->getAverageJourneyMetrics();
                http_response_code(200);
                echo json_encode($metrics);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $data['action'] ?? null;

        switch ($action) {
            case 'predict_ltv':
                $customerId = $data['customer_id'] ?? null;
                if (!$customerId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id required']);
                    exit;
                }
                $result = $valueService->predictCustomerLTV($customerId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'analyze_ltv_components':
                $customerId = $data['customer_id'] ?? null;
                if (!$customerId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id required']);
                    exit;
                }
                $result = $valueService->analyzeLTVComponents($customerId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'create_cohort':
                $result = $cohortService->createCohort(
                    $data['name'] ?? 'Cohort ' . date('Y-m'),
                    $data['type'] ?? 'signup_month',
                    $data['start_date'] ?? date('Y-m-d'),
                    $data['end_date'] ?? date('Y-m-d'),
                    $data['definition'] ?? []
                );
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'compare_cohorts':
                $cohortIds = $data['cohort_ids'] ?? [];
                if (empty($cohortIds)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'cohort_ids required']);
                    exit;
                }
                $result = $cohortService->compareCohorts($cohortIds);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'track_touchpoint':
                $customerId = $data['customer_id'] ?? null;
                if (!$customerId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id required']);
                    exit;
                }
                $result = $attributionService->trackTouchpoint(
                    $customerId,
                    $data['type'] ?? 'view',
                    $data['channel'] ?? 'direct',
                    $data['campaign_id'] ?? null,
                    $data['engagement_value'] ?? 0
                );
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'calculate_attribution':
                $orderId = $data['order_id'] ?? null;
                if (!$orderId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'order_id required']);
                    exit;
                }
                $models = $data['models'] ?? ['first_touch', 'last_touch', 'linear', 'time_decay'];
                $result = $attributionService->calculateOrderAttribution($orderId, $models);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'map_journey':
                $customerId = $data['customer_id'] ?? null;
                if (!$customerId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'customer_id required']);
                    exit;
                }
                $result = $journeyService->mapCustomerJourney($customerId);
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
