CREATE DATABASE university_scheduler;

-- guys This SQL script creates a database for a university scheduling system.
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

INSERT INTO universities (name) VALUES ('Example University');
INSERT INTO departments (university_id, name) VALUES 
(1, 'Computer Science'),
(2, 'Electrical Engineering'), 
(3, 'Architecture');

INSERT INTO users (name, email, password, role, university_id, department_id, section) VALUES
('Admin User', 'admin@example.com', MD5('admin123'), 'admin', 1, NULL, NULL),
('Teacher One', 'teacher1@example.com', MD5('teacher121'), 'teacher', 1, 1, NULL),
('Teacher Two', 'teacher2@example.com', MD5('teacher122'), 'teacher', 1, 2, NULL),
('Teacher Three', 'teacher3@example.com', MD5('teacher123'), 'teacher', 1, 3, NULL),
('Student One', 'student1@example.com', MD5('student121'), 'student', 1, 1, 'A'),
('Student Two', 'student2@example.com', MD5('student122'), 'student', 1, 1, 'B'),
('Student Three', 'student3@example.com', MD5('student123'), 'student', 1, 1, 'C'),
('Student Four', 'student4@example.com', MD5('student124'), 'student', 1, 1, 'D'),
('Student Five', 'student5@example.com', MD5('student125'), 'student', 1, 2, 'A'),
('Student Six', 'student6@example.com', MD5('student126'), 'student', 1, 2, 'B'),
('Student Seven', 'student7@example.com', MD5('student127'), 'student', 1, 2, 'C'),
('Student Eight', 'student8@example.com', MD5('student128'), 'student', 1, 3, 'A'),
('Student Nine', 'student9@example.com', MD5('student129'), 'student', 1, 3, 'B');