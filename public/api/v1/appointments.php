<?php

/**
 * Mobile API v1 - Appointments Endpoints
 * GET/POST/PUT/DELETE /api/v1/appointments
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/mobile/JwtTokenService.php';
require_once __DIR__ . '/../../../includes/mobile/MobileApiMiddleware.php';
require_once __DIR__ . '/../../../includes/communication/NotificationService.php';

use GlamByMariga\Mobile\JwtTokenService;
use GlamByMariga\Mobile\MobileApiMiddleware;
use GlamByMariga\Communication\NotificationService;

// Initialize API response
MobileApiMiddleware::init();

try {
    $db = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $tokenService = new JwtTokenService($db);
    $middleware = new MobileApiMiddleware($tokenService);

    // Authenticate
    if (!$middleware->authenticate()) {
        return;
    }

    $customerId = $middleware->getCustomerId();
    $deviceId = $middleware->getDeviceId();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';
    $appointmentId = $_GET['id'] ?? null;

    if ($method === 'GET') {
        if ($action === 'list') {
            handleListAppointments($db, $customerId);
        } elseif ($action === 'detail' && $appointmentId) {
            handleGetAppointment($db, $customerId, $appointmentId);
        } else {
            MobileApiMiddleware::sendError('Unknown action', 400);
        }

    } elseif ($method === 'POST') {
        $data = MobileApiMiddleware::getRequestData();

        if ($action === 'create') {
            handleCreateAppointment($db, $customerId, $data);
        } else {
            MobileApiMiddleware::sendError('Unknown action', 400);
        }

    } elseif ($method === 'PUT') {
        $data = MobileApiMiddleware::getRequestData();

        if ($action === 'update' && $appointmentId) {
            handleUpdateAppointment($db, $customerId, $appointmentId, $data);
        } else {
            MobileApiMiddleware::sendError('Unknown action', 400);
        }

    } elseif ($method === 'DELETE') {
        if ($action === 'cancel' && $appointmentId) {
            handleCancelAppointment($db, $customerId, $appointmentId);
        } else {
            MobileApiMiddleware::sendError('Unknown action', 400);
        }

    } else {
        MobileApiMiddleware::sendError('Method not allowed', 405);
    }

    // Log access
    MobileApiMiddleware::logAccess($db, $customerId, $deviceId, '/api/v1/appointments?action=' . $action, $method, 200);

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    MobileApiMiddleware::sendError('Server error', 500);
}

/**
 * List customer appointments
 */
function handleListAppointments(\PDO $db, int $customerId): void
{
    $page = (int)(MobileApiMiddleware::getQuery('page', 1));
    $perPage = (int)(MobileApiMiddleware::getQuery('per_page', 20));
    $status = MobileApiMiddleware::getQuery('status', null);

    $perPage = min($perPage, 100); // Max 100 per page

    $offset = ($page - 1) * $perPage;

    // Build query
    $query = "SELECT b.id, b.service_id, s.name as service_name, s.duration as service_duration,
                     b.staff_id, st.name as staff_name, b.booking_date, b.booking_time,
                     b.status, b.notes, b.amount, b.created_at
              FROM bookings b
              JOIN services s ON b.service_id = s.id
              LEFT JOIN staff st ON b.staff_id = st.id
              WHERE b.customer_id = ?";

    $params = [$customerId];

    if ($status) {
        $query .= " AND b.status = ?";
        $params[] = $status;
    }

    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM bookings WHERE customer_id = ?" . ($status ? " AND status = ?" : ""));
    $countStmt->execute(array_slice($params, 0, $status ? 2 : 1));
    $total = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];

    // Fetch data
    $query .= " ORDER BY b.booking_date DESC, b.booking_time DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    MobileApiMiddleware::paginated($appointments, $page, $perPage, $total, 'Appointments retrieved');
}

/**
 * Get appointment detail
 */
function handleGetAppointment(\PDO $db, int $customerId, int $appointmentId): void
{
    $stmt = $db->prepare(
        "SELECT b.id, b.service_id, s.name as service_name, s.description, s.duration,
                s.price, b.staff_id, st.name as staff_name, st.avatar_url as staff_avatar,
                b.booking_date, b.booking_time, b.status, b.notes,
                b.amount, b.paid_at, b.payment_method, b.created_at
         FROM bookings b
         JOIN services s ON b.service_id = s.id
         LEFT JOIN staff st ON b.staff_id = st.id
         WHERE b.id = ? AND b.customer_id = ? LIMIT 1"
    );
    $stmt->execute([$appointmentId, $customerId]);

    if ($stmt->rowCount() === 0) {
        MobileApiMiddleware::sendError('Appointment not found', 404);
    }

    $appointment = $stmt->fetch(\PDO::FETCH_ASSOC);

    MobileApiMiddleware::success($appointment, 'Appointment retrieved');
}

