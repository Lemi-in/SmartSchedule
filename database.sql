-- Drop the database if it already exists (optional)
DROP DATABASE IF EXISTS university_scheduler;

-- Create and select the database
CREATE DATABASE university_scheduler;
USE university_scheduler;

-- 1. Departments
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 2. Sections (A, B, C etc. within departments)
CREATE TABLE sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name CHAR(1) NOT NULL, -- A, B, C etc.
    UNIQUE KEY (department_id, name),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

-- 3. Time slots (Ethiopian time)
CREATE TABLE time_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    UNIQUE KEY (start_time, end_time)
) ENGINE=InnoDB;

-- 4. Classrooms
CREATE TABLE classrooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    building VARCHAR(50) NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    capacity INT NOT NULL,
    UNIQUE KEY (building, room_number)
) ENGINE=InnoDB;

-- 5. Courses
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    code VARCHAR(20) NOT NULL,
    title VARCHAR(100) NOT NULL,
    credit_hours TINYINT NOT NULL,
    UNIQUE KEY (department_id, code),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

-- 6. User roles
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 7. Users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- 8. Teachers (extends users)
CREATE TABLE teachers (
    teacher_id INT PRIMARY KEY,
    qualification VARCHAR(100),
    hire_date DATE,
    FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Students (extends users)
CREATE TABLE students (
    student_id INT PRIMARY KEY,
    section_id INT NOT NULL,
    admission_number VARCHAR(20) NOT NULL UNIQUE,
    admission_date DATE NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(section_id)
) ENGINE=InnoDB;

-- 10. Teacher-course assignments
CREATE TABLE teacher_courses (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL,
    academic_year YEAR NOT NULL,
    UNIQUE KEY (teacher_id, course_id, academic_year),
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id)
) ENGINE=InnoDB;

