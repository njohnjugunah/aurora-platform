<?php

header('Content-Type: application/json');

try {
    // Load environment and classes
    require_once __DIR__ . '/../../config/bootstrap.php';
    require_once __DIR__ . '/../../includes/booking/SlotManager.php';

    use GlamByMariga\Booking\SlotManager;

    // Get parameters
    $serviceId = $_GET['service_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $staffId = $_GET['staff_id'] ?? null;

    if (!$serviceId) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Service ID is required',
            'error_code' => 'MISSING_SERVICE_ID'
        ]));
    }

    if (!$date) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Date is required (YYYY-MM-DD format)',
            'error_code' => 'MISSING_DATE'
        ]));
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid date format. Use YYYY-MM-DD',
            'error_code' => 'INVALID_DATE_FORMAT'
        ]));
    }

    // Check if service exists
    $stmt = $db->prepare("SELECT id, name FROM services WHERE id = ? LIMIT 1");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();

    if (!$service) {
        http_response_code(404);
        exit(json_encode([
            'success' => false,
            'error' => 'Service not found',
            'error_code' => 'SERVICE_NOT_FOUND'
        ]));
    }

    // Get available slots
    $slotManager = new SlotManager($db);
    $slots = $slotManager->getAvailableSlots($serviceId, $date, $staffId);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'service_id' => $serviceId,
            'service_name' => $service['name'],
            'date' => $date,
            'slots' => $slots,
            'total_available' => count($slots)
        ]
    ]);

} catch (Exception $e) {
    error_log('Get slots error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while retrieving available slots',
        'error_code' => 'INTERNAL_SERVER_ERROR'
    ]);
}
