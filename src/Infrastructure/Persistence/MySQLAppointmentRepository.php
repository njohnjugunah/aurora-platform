<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Models\Appointment;
use App\Domain\Repositories\AppointmentRepository;
use App\Infrastructure\Database\Connection;
use PDO;
use PDOException;

class MySQLAppointmentRepository implements AppointmentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM appointments
            WHERE id = ? AND deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findBetween(string $startTime, string $endTime): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM appointments
            WHERE start_time BETWEEN ? AND ?
            AND deleted_at IS NULL
            ORDER BY start_time ASC
        ');
        $stmt->execute([$startTime, $endTime]);
        return $stmt->fetchAll();
    }

    public function findByStaffAndTime(int $staffId, string $startTime, string $endTime): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM appointments
            WHERE staff_id = ?
            AND start_time BETWEEN ? AND ?
            AND status NOT IN (?, ?)
            AND deleted_at IS NULL
            ORDER BY start_time ASC
        ');
        $stmt->execute([$staffId, $startTime, $endTime, 'cancelled', 'no_show']);
        return $stmt->fetchAll();
    }

    public function findByCustomerId(int $customerId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM appointments
            WHERE customer_id = ?
            AND deleted_at IS NULL
            ORDER BY start_time DESC
        ');
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public function save(array $appointment): int
    {
        if (isset($appointment['id'])) {
            return $this->update($appointment['id'], $appointment);
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO appointments (
                customer_id, service_id, staff_id, start_time,
                end_time, status, notes, prepaid_amount, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');

        $stmt->execute([
            $appointment['customer_id'],
            $appointment['service_id'],
            $appointment['staff_id'],
            $appointment['start_time'],
            $appointment['end_time'],
            $appointment['status'] ?? 'pending',
            $appointment['notes'] ?? null,
            $appointment['prepaid_amount'] ?? 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(array $data): mixed
    {
        if (!isset($data['id'])) {
            return null;
        }

        $id = $data['id'];
        $updates = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'created_at') {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($updates)) {
            return $id;
        }

        $values[] = $id;
        $sql = 'UPDATE appointments SET ' . implode(', ', $updates) . ', updated_at = NOW() WHERE id = ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE appointments SET deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function findByStaffId(int $staffId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM appointments
            WHERE staff_id = ?
            AND deleted_at IS NULL
            ORDER BY start_time DESC
        ');
        $stmt->execute([$staffId]);
        return $stmt->fetchAll();
    }

    public function findStaffAndDate(int $staffId, string $date): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM appointments
            WHERE staff_id = ?
            AND DATE(start_time) = ?
            AND deleted_at IS NULL
            ORDER BY start_time ASC
        ');
        $stmt->execute([$staffId, $date]);
        return $stmt->fetchAll();
    }

    public function findConflicting(
        int $staffId,
        string $scheduledDate,
        string $scheduledTime,
        int $durationMinutes
    ): array
    {
        $endTime = date('Y-m-d H:i:s', strtotime("+$durationMinutes minutes", strtotime("$scheduledDate $scheduledTime")));

        $stmt = $this->pdo->prepare('
            SELECT * FROM appointments
            WHERE staff_id = ?
            AND status NOT IN (?, ?)
            AND deleted_at IS NULL
            AND (
                (start_time <= ? AND DATE_ADD(start_time, INTERVAL TIMEDIFF(end_time, start_time) MINUTE) > ?)
                OR (start_time < ? AND end_time > ?)
            )
        ');

        $stmt->execute([
            $staffId,
            'cancelled',
            'no_show',
            $scheduledTime,
            $scheduledTime,
            $endTime,
            $scheduledDate
        ]);

        return $stmt->fetchAll();
    }

    public function findPaginated(
        int $page = 1,
        int $perPage = 20,
        array $filters = []
    ): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = ['deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['date'])) {
            $conditions[] = 'DATE(start_time) = ?';
            $params[] = $filters['date'];
        }
        if (!empty($filters['staff_id'])) {
            $conditions[] = 'staff_id = ?';
            $params[] = $filters['staff_id'];
        }
        if (!empty($filters['customer_id'])) {
            $conditions[] = 'customer_id = ?';
            $params[] = $filters['customer_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'status = ?';
            $params[] = $filters['status'];
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE $where");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['total'];

        $stmt = $this->pdo->prepare("
            SELECT * FROM appointments
            WHERE $where
            ORDER BY start_time DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $perPage;
        $params[] = $offset;
        $stmt->execute($params);

        return [
            'appointments' => $stmt->fetchAll(),
            'total' => $total
        ];
    }

    public function updateStatus(int $id, string $status, string $reason = ''): mixed
    {
        $stmt = $this->pdo->prepare('
            UPDATE appointments
            SET status = ?, notes = CONCAT(COALESCE(notes, ""), " [", ?, "]"),
                updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ');

        $stmt->execute([$status, $reason, $id]);

        return $this->findById($id);
    }
}
