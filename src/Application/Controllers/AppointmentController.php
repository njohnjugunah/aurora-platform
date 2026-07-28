<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Services\BookingService;
use App\Application\Services\AvailabilityService;
use App\Application\Validators\AppointmentValidator;
use App\Application\Exceptions\ValidationException;
use App\Application\Exceptions\InvalidBookingException;
use App\Application\Exceptions\AppointmentConflictException;
use App\Domain\Repositories\AppointmentRepository;
use Psr\Log\LoggerInterface;

class AppointmentController
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private BookingService $bookingService,
        private AppointmentValidator $validator,
        private LoggerInterface $logger
    ) {}

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function list(array $query): array
    {
        try {
            $page = (int) ($query['page'] ?? 1);
            $limit = (int) ($query['limit'] ?? 50);
            $limit = min($limit, 100);

            $filters = [
                'date' => $query['date'] ?? null,
                'staff_id' => $query['staff_id'] ?? null,
                'customer_id' => $query['customer_id'] ?? null,
                'status' => $query['status'] ?? null,
            ];

            $result = $this->appointmentRepository->findPaginated(
                $page,
                $limit,
                $filters
            );

            $this->logger->info('Appointments listed', [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total']
            ]);

            return [
                'success' => true,
                'data' => $result['appointments'],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $result['total'],
                    'pages' => ceil($result['total'] / $limit),
                    'hasMore' => ($page * $limit) < $result['total']
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to list appointments', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve appointments'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        try {
            $appointment = $this->appointmentRepository->findById($id);

            if (!$appointment) {
                $this->logger->warning('Appointment not found', ['id' => $id]);

                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Appointment not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $this->logger->info('Appointment retrieved', ['id' => $id]);

            return [
                'success' => true,
                'data' => $appointment,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve appointment', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve appointment'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function create(array $request): array
    {
        try {
            $this->validator->validate($request);

            $appointment = $this->bookingService->scheduleAppointment(
                customer_id: $request['customerId'],
                service_id: $request['serviceId'],
                staff_id: $request['staffId'],
                start_time: $request['startTime'],
                notes: $request['notes'] ?? null
            );

            $this->logger->info('Appointment created', [
                'appointment_id' => $appointment['id'],
                'customer_id' => $request['customerId'],
                'staff_id' => $request['staffId']
            ]);

            return [
                'success' => true,
                'data' => $appointment,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (ValidationException $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors()
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (AppointmentConflictException $e) {
            $this->logger->warning('Appointment conflict', [
                'staff_id' => $request['staffId'] ?? null,
                'start_time' => $request['startTime'] ?? null
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'BUSINESS_RULE_VIOLATION',
                    'message' => $e->getMessage()
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (InvalidBookingException $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'BUSINESS_RULE_VIOLATION',
                    'message' => $e->getMessage()
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to create appointment', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to create appointment'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function update(int $id, array $request): array
    {
        try {
            $appointment = $this->appointmentRepository->findById($id);

            if (!$appointment) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Appointment not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            if (in_array($appointment['status'], ['completed', 'cancelled'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'BUSINESS_RULE_VIOLATION',
                        'message' => 'Cannot update completed or cancelled appointment'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $updated = [
                'service_id' => $request['serviceId'] ?? $appointment['serviceId'],
                'staff_id' => $request['staffId'] ?? $appointment['staffId'],
                'start_time' => $request['startTime'] ?? $appointment['startTime'],
                'notes' => $request['notes'] ?? $appointment['notes'],
            ];

            $this->validator->validateUpdate($updated);

            $updated['id'] = $id;
            $updated['customerId'] = $appointment['customerId'];

            $result = $this->appointmentRepository->update($updated);

            $this->logger->info('Appointment updated', [
                'id' => $id,
                'customer_id' => $appointment['customerId']
            ]);

            return [
                'success' => true,
                'data' => $result,
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (ValidationException $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage(),
                    'details' => $e->getErrors()
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (AppointmentConflictException $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'BUSINESS_RULE_VIOLATION',
                    'message' => $e->getMessage()
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to update appointment', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to update appointment'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function cancel(int $id, array $request): array
    {
        try {
            $appointment = $this->appointmentRepository->findById($id);

            if (!$appointment) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Appointment not found'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            if (in_array($appointment['status'], ['completed', 'cancelled'])) {
                return [
                    'success' => false,
                    'error' => [
                        'code' => 'BUSINESS_RULE_VIOLATION',
                        'message' => 'Cannot cancel completed or already cancelled appointment'
                    ],
                    'meta' => ['timestamp' => date('c')]
                ];
            }

            $reason = $request['reason'] ?? 'Cancelled by user';

            $result = $this->appointmentRepository->updateStatus($id, 'cancelled', $reason);

            $this->logger->info('Appointment cancelled', [
                'id' => $id,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'data' => [
                    'id' => $id,
                    'status' => 'cancelled',
                    'cancelledAt' => date('c'),
                    'cancelReason' => $reason
                ],
                'meta' => ['timestamp' => date('c')]
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel appointment', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to cancel appointment'
                ],
                'meta' => ['timestamp' => date('c')]
            ];
        }
    }
}
