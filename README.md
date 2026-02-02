# 💉 VaccineCare – Digital Vaccination Management System

VaccineCare is a centralized web-based application designed to manage vaccination records digitally and ensure timely immunization through automated reminders. The system helps parents, healthcare workers (ANMs), and administrators track vaccination schedules efficiently, reducing missed doses and manual workload.

---

## 📌 Project Overview

Vaccination plays a crucial role in public health, but traditional paper-based systems suffer from data loss, inaccessibility, and lack of timely reminders. VaccineCare addresses these challenges by providing a secure, user-friendly, and automated digital platform for vaccination management.

The system enables parents to view their child’s vaccination schedule and receive reminders, while healthcare workers can update and verify vaccination records in real time.

---

## ❗ Problem Statement

Existing vaccination record systems are mostly manual and fragmented, leading to:
- Loss or damage of physical records
- Missed or delayed vaccinations due to lack of reminders
- No direct access for parents to vaccination details
- Increased workload on ANMs and healthcare staff
- Difficulty in verifying vaccination certificates

---

## 🎯 Objectives

- Develop a centralized digital platform for vaccination management  
- Provide parents with easy access to child vaccination records  
- Automate email/SMS reminders for upcoming vaccinations  
- Enable ANMs to update records efficiently  
- Generate digital vaccination certificates  
- Ensure data security through authentication and role-based access  

---

## 🛠️ Tech Stack

### Frontend
- HTML5  
- CSS3  
- JavaScript  
- AJAX  

### Backend
- PHP  

### Database
- MySQL  

### Server & Tools
- XAMPP (Apache, MySQL, PHP)  
- Brevo (Sendinblue) Email API  
- Cron Jobs (Automation)  
- Visual Studio Code  

### Deployment
- InfinityFree Hosting  

---

## 🧩 System Modules

- **User Management Module**  
  Handles registration, login, and role-based access (Parent, ANM, Admin).

- **Child Management Module**  
  Stores child details and links them with parents and assigned ANMs.

- **Vaccination Scheduling Module**  
  Automatically calculates vaccination dates based on date of birth.

- **Reminder Module**  
  Sends automated email/SMS reminders for upcoming or missed vaccinations.

- **Certificate Module**  
  Generates downloadable digital vaccination certificates.

---

## 🗂️ Database Design

The system uses a centralized MySQL database with tables for:
- Users (Parents, ANMs, Admins)
- Children
- Vaccines
- Vaccination Records
- Reminders  

Relationships are maintained using primary and foreign keys to ensure data integrity.

---

## ✅ Features

- Centralized digital vaccination records  
- Automated email/SMS reminders  
- Role-based access control  
- Secure authentication  
- Real-time record updates  
- Easy verification of vaccination status  

---

## 🧪 Testing & Validation

- Form validation using JavaScript  
- Backend validation using PHP  
- Database integrity testing using SQL queries  
- Email reminder testing using Brevo API  
- Manual testing of all modules  

---

## 📈 Results

- Reduced missed vaccinations  
- Improved data accuracy  
- Easy access to records for parents  
- Reduced manual workload for ANMs  
- Reliable and timely reminder notifications  

---

## 🚀 Future Scope

- SMS integration using telecom APIs  
- Mobile application version (Android/iOS)  
- Government health portal integration  
- QR-based certificate verification  
- Advanced analytics and reporting dashboard  

---

## 📄 How to Run the Project Locally

1. Install XAMPP  
2. Clone this repository  
3. Move the project folder to `htdocs`  
4. Import the MySQL database  
5. Start Apache and MySQL  
6. Access the project via `http://localhost/VaccineCare`

---

## 👩‍💻 Author

**G. Roopa**  
B.Tech – Computer Science Engineering  
RGUKT Basar  

---

## 📜 License

This project is developed for academic purposes.
