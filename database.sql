CREATE DATABASE university_scheduler;
USE university_scheduler;

CREATE TABLE universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT,
    name VARCHAR(255),
    FOREIGN KEY (university_id) REFERENCES universities(id)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'teacher', 'student'),
    university_id INT,
    department_id INT,
    section VARCHAR(10),
    FOREIGN KEY (university_id) REFERENCES universities(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE teacher_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT,
    section VARCHAR(10),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255),
    department_id INT,
    section VARCHAR(10),
    scheduled_by INT,
    schedule_type ENUM('course', 'test', 'assignment') DEFAULT 'course',
    start_time DATETIME,
    end_time DATETIME,
    room VARCHAR(255),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (scheduled_by) REFERENCES users(id)
);

CREATE TABLE locations {
    building VARCHAR(10),
    room VARCHAR(10)
};

INSERT INTO universities (name) VALUES ('Example University');

INSERT INTO departments (university_id, name) VALUES 
(1, 'Computer Science'),
(1, 'Electrical Engineering'),
(1, 'Architecture');

INSERT INTO location (building, room) VALUES
(57, 1001), (57, 1002), (57, 1003),
(57, 2001), (57, 2002), (57, 2003),
(57, 3001), (57, 3002), (57, 3003),
(57, 4001), (57, 4002), (57, 4003),
(57, 5001), (57, 5002), (57, 5003),

(58, 1001), (58, 1002), (58, 1003),
(58, 2001), (58, 2002), (58, 2003),
(58, 3001), (58, 3002), (58, 3003),
(58, 4001), (58, 4002), (58, 4003),
(58, 5001), (58, 5002), (58, 5003);


-- Users with MD5-hashed passwords
INSERT INTO users (name, email, password, role, university_id, department_id, section) VALUES
('Admin User', 'admin@example.com', MD5('admin123'), 'admin', 1, NULL, NULL),
('Teacher One', 'teacher1@example.com', MD5('teacher121'), 'teacher', 1, 1, NULL),
('Teacher Two', 'teacher2@example.com', MD5('teacher122'), 'teacher', 1, 2, NULL),
('Student One', 'student1@example.com', MD5('student121'), 'student', 1, 1, 'A');
('Student One', 'student@example.com', MD5('student123'), 'student', 1, 1, 'C');
