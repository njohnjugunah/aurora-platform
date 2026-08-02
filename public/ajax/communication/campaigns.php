<?php
/**
 * Campaign Management API
 * POST /ajax/communication/campaigns.php - Create/manage campaigns
 * GET /ajax/communication/campaigns.php - Get campaigns
 */

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/communication/CampaignService.php';
require_once '../../includes/communication/EmailService.php';

use GlamByMariga\Communication\CampaignService;
use GlamByMariga\Communication\EmailService;

try {
    // Admin authorization
    $adminId = $_SESSION['admin_id'] ?? null;

    if (!$adminId && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Admin authorization required']);
        exit;
    }

    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $emailService = new EmailService($db);
    $campaignService = new CampaignService($db, $emailService);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'list') {
            // Get campaigns list
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $result = $campaignService->getCampaigns($page, $limit);
            http_response_code(200);
            echo json_encode($result);

        } elseif ($action === 'analytics' && $adminId) {
            // Get campaign analytics
            $campaignId = $_GET['campaign_id'] ?? null;
            if (!$campaignId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'campaign_id required']);
                exit;
            }

            $result = $campaignService->getCampaignAnalytics($campaignId);
            http_response_code(200);
            echo json_encode($result);

        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $action = $data['action'] ?? null;

        switch ($action) {
            case 'create':
                // Create campaign
                $result = $campaignService->createCampaign($data);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'schedule':
                // Schedule campaign
                $campaignId = $data['campaign_id'] ?? null;
                if (!$campaignId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'campaign_id required']);
                    exit;
                }

                $result = $campaignService->scheduleCampaign($campaignId, $data['schedule']);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'send':
                // Send campaign
                $campaignId = $data['campaign_id'] ?? null;
                if (!$campaignId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'campaign_id required']);
                    exit;
                }

                $result = $campaignService->sendCampaign($campaignId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'create_ab_test':
                // Create A/B test
                $campaignId = $data['campaign_id'] ?? null;
                if (!$campaignId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'campaign_id required']);
                    exit;
                }

                $result = $campaignService->createABTest($campaignId, $data['test']);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'determine_winner':
                // Determine A/B test winner
                $testId = $data['test_id'] ?? null;
                if (!$testId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'test_id required']);
                    exit;
                }

                $result = $campaignService->determineABTestWinner($testId);
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'segment_recipients':
                // Get recipients for segment
                $segment = $data['segment'] ?? 'all';
                $recipients = $campaignService->getSegmentRecipients($segment);
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'segment' => $segment,
                    'recipients' => $recipients,
                    'count' => count($recipients)
                ]);
                break;

            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Unknown action: ' . $action
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
