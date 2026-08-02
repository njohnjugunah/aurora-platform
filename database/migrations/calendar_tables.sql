-- FullCalendar Integration - Business Hours and Scheduling Tables

-- Business Hours Configuration
CREATE TABLE IF NOT EXISTS business_hours (
    id INT PRIMARY KEY AUTO_INCREMENT,
    day_of_week INT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    opening_time TIME NOT NULL,
    closing_time TIME NOT NULL,
    is_open BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_day (day_of_week),
    INDEX idx_day (day_of_week)
);

-- Holidays (Closed Days)
CREATE TABLE IF NOT EXISTS holidays (
    id INT PRIMARY KEY AUTO_INCREMENT,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(255) NOT NULL,
    reason TEXT,
    is_all_day BOOLEAN DEFAULT TRUE,
    start_time TIME,
    end_time TIME,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (holiday_date),
    INDEX idx_date (holiday_date),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Break Times (Lunch, etc.)
CREATE TABLE IF NOT EXISTS break_times (
    id INT PRIMARY KEY AUTO_INCREMENT,
    day_of_week INT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_type VARCHAR(100) DEFAULT 'lunch',
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_day (day_of_week),
    INDEX idx_time (start_time, end_time)
);

-- Service Duration and Availability
CREATE TABLE IF NOT EXISTS service_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    duration_minutes INT NOT NULL COMMENT 'How long the service takes',
    max_concurrent INT DEFAULT 1 COMMENT 'How many can be booked at same time',
    requires_confirmation BOOLEAN DEFAULT TRUE,
    buffer_before_minutes INT DEFAULT 0 COMMENT 'Buffer time before next booking',
    buffer_after_minutes INT DEFAULT 0 COMMENT 'Buffer time after this booking',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_service (service_id),
    INDEX idx_service (service_id)
);

-- Staff Availability and Schedules
CREATE TABLE IF NOT EXISTS staff_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_staff (staff_id),
    INDEX idx_date (date),
    INDEX idx_availability (is_available)
);

-- Appointment Time Slots (For real-time availability)
CREATE TABLE IF NOT EXISTS appointment_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_id INT NOT NULL,
    staff_id INT,
    slot_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    booked_by INT,
    booking_id INT,
    locked_until DATETIME,
    lock_token VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_service (service_id),
    INDEX idx_staff (staff_id),
    INDEX idx_date (slot_date),
    INDEX idx_availability (is_available),
    INDEX idx_locked (locked_until),
    UNIQUE KEY unique_slot (service_id, slot_date, start_time)
);

-- Booking Reschedule History
CREATE TABLE IF NOT EXISTS booking_reschedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    original_date DATETIME NOT NULL,
    new_date DATETIME NOT NULL,
    reason VARCHAR(255),
    requested_by INT COMMENT 'Staff or customer who requested',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_status (status)
);

-- Update bookings table to support calendar features
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS slot_locked_until DATETIME COMMENT 'Slot locked for payment processing';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS lock_reason VARCHAR(100);
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS lock_token VARCHAR(100);
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS staff_id INT COMMENT 'Assigned staff member';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS calendar_color VARCHAR(20) DEFAULT '#B76E79' COMMENT 'Color for calendar display';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS is_confirmed BOOLEAN DEFAULT FALSE COMMENT 'Admin has confirmed';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS reminder_sent BOOLEAN DEFAULT FALSE COMMENT 'Reminder email sent';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS reminder_sent_at DATETIME;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancellation_reason TEXT;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancelled_by INT COMMENT 'Who cancelled the booking';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancelled_at DATETIME;

-- Add foreign key for staff_id if not exists
ALTER TABLE bookings ADD FOREIGN KEY IF NOT EXISTS fk_booking_staff (staff_id) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE bookings ADD FOREIGN KEY IF NOT EXISTS fk_booking_cancelled_by (cancelled_by) REFERENCES users(id) ON DELETE SET NULL;

-- Insert default business hours (Monday-Saturday, 8AM-8PM; Sunday Closed)
INSERT IGNORE INTO business_hours (day_of_week, opening_time, closing_time, is_open) VALUES
(0, '10:00:00', '17:00:00', TRUE),   -- Sunday: 10AM-5PM (optional)
(1, '08:00:00', '20:00:00', TRUE),   -- Monday: 8AM-8PM
(2, '08:00:00', '20:00:00', TRUE),   -- Tuesday: 8AM-8PM
(3, '08:00:00', '20:00:00', TRUE),   -- Wednesday: 8AM-8PM
(4, '08:00:00', '20:00:00', TRUE),   -- Thursday: 8AM-8PM
(5, '08:00:00', '20:00:00', TRUE),   -- Friday: 8AM-8PM
(6, '08:00:00', '20:00:00', TRUE);   -- Saturday: 8AM-8PM

-- Insert default break times (Lunch 1-2PM)
INSERT IGNORE INTO break_times (day_of_week, start_time, end_time, break_type, description) VALUES
(1, '13:00:00', '14:00:00', 'lunch', 'Lunch Break'),
(2, '13:00:00', '14:00:00', 'lunch', 'Lunch Break'),
(3, '13:00:00', '14:00:00', 'lunch', 'Lunch Break'),
(4, '13:00:00', '14:00:00', 'lunch', 'Lunch Break'),
(5, '13:00:00', '14:00:00', 'lunch', 'Lunch Break'),
(6, '13:00:00', '14:00:00', 'lunch', 'Lunch Break');
