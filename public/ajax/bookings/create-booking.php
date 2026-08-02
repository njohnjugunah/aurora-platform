<?php

header('Content-Type: application/json');

try {
    // Load environment and classes
    require_once __DIR__ . '/../../config/bootstrap.php';
    require_once __DIR__ . '/../../includes/booking/SlotManager.php';

    use GlamByMariga\Booking\SlotManager;

    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(json_encode([
            'success' => false,
            'error' => 'Method not allowed',
            'error_code' => 'METHOD_NOT_ALLOWED'
        ]));
    }

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid request data',
            'error_code' => 'INVALID_REQUEST'
        ]));
    }

    // Extract parameters
    $serviceId = $input['service_id'] ?? null;
    $date = $input['date'] ?? null;
    $time = $input['time'] ?? null;
    $customerId = $input['customer_id'] ?? null;
    $customerName = $input['customer_name'] ?? null;
    $customerEmail = $input['customer_email'] ?? null;
    $customerPhone = $input['customer_phone'] ?? null;
    $staffId = $input['staff_id'] ?? null;
    $notes = $input['notes'] ?? null;

    // Validate required fields
    if (!$serviceId || !$date || !$time || !$customerName || !$customerEmail || !$customerPhone) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Missing required fields',
            'error_code' => 'MISSING_FIELDS'
        ]));
    }

    // Validate date and time format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid date format (use YYYY-MM-DD)',
            'error_code' => 'INVALID_DATE'
        ]));
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => 'Invalid time format (use HH:MM)',
            'error_code' => 'INVALID_TIME'
        ]));
    }

    // Verify service exists
    $stmt = $db->prepare("SELECT id, name, price FROM services WHERE id = ? LIMIT 1");
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

    // Lock the slot
    $slotManager = new SlotManager($db);
    $lockResult = $slotManager->lockSlot($serviceId, $date, $time, $customerId ?? 0);

    if (!$lockResult['success']) {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'error' => $lockResult['error'] ?? 'Failed to lock slot',
            'error_code' => 'SLOT_LOCK_FAILED'
        ]));
    }

    // Create booking
    $bookingDateTime = $date . ' ' . $time . ':00';
    $stmt = $db->prepare(
        "INSERT INTO bookings
        (service_id, customer_id, customer_name, customer_email, customer_phone,
         booking_time, staff_id, notes, status, payment_status, lock_token, slot_locked_until)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?)"
    );

    $stmt->execute([
        $serviceId,
        $customerId,
        $customerName,
        $customerEmail,
        $customerPhone,
        $bookingDateTime,
        $staffId,
        $notes,
        $lockResult['lock_token'],
        $lockResult['expires_at']
    ]);

    $bookingId = $db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'booking' => [
            'id' => $bookingId,
            'service_id' => $serviceId,
            'service_name' => $service['name'],
            'service_price' => $service['price'],
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'booking_date' => $date,
            'booking_time' => $time,
            'status' => 'pending',
            'lock_token' => $lockResult['lock_token'],
            'expires_at' => $lockResult['expires_at']
        ],
        'message' => 'Booking created. Please complete payment within ' . $lockResult['expires_in_minutes'] . ' minutes.'
    ]);

} catch (Exception $e) {
    error_log('Create booking error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while creating the booking',
        'error_code' => 'INTERNAL_SERVER_ERROR'
    ]);
}