-- 11. Weekly schedule (main table)
CREATE TABLE schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    section_id INT NOT NULL,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    slot_id INT NOT NULL,
    room_id INT NOT NULL,
    class_type ENUM('lecture', 'exam') NOT NULL,
    day_of_week TINYINT NOT NULL CHECK (day_of_week BETWEEN 1 AND 6), -- 1=Monday to 6=Saturday
    academic_year YEAR NOT NULL,
    semester TINYINT NOT NULL, -- 1 or 2
    created_by INT NOT NULL, -- admin who created
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Prevent teacher double-booking
    UNIQUE KEY (teacher_id, slot_id, day_of_week, academic_year, semester),
    -- Prevent section double-booking
    UNIQUE KEY (section_id, slot_id, day_of_week, academic_year, semester),
    -- Prevent room double-booking
    UNIQUE KEY (room_id, slot_id, day_of_week, academic_year, semester),
    FOREIGN KEY (section_id) REFERENCES sections(section_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id),
    FOREIGN KEY (slot_id) REFERENCES time_slots(slot_id),
    FOREIGN KEY (room_id) REFERENCES classrooms(room_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 12. Schedule modification requests (by teachers)
CREATE TABLE schedule_changes (
    change_id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    requested_by INT NOT NULL, -- teacher
    requested_slot_id INT NOT NULL,
    requested_room_id INT NOT NULL,
    requested_day TINYINT NOT NULL CHECK (requested_day BETWEEN 1 AND 6),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reason TEXT,
    processed_by INT, -- admin who handled
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id),
    FOREIGN KEY (requested_by) REFERENCES teachers(teacher_id),
    FOREIGN KEY (requested_slot_id) REFERENCES time_slots(slot_id),
    FOREIGN KEY (requested_room_id) REFERENCES classrooms(room_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Insert initial data
INSERT INTO time_slots (name, start_time, end_time) VALUES
('Slot 1', '14:30:00', '16:30:00'),
('Slot 2', '16:30:00', '18:00:00'),
('Slot 3', '19:00:00', '21:30:00'),
('Slot 4', '21:30:00', '23:00:00');

INSERT INTO roles (name) VALUES
('administrator'),
('teacher'),
('student');

-- 1. Insert sample departments
INSERT INTO departments (name, code) VALUES
('Computer Science', 'CS'),
('Electrical Engineering', 'EE'),
('Mechanical Engineering', 'ME'),
('Mathematics', 'MATH'),
('Physics', 'PHY');

-- 2. Insert sample sections for each department (A, B, C)
INSERT INTO sections (department_id, name) VALUES
-- CS sections
(1, 'A'), (1, 'B'), (1, 'C'),
-- EE sections
(2, 'A'), (2, 'B'),
-- ME sections
(3, 'A'), (3, 'B'), (3, 'C'),
-- MATH sections
(4, 'A'), (4, 'B'),
-- PHY sections
(5, 'A');

-- 3. Insert sample classrooms
INSERT INTO classrooms (building, room_number, capacity) VALUES
('Science Building', 'S101', 30),
('Science Building', 'S102', 30),
('Main Building', 'M201', 50),
('Main Building', 'M202', 50),
('Engineering Block', 'E101', 40),
('Engineering Block', 'E102', 40);

-- 4. Insert sample courses for each department
INSERT INTO courses (department_id, code, title, credit_hours) VALUES
-- CS courses
(1, 'CS101', 'Introduction to Programming', 4),
(1, 'CS201', 'Data Structures', 4),
(1, 'CS301', 'Database Systems', 3),
-- EE courses 
(2, 'EE101', 'Circuit Theory', 4),
(2, 'EE201', 'Digital Electronics', 4),
-- ME courses
(3, 'ME101', 'Thermodynamics', 3),
(3, 'ME201', 'Fluid Mechanics', 4),
-- MATH courses
(4, 'MATH101', 'Calculus I', 4),
(4, 'MATH201', 'Linear Algebra', 3),
-- PHY courses
(5, 'PHY101', 'General Physics', 4);

-- 5. Create sample users (password for all is 'test1234')
-- Users with MD5 hashed passwords (password = 'test1234' for all)
INSERT INTO users (role_id, username, email, password_hash, first_name, last_name, phone, is_active) VALUES
-- Administrators
(1, 'admin1', 'admin1@university.edu', MD5('test1234'), 'Abebe', 'Kebede', '0911123456', TRUE),
(1, 'admin2', 'admin2@university.edu', MD5('test1234'), 'Meseret', 'Hailu', '0911123457', TRUE),

-- Teachers
(2, 'teacher1', 'teacher1@university.edu', MD5('test1234'), 'Yohannes', 'Tesfaye', '0911123458', TRUE),
(2, 'teacher2', 'teacher2@university.edu', MD5('test1234'), 'Meron', 'Girma', '0911123459', TRUE),
(2, 'teacher3', 'teacher3@university.edu', MD5('test1234'), 'Tewodros', 'Assefa', '0911123460', TRUE),
(2, 'teacher4', 'teacher4@university.edu', MD5('test1234'), 'Selam', 'Mulugeta', '0911123461', TRUE),

-- Students
(3, 'student1', 'student1@university.edu', MD5('test1234'), 'Dawit', 'Hailu', '0911123462', TRUE),
(3, 'student2', 'student2@university.edu', MD5('test1234'), 'Eyerusalem', 'Teshome', '0911123463', TRUE),
(3, 'student3', 'student3@university.edu', MD5('test1234'), 'Fitsum', 'Gebre', '0911123464', TRUE),
(3, 'student4', 'student4@university.edu', MD5('test1234'), 'Genet', 'Kassa', '0911123465', TRUE),
(3, 'student5', 'student5@university.edu', MD5('test1234'), 'Haben', 'Yohannes', '0911123466', TRUE);
-- 6. Populate teacher details
INSERT INTO teachers (teacher_id, qualification, hire_date) VALUES
(3, 'PhD in Computer Science', '2018-09-01'),
(4, 'MSc in Electrical Engineering', '2019-03-15'),
(5, 'PhD in Mechanical Engineering', '2017-11-01'),
(6, 'MSc in Mathematics', '2020-02-10');

-- 7. Populate student details with section assignments
INSERT INTO students (student_id, section_id, admission_number, admission_date) VALUES
-- CS students
(7, 1, 'CS2023001', '2023-09-01'), -- CS-A
(8, 2, 'CS2023002', '2023-09-01'), -- CS-B
-- EE students
(9, 4, 'EE2023001', '2023-09-01'), -- EE-A
-- ME students
(10, 6, 'ME2023001', '2023-09-01'), -- ME-A
-- MATH student
(11, 9, 'MATH2023001', '2023-09-01'); -- MATH-A

-- 8. Assign teachers to courses for current year
INSERT INTO teacher_courses (teacher_id, course_id, academic_year) VALUES
-- Teacher 3 (CS) teaches CS101 and CS201
(3, 1, 2023), (3, 2, 2023),
-- Teacher 4 (EE) teaches EE101 and EE201
(4, 4, 2023), (4, 5, 2023),
-- Teacher 5 (ME) teaches ME101
(5, 6, 2023),
-- Teacher 6 (MATH) teaches MATH101
(6, 8, 2023);

-- 9. Create sample schedules
INSERT INTO schedules (section_id, course_id, teacher_id, slot_id, room_id, class_type, day_of_week, academic_year, semester, created_by) VALUES
-- CS-A schedule
(1, 1, 3, 1, 1, 'lecture', 1, 2023, 1, 1), -- Mon 2:30-4:30 PM CS101 in S101
(1, 2, 3, 2, 1, 'lecture', 3, 2023, 1, 1), -- Wed 4:30-6:00 PM CS201 in S101
-- EE-A schedule
(4, 4, 4, 3, 3, 'lecture', 2, 2023, 1, 1), -- Tue 7:00-9:30 PM EE101 in M201
-- ME-A schedule
(6, 6, 5, 4, 5, 'lecture', 4, 2023, 1, 1), -- Thu 9:30-11:00 PM ME101 in E101
-- MATH-A schedule
(9, 8, 6, 1, 2, 'lecture', 5, 2023, 1, 1); -- Fri 2:30-4:30 PM MATH101 in S102