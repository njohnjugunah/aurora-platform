/**
 * Mock API Server for Aurora Platform Frontend Testing
 * Simulates backend API responses for development
 */

import http from 'http';
import { parse } from 'url';

const PORT = 9000;

// Mock data
const mockData = {
    users: {
        'demo@aurora.local': {
            id: 1,
            email: 'demo@aurora.local',
            name: 'Demo User',
            role: 'admin'
        }
    },
    token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MSwiZW1haWwiOiJkZW1vQGF1cm9yYS5sb2NhbCIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTY4OTAwMDAwMH0.mock-token-signature',
    appointments: [
        {
            id: 1,
            customer_name: 'Alice Johnson',
            service_name: 'Facial',
            staff_name: 'Sarah',
            start_time: '2026-08-05 10:00:00',
            status: 'confirmed',
            notes: 'Regular facial appointment'
        },
        {
            id: 2,
            customer_name: 'Bob Smith',
            service_name: 'Hair Cut',
            staff_name: 'John',
            start_time: '2026-08-05 11:30:00',
            status: 'pending',
            notes: null
        },
        {
            id: 3,
            customer_name: 'Carol White',
            service_name: 'Massage',
            staff_name: 'Maria',
            start_time: '2026-08-05 14:00:00',
            status: 'confirmed',
            notes: 'Deep tissue massage'
        }
    ],
    services: [
        { id: 1, name: 'Facial', base_price: 50, duration_minutes: 60 },
        { id: 2, name: 'Hair Cut', base_price: 30, duration_minutes: 30 },
        { id: 3, name: 'Massage', base_price: 80, duration_minutes: 60 },
        { id: 4, name: 'Pedicure', base_price: 40, duration_minutes: 45 },
        { id: 5, name: 'Manicure', base_price: 35, duration_minutes: 30 }
    ],
    customers: [
        {
            id: 1,
            first_name: 'Alice',
            last_name: 'Johnson',
            email: 'alice@example.com',
            phone: '254712345678',
            status: 'active',
            date_of_birth: '1995-03-15'
        },
        {
            id: 2,
            first_name: 'Bob',
            last_name: 'Smith',
            email: 'bob@example.com',
            phone: '254723456789',
            status: 'active',
            date_of_birth: '1990-07-20'
        },
        {
            id: 3,
            first_name: 'Carol',
            last_name: 'White',
            email: 'carol@example.com',
            phone: '254734567890',
            status: 'active',
            date_of_birth: '1998-11-10'
        }
    ],
    dashboard: {
        active_customers: 45,
        staff_count: 8,
        today_revenue: 3500,
        pending_appointments: 12
    },
    reports: {
        revenue: {
            total_revenue: 125000,
            revenue_trend: 12.5,
            daily_revenue: [
                { date: '2026-08-01', revenue: 4200 },
                { date: '2026-08-02', revenue: 5100 },
                { date: '2026-08-03', revenue: 3800 },
                { date: '2026-08-04', revenue: 5500 }
            ]
        },
        appointments: {
            total_appointments: 156,
            appointment_trend: 8.3,
            by_status: {
                pending: 12,
                confirmed: 89,
                completed: 50,
                cancelled: 5
            }
        },
        staff: {
            staff_performance: [
                { id: 1, name: 'Sarah', appointments: 34, revenue: 2800, rating: 4.8, status: 'active' },
                { id: 2, name: 'John', appointments: 28, revenue: 1900, rating: 4.6, status: 'active' },
                { id: 3, name: 'Maria', appointments: 31, revenue: 2500, rating: 4.9, status: 'active' }
            ]
        }
    }
};

// Create the HTTP server
const server = http.createServer((req, res) => {
    // CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    res.setHeader('Content-Type', 'application/json');

    // Handle preflight requests
    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }

    const parsedUrl = parse(req.url, true);
    const pathname = parsedUrl.pathname;
    const query = parsedUrl.query;

    console.log(`${req.method} ${pathname}`);

    // Route handlers
    if (pathname === '/api/v1/auth/login' && req.method === 'POST') {
        handleLogin(req, res);
    } else if (pathname === '/api/v1/auth/verify' && req.method === 'GET') {
        handleVerifyToken(req, res);
    } else if (pathname === '/api/v1/appointments' && req.method === 'GET') {
        handleGetAppointments(req, res, query);
    } else if (pathname === '/api/v1/services' && req.method === 'GET') {
        handleGetServices(req, res);
    } else if (pathname === '/api/v1/customers' && req.method === 'GET') {
        handleGetCustomers(req, res, query);
    } else if (pathname === '/api/v1/reports/dashboard' && req.method === 'GET') {
        handleGetDashboard(req, res);
    } else if (pathname === '/api/v1/reports/appointments' && req.method === 'GET') {
        handleGetAppointmentReport(req, res);
    } else if (pathname === '/api/v1/reports/revenue' && req.method === 'GET') {
        handleGetRevenueReport(req, res);
    } else if (pathname === '/api/v1/reports/staff' && req.method === 'GET') {
        handleGetStaffReport(req, res);
    } else if (pathname === '/api/v1/sales' && req.method === 'POST') {
        handleCreateSale(req, res);
    } else if (pathname.match(/^\/api\/v1\/sales\/\d+\/pay$/) && req.method === 'POST') {
        const saleId = pathname.split('/')[4];
        handlePayment(req, res, saleId);
    } else {
        res.writeHead(404);
        res.end(JSON.stringify({ error: 'Not found' }));
    }
});

