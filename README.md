# 🗳️ Class Election Voting System

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)
![Database](https://img.shields.io/badge/Database-MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-Data%20Viz-FF6384?style=flat-square&logo=chartdotjs&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

A secure, real-time web application built with PHP and MySQL to manage student elections. The system enforces strict voter integrity, features a real-time administrative analytics dashboard, and automatically generates verifiable digital receipts with embedded QR codes.

---

## 📌 Project Overview

Traditional paper-based class elections are prone to miscounts, duplicate voting, and slow tabulation. This platform digitizes the entire workflow, separating the experience into a secure **Student Portal** and a moderated **Admin Dashboard**[cite: 28, 33]. 

Students log in using their Roll Numbers, are forced to change default passwords for security, and can either vote from a pre-approved list or nominate a custom candidate[cite: 31, 33, 36]. Administrators can monitor live voting metrics and approve or reject custom nominations on the fly[cite: 28].

---

## 🛠️ Key Technical Capabilities

* **Strict Voter Integrity:** Enforces a strict one-vote-per-student policy using database validation (`has_voted` flags). Attempting to submit a custom nomination or access the voting form after casting a ballot is instantly blocked[cite: 32, 33].
* **Forced Security Protocols:** First-time users logging in with the default password (`pass123`) are locked out of the voting booth and forcefully redirected to a password change portal featuring a dynamic password strength meter[cite: 31, 33].
* **Dynamic Receipt & QR Generation:** Upon a successful vote, the system generates a printable digital receipt containing a cryptographic-style QR code (via `qrcodejs`) linking to a verification URL[cite: 33].
* **Live Admin Analytics:** The admin dashboard utilizes `Chart.js` to render a responsive, auto-updating doughnut chart of the current vote distribution, total vote counts, and the leading candidate[cite: 28].
* **Custom Nomination Workflow:** Students can bypass the default ballot to request a custom candidate name. These requests sit in a "Pending" queue on the Admin dashboard, where they can be instantly approved (automatically casting the vote and updating the chart) or rejected[cite: 28, 32].

---

## 📁 Repository Structure

```text
Online-Voting-System/
├── admin.php               # Real-time analytics dashboard & moderation queue
├── admin_login.php         # Secure administrator authentication portal
├── change_password.php     # Forced user password reset with strength validation
├── custom_names.php        # Processing logic for custom candidate requests
├── dashboard.php           # Main student voting booth and QR receipt generator
├── db.php                  # Database connection handler
├── index.php               # Root redirector to the login portal
├── login.php               # Student roll-number authentication
└── logout.php              # Session termination script

---

## 🗄️ Relational Database Schema

The system relies on three primary tables within the `voting_db` database:

### 1. `users` (Voter Registry)

Tracks student authentication and voting status.

* `id` (INT, Primary Key)
* `username` (VARCHAR) - Student Roll Number.
* `password` (VARCHAR) - Plaintext or Hashed password.
* `role` (VARCHAR) - Access level.
* `has_voted` (BOOLEAN) - Prevents duplicate voting.
* `voted_for` (INT) - Foreign key to the candidate ID.
* `custom_name` (VARCHAR) - Requested custom candidate string.
* `custom_name_status` (ENUM: 'pending', 'approved', 'rejected').

### 2. `candidates` (The Ballot)

Tracks the official candidates and their tallies.

* `id` (INT, Primary Key)
* `name` (VARCHAR) - Candidate Name.
* `vote_count` (INT) - Live running tally of received votes.

### 3. `admins` (Moderators)

Tracks administrative access.

* `id` (INT, Primary Key)
* `username` (VARCHAR) - Admin login ID.
* `password_hash` (VARCHAR) - Secure admin password.

---

## 💻 Tech Stack

* **Backend Logic:** PHP 8.x (Session management, routing, validation)


* **Database Layer:** MySQL (`voting_db`)


* **Frontend UI:** Custom CSS / HTML5 (No heavy frameworks, uses Google Fonts 'Poppins')


* **Data Visualization:** Chart.js


* **Utilities:** QRCode.js (Client-side receipt generation)



---

## 🚀 Quick Start & Installation

### 1. Clone the Repository

```bash
git clone [https://github.com/niketsah007/Online-Voting-System.git](https://github.com/niketsah007/Online-Voting-System.git)
cd Online-Voting-System

```

### 2. Database Configuration

1. Open your MySQL interface (phpMyAdmin, HeidiSQL, etc.).
2. Create a new database named `voting_db`.


3. Import your SQL schema to create the `users`, `candidates`, and `admins` tables.
4. *Note on local setup:* The `db.php` file is currently configured for a local environment (like USBWebserver) with the default password set to `usbw`. Update these credentials if you are using Laragon or XAMPP:


```php
$servername = "localhost";
$username = "root";
$password = ""; // Change 'usbw' to empty for standard XAMPP/Laragon
$dbname = "voting_db";

```



### 3. Access the Portals

* **Student Login:** Navigate to `http://localhost/Online-Voting-System/login.php`.


* **Admin Login:** Navigate to `http://localhost/Online-Voting-System/admin_login.php`.



---

## 📄 License

This project is licensed under the **MIT License**. See the `LICENSE` file for full details.

---

## 👤 Author

**Niket Sah**

* B.Tech Computer Science Engineering (2027)
* Kathgodam, India
* GitHub: [@niketsah007](https://www.google.com/search?q=https://github.com/niketsah007)

```

```