/**
 * Create appointment
 */
function handleCreateAppointment(\PDO $db, int $customerId, array $data): void
{
    $errors = MobileApiMiddleware::validate($data, [
        'service_id' => ['required', 'integer'],
        'booking_date' => ['required', 'date'],
        'booking_time' => ['required'],
        'staff_id' => ['integer'],
        'notes' => ['max:500'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    // Verify service exists
    $stmt = $db->prepare("SELECT price FROM services WHERE id = ? LIMIT 1");
    $stmt->execute([$data['service_id']]);

    if ($stmt->rowCount() === 0) {
        MobileApiMiddleware::sendError('Service not found', 404);
    }

    $service = $stmt->fetch(\PDO::FETCH_ASSOC);

    // Check for conflicts (simplified)
    $stmt = $db->prepare(
        "SELECT COUNT(*) as count FROM bookings
         WHERE service_id = ? AND booking_date = ? AND booking_time = ? AND status != 'cancelled'"
    );
    $stmt->execute([$data['service_id'], $data['booking_date'], $data['booking_time']]);

    if ($stmt->fetch(\PDO::FETCH_ASSOC)['count'] > 0) {
        MobileApiMiddleware::sendError('Time slot already booked', 409);
    }

    // Create booking
    $stmt = $db->prepare(
        "INSERT INTO bookings (customer_id, service_id, staff_id, booking_date, booking_time, amount, notes, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    $stmt->execute([
        $customerId,
        $data['service_id'],
        $data['staff_id'] ?? null,
        $data['booking_date'],
        $data['booking_time'],
        $service['price'],
        $data['notes'] ?? null,
    ]);

    $bookingId = $db->lastInsertId();

    // Send confirmation notification
    try {
        $notificationService = new NotificationService($db);
        $notificationService->notifyAppointmentConfirmed($bookingId);
    } catch (Exception $e) {
        error_log('Notification sending failed: ' . $e->getMessage());
    }

    MobileApiMiddleware::success(
        ['booking_id' => $bookingId, 'status' => 'pending'],
        'Appointment created',
        201
    );
}

/**
 * Update appointment
 */
function handleUpdateAppointment(\PDO $db, int $customerId, int $appointmentId, array $data): void
{
    // Verify booking belongs to customer
    $stmt = $db->prepare("SELECT status FROM bookings WHERE id = ? AND customer_id = ? LIMIT 1");
    $stmt->execute([$appointmentId, $customerId]);

    if ($stmt->rowCount() === 0) {
        MobileApiMiddleware::sendError('Appointment not found', 404);
    }

    $booking = $stmt->fetch(\PDO::FETCH_ASSOC);

    // Can only update pending/confirmed appointments
    if (!in_array($booking['status'], ['pending', 'confirmed'])) {
        MobileApiMiddleware::sendError('Cannot update appointment with status: ' . $booking['status'], 400);
    }

    $errors = MobileApiMiddleware::validate($data, [
        'booking_date' => ['date'],
        'booking_time' => [],
        'notes' => ['max:500'],
    ]);

    if (!empty($errors)) {
        MobileApiMiddleware::validationError($errors);
    }

    $updates = [];
    $params = [];

    if (isset($data['booking_date'])) {
        $updates[] = "booking_date = ?";
        $params[] = $data['booking_date'];
    }

    if (isset($data['booking_time'])) {
        $updates[] = "booking_time = ?";
        $params[] = $data['booking_time'];
    }

    if (isset($data['notes'])) {
        $updates[] = "notes = ?";
        $params[] = $data['notes'];
    }

    if (empty($updates)) {
        MobileApiMiddleware::sendError('No fields to update', 400);
    }

    $params[] = $appointmentId;

    $stmt = $db->prepare("UPDATE bookings SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);

    MobileApiMiddleware::success([], 'Appointment updated');
}

/**
 * Cancel appointment
 */
function handleCancelAppointment(\PDO $db, int $customerId, int $appointmentId): void
{
    $stmt = $db->prepare("SELECT status FROM bookings WHERE id = ? AND customer_id = ? LIMIT 1");
    $stmt->execute([$appointmentId, $customerId]);

    if ($stmt->rowCount() === 0) {
        MobileApiMiddleware::sendError('Appointment not found', 404);
    }

    $booking = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($booking['status'] === 'cancelled') {
        MobileApiMiddleware::sendError('Appointment already cancelled', 400);
    }

    $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$appointmentId]);

    MobileApiMiddleware::success([], 'Appointment cancelled');
}