// Route handlers
function handleLogin(req, res) {
    let body = '';
    req.on('data', chunk => {
        body += chunk.toString();
    });

    req.on('end', () => {
        try {
            const data = JSON.parse(body);

            // Simulate authentication
            if (data.email === 'demo@aurora.local' && data.password === 'password') {
                res.writeHead(200);
                res.end(JSON.stringify({
                    success: true,
                    token: mockData.token,
                    user: mockData.users['demo@aurora.local']
                }));
            } else {
                res.writeHead(401);
                res.end(JSON.stringify({
                    success: false,
                    error: 'Invalid credentials'
                }));
            }
        } catch (error) {
            res.writeHead(400);
            res.end(JSON.stringify({ error: 'Invalid JSON' }));
        }
    });
}

function handleVerifyToken(req, res) {
    const auth = req.headers.authorization;
    if (auth && auth.includes('Bearer ')) {
        res.writeHead(200);
        res.end(JSON.stringify({ valid: true, user: mockData.users['demo@aurora.local'] }));
    } else {
        res.writeHead(401);
        res.end(JSON.stringify({ valid: false }));
    }
}

function handleGetAppointments(req, res, query) {
    res.writeHead(200);
    res.end(JSON.stringify(mockData.appointments));
}

function handleGetServices(req, res) {
    res.writeHead(200);
    res.end(JSON.stringify(mockData.services));
}

function handleGetCustomers(req, res, query) {
    let customers = mockData.customers;

    // Apply search filter if provided
    if (query.search && query.search !== '') {
        const searchTerm = query.search.toLowerCase();
        customers = customers.filter(c =>
            c.first_name.toLowerCase().includes(searchTerm) ||
            c.last_name.toLowerCase().includes(searchTerm) ||
            c.email.toLowerCase().includes(searchTerm)
        );
    }

    res.writeHead(200);
    res.end(JSON.stringify(customers));
}

function handleGetDashboard(req, res) {
    res.writeHead(200);
    res.end(JSON.stringify(mockData.dashboard));
}

function handleGetAppointmentReport(req, res) {
    res.writeHead(200);
    res.end(JSON.stringify(mockData.reports.appointments));
}

function handleGetRevenueReport(req, res) {
    res.writeHead(200);
    res.end(JSON.stringify(mockData.reports.revenue));
}

function handleGetStaffReport(req, res) {
    res.writeHead(200);
    res.end(JSON.stringify(mockData.reports.staff));
}

function handleCreateSale(req, res) {
    let body = '';
    req.on('data', chunk => {
        body += chunk.toString();
    });

    req.on('end', () => {
        try {
            const saleData = JSON.parse(body);

            // Create mock sale record
            const sale = {
                id: Math.floor(Math.random() * 10000),
                customer_id: saleData.customer_id,
                line_items: saleData.line_items,
                subtotal: saleData.subtotal,
                tax_amount: saleData.tax_amount,
                total_amount: saleData.total_amount,
                status: 'pending',
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString()
            };

            res.writeHead(200);
            res.end(JSON.stringify({
                success: true,
                id: sale.id,
                ...sale
            }));
        } catch (error) {
            res.writeHead(400);
            res.end(JSON.stringify({ error: 'Invalid JSON' }));
        }
    });
}

function handlePayment(req, res, saleId) {
    let body = '';
    req.on('data', chunk => {
        body += chunk.toString();
    });

    req.on('end', () => {
        try {
            const paymentData = JSON.parse(body);

            // Create mock payment record
            const payment = {
                id: Math.floor(Math.random() * 10000),
                sale_id: saleId,
                method: paymentData.method,
                amount: paymentData.amount,
                reference: paymentData.reference,
                status: paymentData.status || 'completed',
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString()
            };

            res.writeHead(200);
            res.end(JSON.stringify({
                success: true,
                message: `Payment of KES ${paymentData.amount.toFixed(2)} processed successfully via ${paymentData.method}`,
                payment
            }));
        } catch (error) {
            res.writeHead(400);
            res.end(JSON.stringify({ error: 'Invalid JSON' }));
        }
    });
}

// Start the server
server.listen(PORT, () => {
    console.log(`
╔════════════════════════════════════════╗
║   Aurora Platform - Mock API Server    ║
╚════════════════════════════════════════╝

✓ Server running on http://localhost:${PORT}
✓ API endpoints available at http://localhost:${PORT}/api/v1

📚 Available Endpoints:
  POST   /api/v1/auth/login              (demo@aurora.local / password)
  GET    /api/v1/auth/verify             (check token validity)
  GET    /api/v1/appointments            (list appointments)
  GET    /api/v1/services                (list services)
  GET    /api/v1/customers               (list customers)
  GET    /api/v1/reports/dashboard       (KPI metrics)
  GET    /api/v1/reports/appointments    (appointment report)
  GET    /api/v1/reports/revenue         (revenue report)
  GET    /api/v1/reports/staff           (staff performance)

🔑 Demo Credentials:
  Email:    demo@aurora.local
  Password: password

💡 Frontend API is configured to use /api/v1 relative path.
   It will work correctly when both servers are running on localhost.

Ctrl+C to stop
    `);
});

server.on('error', (error) => {
    console.error('Server error:', error);
});
