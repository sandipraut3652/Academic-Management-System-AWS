Eduportal – AWS Powered Academic Management Portal

📌 Overview

ScholarPoint is a cloud-based Academic Management Portal developed for the Electronics & Computer Engineering Department at Parikrama College of Engineering. The project demonstrates the deployment of a PHP web application on Amazon Web Services (AWS) using a production-style cloud architecture.

The portal provides separate dashboards for Teachers and Students, enabling efficient academic management, communication, and resource sharing.

---

🚀 Features

Student Module

- Student Registration
- Teacher Approval System
- Access Study Materials
- Submit Assignments
- View Announcements
- Join Online Meetings
- View Attendance

Teacher Module

- Approve Student Registrations
- Manage Classes & Subjects
- Upload Study Materials
- Post Announcements
- Schedule Online Meetings
- Track Student Attendance

---

☁️ AWS Architecture

- Amazon EC2 (Ubuntu 22.04)
- Amazon RDS (MySQL 8.0)
- Amazon VPC
- Public & Private Subnets
- Application Load Balancer (ALB)
- AWS IAM
- Amazon CloudWatch
- Security Groups
- Internet Gateway

---

🛠 Technology Stack

Backend

- PHP

Frontend

- HTML
- CSS
- JavaScript

Database

- MySQL 8.0 (AWS RDS)

Cloud

- AWS EC2
- AWS RDS
- AWS VPC
- AWS ALB
- AWS CloudWatch
- AWS IAM

Web Server

- Apache2
- Ubuntu 22.04

---

📂 Project Architecture

Client

↓

Application Load Balancer (ALB)

↓

EC2 Instance (Apache + PHP)

↓

AWS RDS MySQL (Private Subnet)

---

🔒 Security Features

- RDS deployed in Private Subnet
- Security Group Chaining (ALB → EC2 → RDS)
- Password Hashing
- SQL Injection Prevention using Prepared Statements
- IAM Role-based Access
- Apache Security Configuration
- XSS Protection

---

📊 Monitoring

- EC2 CPU Utilization
- Memory Usage
- Disk Usage
- Network Traffic
- Apache Access Logs
- Apache Error Logs
- RDS Database Connections

Monitored using Amazon CloudWatch Dashboard.

---

🚀 Deployment Steps

1. Launch EC2 Instance
2. Install Apache, PHP & MySQL Client
3. Deploy PHP Application
4. Create RDS MySQL Database
5. Connect Application to RDS
6. Configure VPC & Security Groups
7. Configure Application Load Balancer
8. Install CloudWatch Agent
9. Create Monitoring Dashboard

---

📷 Screenshots

Add screenshots of:

- EC2 Instance
- VPC
- RDS
- Application Load Balancer
- CloudWatch Dashboard
- Login Page
- Teacher Dashboard
- Student Dashboard

---

📈 Future Enhancements

- Amazon S3 for File Storage
- Auto Scaling Group
- HTTPS with SSL Certificate
- AWS WAF
- Email Notifications using Amazon SES
- CI/CD using AWS CodePipeline
- Amazon ElastiCache (Redis)

---

👨‍💻 Author

Sandip Kalyan Raut

Electronics & Computer Engineering

Parikrama College of Engineering

Academic Year: 2025–2026

---

⭐ If you like this project

Give this repository a ⭐ and feel free to fork it for learning purposes.
