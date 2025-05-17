# 📅 SmartSchedule: University Course Scheduling System

SmartSchedule is a PHP + MySQL-based web application that streamlines the scheduling of university courses, instructors, classrooms, and exams. It supports role-based access for administrators, lecturers, and students, with an intuitive dashboard and automated conflict detection.

---

## 🚀 Features

### 👤 Role-Based Access
- **Admin**
  - Create and manage schedules for all departments
  - Assign courses to teachers
  - Detect and resolve schedule conflicts
- **Teacher**
  - View assigned schedules
  - Reschedule lectures within constraints
  - Set availability and section coverage
- **Student**
  - View real-time class/test/assignment schedules
  - Filter by course, department, or section

### 📅 Scheduling Engine
- Create course schedules with room, section, and time slot
- Schedule tests and assignments
- Detect conflicts (room overlap, teacher double-booking, etc.)
- Visual calendar view with FullCalendar.js

### 📸 Extras
- Responsive Bootstrap 5 UI
- Secure login system with active/inactive account control
- Admin analytics (student/teacher/schedule counts)
- Image upload support with file validation (PDFs, cover images, etc.)
- Logging system for actions like CREATE, UPDATE, DELETE

---

## 🛠️ Tech Stack

- **Backend**: PHP (OOP, procedural)
- **Database**: MySQL
- **Frontend**: HTML, CSS, Bootstrap 5, FullCalendar.js
- **Security**: Session management, hashed passwords

---


