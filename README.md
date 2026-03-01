🗳️ Online Voting System

![PHP](https://img.shields.io/badge/PHP-8.x-blue)
![MySQL](https://img.shields.io/badge/MySQL-Database-orange)
![JavaScript](https://img.shields.io/badge/JavaScript-Frontend-yellow)
![CSS3](https://img.shields.io/badge/CSS3-Styling-blue)

A secure and interactive role-based Online Voting System built using:

PHP (Backend Logic)

MySQL (Database)

HTML5 & CSS3 (UI Design)

JavaScript (Interactivity & Dynamic Behavior)

This system allows administrators to create and manage elections, candidates, and users, while voters can securely cast their vote and view live results.

🚀 Features
🔐 Role-Based Authentication

Admin login

Voter login

Session-based access control

Secure password hashing using password_hash()

👨‍💼 Admin Capabilities

Create multiple elections

Set election status (Upcoming / Ongoing / Closed)

Add and manage candidates per election

Create and manage user accounts (Admin & Voter)

View live election results

Delete users (with self-protection logic)

🗳️ Voter Capabilities

View active election

Select candidate via interactive card UI

One vote per election (database enforced)

View live vote results with percentage bars

Secure session-based voting

🎨 User Interface

Clean modern dark-themed design

Card-based layouts

Interactive buttons and hover effects

Responsive layout for different screen sizes

Dynamic JavaScript vote selection

Visual vote percentage progress bars

🧠 System Architecture
Database Tables

users (Admin & Voter roles)

elections

candidates

votes

Security Measures

Password hashing (PASSWORD_DEFAULT)

Prepared statements (SQL injection protection)

Session-based authentication

Unique vote constraint per user per election

🛠️ Installation (Local Setup)

Install XAMPP

Place project folder inside:

C:\xampp\htdocs\

Start Apache and MySQL

Create a database in phpMyAdmin

Import the provided SQL file

Update config.php with database credentials

Visit:

http://localhost/your-folder-name
📂 Folder Structure
admin/
voter/
includes/
assets/
    css/
    js/
config.php
index.php
login.php
logout.php
📈 Future Improvements

Edit/Delete candidates

Password reset feature

Email verification

Election time auto-control

Chart.js result graphs

Audit logs

API-based architecture

📚 Educational Purpose

This project was built as a full-stack web development exercise to demonstrate:

Backend development with PHP

Database integration with MySQL

Authentication & authorization systems

Role-based access control

Secure voting logic implementation

Clean UI/UX design

👨‍💻 Author

Edwin


Live Test

http://electionsystem.ct.ws

test logs
san
1234
