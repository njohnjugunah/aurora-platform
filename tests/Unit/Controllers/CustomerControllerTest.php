<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Application\Controllers\CustomerController;
use App\Application\Validators\CustomerValidator;
use App\Application\Exceptions\ValidationException;
use App\Domain\Repositories\CustomerRepository;
use Psr\Log\LoggerInterface;

class CustomerControllerTest extends TestCase
{
    private CustomerController $controller;
    private $customerRepository;
    private $validator;
    private $logger;

    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->validator = $this->createMock(CustomerValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new CustomerController(
            $this->customerRepository,
            $this->validator,
            $this->logger
        );
    }

    public function testListCustomers(): void
    {
        $query = [
            'page' => 1,
            'limit' => 50,
            'loyalty_tier' => 'Gold',
        ];

        $mockCustomers = [
            ['id' => 1, 'name' => 'John Doe', 'loyalty_tier' => 'Gold'],
            ['id' => 2, 'name' => 'Jane Smith', 'loyalty_tier' => 'Gold'],
        ];

        $this->customerRepository->method('findFiltered')->willReturn([
            'data' => $mockCustomers,
            'total' => 2,
        ]);

        $result = $this->controller->list($query);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(2, $result['data']);
    }

    public function testGetCustomer(): void
    {
        $customerId = 1;
        $mockCustomer = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+254712345678',
        ];

        $this->customerRepository->method('findById')->willReturn($mockCustomer);

        $result = $this->controller->get($customerId);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals($mockCustomer, $result['data']);
    }

    public function testCreateCustomer(): void
    {
        $request = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+254712345678',
        ];

        $this->validator->method('validate')->willReturn(true);
        $this->customerRepository->method('save')->willReturn(1);
        $this->customerRepository->method('findById')->willReturn(array_merge(['id' => 1], $request));

        $result = $this->controller->create($request);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(201, $result['meta']['code']);
    }

    public function testCreateCustomerValidationError(): void
    {
        $request = [
            'name' => 'J',
            'email' => 'john@example.com',
            'phone' => '+254712345678',
        ];

        $this->validator->method('validate')
            ->will($this->throwException(new ValidationException('Validation failed', ['name' => ['too short']])));

        $result = $this->controller->create($request);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('VALIDATION_ERROR', $result['error']['code']);
    }

    public function testUpdateCustomer(): void
    {
        $customerId = 1;
        $request = [
            'name' => 'John Updated',
            'phone' => '+254712345679',
        ];

        $existingCustomer = [
            'id' => 1,
            'name' => 'John Doe',
            'phone' => '+254712345678',
        ];

        $this->customerRepository->method('findById')->willReturn($existingCustomer);
        $this->validator->method('validateUpdate')->willReturn(true);
        $this->customerRepository->method('update')->willReturn(1);

        $result = $this->controller->update($customerId, $request);

        $this->assertEquals('success', $result['status']);
    }

    public function testDeleteCustomer(): void
    {
        $customerId = 1;

        $existingCustomer = [
            'id' => 1,
            'name' => 'John Doe',
        ];

        $this->customerRepository->method('findById')->willReturn($existingCustomer);
        $this->customerRepository->method('delete')->willReturn(1);

        $result = $this->controller->delete($customerId);

        $this->assertEquals('success', $result['status']);
    }

    public function testDeleteCustomerNotFound(): void
    {
        $customerId = 999;

        $this->customerRepository->method('findById')->willReturn(null);

        $result = $this->controller->delete($customerId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('NOT_FOUND', $result['error']['code']);
    }

    public function testPaginationMaxLimit(): void
    {
        $query = [
            'page' => 1,
            'limit' => 500, // More than max
        ];

        $this->customerRepository->method('findFiltered')->willReturn(['data' => [], 'total' => 0]);

        $result = $this->controller->list($query);

        $this->assertEquals('success', $result['status']);
    }
}
