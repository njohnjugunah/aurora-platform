<?php

namespace GlamByMariga\Booking;

use DateTime;
use DateInterval;
use PDO;
use Exception;

/**
 * Slot Manager
 * Handles appointment slot availability, generation, and locking
 */
class SlotManager
{
    private $db;
    private $slotDurationMinutes = 30;
    private $lockDurationMinutes = 10;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get available slots for a given date and service
     *
     * @param int $serviceId Service ID
     * @param string $date Date in YYYY-MM-DD format
     * @param int $staffId Optional staff member ID
     *
     * @return array Available time slots
     */
    public function getAvailableSlots($serviceId, $date, $staffId = null)
    {
        try {
            // Validate date format
            $dateObj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj) {
                throw new Exception('Invalid date format');
            }

            // Check if date is holiday
            if ($this->isHoliday($date)) {
                return [];
            }

            // Get service duration
            $serviceDuration = $this->getServiceDuration($serviceId);
            if (!$serviceDuration) {
                throw new Exception('Service not found or duration not configured');
            }

            // Get business hours for the day
            $dayOfWeek = $dateObj->format('w');
            $businessHours = $this->getBusinessHours($dayOfWeek);

            if (!$businessHours || !$businessHours['is_open']) {
                return [];
            }

            // Get breaks for this day
            $breaks = $this->getBreakTimes($dayOfWeek);

            // Get existing bookings for this service on this date
            $bookings = $this->getBookingsForDate($serviceId, $date, $staffId);

            // Generate slots
            $slots = $this->generateSlots(
                $date,
                $businessHours,
                $breaks,
                $serviceDuration,
                $bookings,
                $staffId
            );

            return $slots;

        } catch (Exception $e) {
            error_log('Slot availability error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available dates for next N days
     *
     * @param int $serviceId Service ID
     * @param int $daysAhead Number of days to check ahead
     * @param int $staffId Optional staff member ID
     *
     * @return array Array of dates with available slot counts
     */
    public function getAvailableDates($serviceId, $daysAhead = 30, $staffId = null)
    {
        $availableDates = [];
        $date = new DateTime();

        for ($i = 0; $i < $daysAhead; $i++) {
            $dateStr = $date->format('Y-m-d');
            $slots = $this->getAvailableSlots($serviceId, $dateStr, $staffId);

            if (!empty($slots)) {
                $availableDates[] = [
                    'date' => $dateStr,
                    'day' => $date->format('l'),
                    'available_slots' => count($slots),
                    'first_slot' => $slots[0]['time'] ?? null
                ];
            }

            $date->add(new DateInterval('P1D'));
        }

        return $availableDates;
    }

    /**
     * Lock a time slot for payment processing
     *
     * @param int $serviceId Service ID
     * @param string $date Date in YYYY-MM-DD format
     * @param string $time Time in HH:MM format
     * @param string $customerId Customer ID
     *
     * @return array Lock token and expiry time
     */
    public function lockSlot($serviceId, $date, $time, $customerId)
    {
        try {
            $lockToken = $this->generateLockToken();
            $lockExpiry = new DateTime();
            $lockExpiry->add(new DateInterval('PT' . $this->lockDurationMinutes . 'M'));

            $datetime = $date . ' ' . $time . ':00';

            // Insert or update slot lock
            $stmt = $this->db->prepare(
                "INSERT INTO appointment_slots
                (service_id, slot_date, start_time, end_time, is_available, booked_by, locked_until, lock_token)
                VALUES (?, ?, ?, DATE_ADD(?, INTERVAL ? MINUTE), FALSE, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    is_available = FALSE,
                    booked_by = ?,
                    locked_until = ?,
                    lock_token = ?"
            );

            $endTime = new DateTime($datetime);
            $duration = $this->getServiceDuration($serviceId);
            $endTime->add(new DateInterval('PT' . $duration . 'M'));

            $stmt->execute([
                $serviceId,
                $date,
                $time,
                $time,
                $duration,
                $customerId,
                $lockExpiry->format('Y-m-d H:i:s'),
                $lockToken,
                $customerId,
                $lockExpiry->format('Y-m-d H:i:s'),
                $lockToken
            ]);

            return [
                'success' => true,
                'lock_token' => $lockToken,
                'expires_at' => $lockExpiry->format('Y-m-d H:i:s'),
                'expires_in_minutes' => $this->lockDurationMinutes
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Unlock a slot (after payment or cancellation)
     *
     * @param string $lockToken The lock token
     * @param string $reason Why the lock is being released
     *
     * @return bool Success
     */
    public function unlockSlot($lockToken, $reason = 'payment_cancelled')
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE appointment_slots
                SET is_available = TRUE, locked_until = NULL, lock_token = NULL, booked_by = NULL
                WHERE lock_token = ?"
            );
            return $stmt->execute([$lockToken]);

        } catch (Exception $e) {
            error_log('Slot unlock error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Confirm a booking (after payment received)
     *
     * @param int $bookingId Booking ID
     * @param string $lockToken Lock token to verify
     *
     * @return bool Success
     */
    public function confirmBooking($bookingId, $lockToken)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE appointment_slots
                SET booking_id = ?, is_available = FALSE, locked_until = NULL
                WHERE lock_token = ? AND lock_token IS NOT NULL"
            );
            return $stmt->execute([$bookingId, $lockToken]);

        } catch (Exception $e) {
            error_log('Booking confirmation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cleanup expired locks
     *
     * @return int Number of locks released
     */
    public function cleanupExpiredLocks()
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE appointment_slots
                SET is_available = TRUE, locked_until = NULL, lock_token = NULL, booked_by = NULL
                WHERE locked_until < NOW() AND is_available = FALSE AND booking_id IS NULL"
            );
            $stmt->execute();
            return $this->db->query("SELECT ROW_COUNT()")->fetch()[0];

        } catch (Exception $e) {
            error_log('Lock cleanup error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get business hours for a day
     */
    private function getBusinessHours($dayOfWeek)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM business_hours WHERE day_of_week = ? LIMIT 1"
        );
        $stmt->execute([$dayOfWeek]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get break times for a day
     */
    private function getBreakTimes($dayOfWeek)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM break_times WHERE day_of_week = ?"
        );
        $stmt->execute([$dayOfWeek]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get service duration in minutes
     */
    private function getServiceDuration($serviceId)
    {
        $stmt = $this->db->prepare(
            "SELECT duration_minutes FROM service_availability WHERE service_id = ? LIMIT 1"
        );
        $stmt->execute([$serviceId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['duration_minutes'] ?? 60; // Default 60 minutes
    }

    /**
     * Get bookings for a specific date and service
     */
    private function getBookingsForDate($serviceId, $date, $staffId = null)
    {
        $query = "SELECT b.booking_time, b.estimated_duration
                  FROM bookings b
                  JOIN services s ON b.service_id = s.id
                  WHERE b.service_id = ?
                  AND DATE(b.booking_time) = ?
                  AND b.status IN ('confirmed', 'completed')";

        $params = [$serviceId, $date];

        if ($staffId) {
            $query .= " AND b.staff_id = ?";
            $params[] = $staffId;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if date is a holiday
     */
    private function isHoliday($date)
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM holidays WHERE holiday_date = ? AND is_all_day = TRUE LIMIT 1"
        );
        $stmt->execute([$date]);
        return $stmt->fetch() !== false;
    }

    /**
     * Generate time slots for a day
     */
    private function generateSlots($date, $businessHours, $breaks, $duration, $bookings, $staffId = null)
    {
        $slots = [];
        $openTime = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $businessHours['opening_time']);
        $closeTime = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $businessHours['closing_time']);

        $currentTime = clone $openTime;

        while ($currentTime < $closeTime) {
            $slotEnd = clone $currentTime;
            $slotEnd->add(new DateInterval('PT' . $duration . 'M'));

            // Check if slot goes beyond closing time
            if ($slotEnd > $closeTime) {
                break;
            }

            // Check if slot overlaps with breaks
            if (!$this->overlapsWithBreak($currentTime, $slotEnd, $breaks)) {
                // Check if slot is already booked
                if (!$this->isSlotBooked($currentTime, $slotEnd, $bookings)) {
                    $slots[] = [
                        'date' => $date,
                        'time' => $currentTime->format('H:i'),
                        'end_time' => $slotEnd->format('H:i'),
                        'available' => true
                    ];
                }
            }

            $currentTime->add(new DateInterval('PT' . $this->slotDurationMinutes . 'M'));
        }

        return $slots;
    }

    /**
     * Check if time slot overlaps with break times
     */
    private function overlapsWithBreak($slotStart, $slotEnd, $breaks)
    {
        foreach ($breaks as $break) {
            $breakStart = DateTime::createFromFormat('H:i:s', $break['start_time']);
            $breakEnd = DateTime::createFromFormat('H:i:s', $break['end_time']);

            // Adjust times to same day for comparison
            $breakStart->setDate($slotStart->format('Y'), $slotStart->format('m'), $slotStart->format('d'));
            $breakEnd->setDate($slotStart->format('Y'), $slotStart->format('m'), $slotStart->format('d'));

            if ($slotStart < $breakEnd && $slotEnd > $breakStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if slot is already booked
     */
    private function isSlotBooked($slotStart, $slotEnd, $bookings)
    {
        foreach ($bookings as $booking) {
            $bookingTime = new DateTime($booking['booking_time']);
            $bookingEndTime = clone $bookingTime;
            $duration = $booking['estimated_duration'] ?? 60;
            $bookingEndTime->add(new DateInterval('PT' . $duration . 'M'));

            // Check for overlap
            if ($slotStart < $bookingEndTime && $slotEnd > $bookingTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate unique lock token
     */
    private function generateLockToken()
    {
        return 'LOCK_' . bin2hex(random_bytes(16)) . '_' . time();
    }
}
