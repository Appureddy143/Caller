-- Telecaller College Admission System
-- Compatible with Neon PostgreSQL

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    gender VARCHAR(10),
    dob DATE,
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'telecaller', 'office')),
    password_hash VARCHAR(255) NOT NULL,
    system_password VARCHAR(100),
    is_first_login BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    present_college VARCHAR(200),
    college_type VARCHAR(20) CHECK (college_type IN ('PU', 'Diploma', 'Other')),
    address TEXT,
    assigned_to INT REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected', 'callback', 'in_progress')),
    created_by INT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS feedback (
    id SERIAL PRIMARY KEY,
    student_id INT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    telecaller_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    call_status VARCHAR(30) NOT NULL CHECK (call_status IN ('accepted', 'rejected', 'other')),
    other_reason TEXT,
    callback_date DATE,
    notes TEXT,
    call_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reminders (
    id SERIAL PRIMARY KEY,
    student_id INT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    telecaller_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reminder_date DATE NOT NULL,
    is_notified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_students_assigned ON students(assigned_to);
CREATE INDEX IF NOT EXISTS idx_students_status ON students(status);
CREATE INDEX IF NOT EXISTS idx_feedback_student ON feedback(student_id);
CREATE INDEX IF NOT EXISTS idx_feedback_telecaller ON feedback(telecaller_id);
CREATE INDEX IF NOT EXISTS idx_reminders_date ON reminders(reminder_date, telecaller_id);

-- Default admin user (password: Admin@123 - will be changed on first login)
INSERT INTO users (name, email, phone, gender, dob, role, password_hash, system_password, is_first_login)
VALUES (
    'Super Admin',
    'admin@college.com',
    '9999999999',
    'Other',
    '1990-01-01',
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin@123',
    FALSE
) ON CONFLICT (email) DO NOTHING;
