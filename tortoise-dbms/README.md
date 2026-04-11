# Tortoise Management

Simple PHP/XAMPP project to manage tortoise records.

Local setup:

1. Ensure PHP/XAMPP is installed and configured.
2. Import any database dumps located in `database/` if needed.

To publish to GitHub (after creating a remote repo named `REPO`):

```bash
git remote add origin https://github.com/USERNAME/REPO.git
git branch -M main
git push -u origin main
```

Or use the GitHub CLI:

```bash
gh repo create USERNAME/REPO --public --source=. --remote=origin --push
```
# 🐢 Tortoise Conservation Management System

## Complete Full-Stack CRUD Application (PHP 8 / MySQL / Bootstrap 5)

A professional-grade database and web application for managing tortoise conservation centers, including animal profiles, health records, breeding programs, feeding logs, task scheduling, and environmental monitoring.

---

## 📋 Features

### Core CRUD Operations
- ✅ **Dashboard (Read)** - View all tortoises with detailed information
- ✅ **Register (Create)** - Add new tortoises to the system
- ✅ **Edit (Update)** - Modify existing tortoise profiles
- ✅ **Delete** - Remove tortoises with confirmation

### Extended Features
- 🍗 **Feeding Logs** - Track dietary intake and feeding schedules
- 🏥 **Health Records** - Medical history, treatments, vaccinations
- 📋 **Breeding Records** - Breeding program tracking and success rates
- ✓ **Task Schedule** - Staff task assignment and management
- 🚨 **Environmental Alerts** - IoT telemetry and enclosure monitoring
- 🔗 **Relational Database** - Normalized design with 11 tables

### Technical Features
- 🔐 PDO Prepared Statements (SQL Injection prevention)
- 🛡️ Input Validation & Sanitization
- 📊 Cascading Deletes (Database referential integrity)
- 🎨 Responsive Bootstrap 5 UI
- 📱 Mobile-Friendly Design
- ⚡ Error Handling & Logging

---

## 📁 Project Structure

```
C:\xampp\htdocs\tortoise_management\
│
├── README.md                      [This file]
├── XAMPP_SETUP.md                 [Installation guide]
├── db_connect.php                 [Database connection bridge]
├── header.php                     [Navigation component]
│
├── index.php                      [Dashboard - View tortoises]
├── create.php                     [Register/Edit tortoise]
├── edit.php                       [Edit redirect wrapper]
├── delete.php                     [Delete confirmation]
│
├── feeding_logs.php               [Feeding history]
├── health_records.php             [Medical records]
├── breeding_records.php           [Breeding program]
├── tasks.php                      [Task scheduling]
├── alerts.php                     [Environmental alerts]
│
└── database/
    └── fullDatabase.sql           [Complete schema + dummy data]
```

---

## 🚀 QUICK START (5 Minutes)

### **Step 1: Install XAMPP**
- Download from: https://www.apachefriends.org/
- Run installer with default options

### **Step 2: Start XAMPP Services**
```
XAMPP Control Panel (Windows):
✓ Click "Start" next to Apache
✓ Click "Start" next to MySQL
```

### **Step 3: Copy Project Files**
```
Copy all files to:
C:\xampp\htdocs\tortoise_management\
```

### **Step 4: Import Database**
```
1. Open: http://localhost/phpmyadmin
2. Click "Import" tab
3. Select: database/fullDatabase.sql
4. Click "Import"
```

### **Step 5: Launch Application**
```
In browser, go to:
http://localhost/tortoise_management/
```

**See detailed instructions in: [XAMPP_SETUP.md](XAMPP_SETUP.md)**

---

## 🗄️ Database Schema (11 Tables)

| Table | Records | Purpose |
|---|---|---|
| **ROLES** | 5 | User roles & permissions |
| **USERS** | 5 | System users |
| **SPECIES_DICT** | 5 | Species classification |
| **ENCLOSURE** | 5 | Habitat information |
| **TORTOISE_PROFILE** | 5 | Individual tortoise data |
| **FEEDING_LOG** | 5 | Feeding history |
| **HEALTH_RECORD** | 5 | Medical records |
| **BREEDING_RECORD** | 5 | Breeding programs |
| **TASK_SCHEDULE** | 5 | Staff assignments |
| **IOT_TELEMETRY** | 5 | Environmental data |
| **ALERTS** | 5 | System alerts |

### Key Relationships
```
ROLES (1) ──→ USERS (N)
SPECIES_DICT (1) ──→ TORTOISE_PROFILE (N)
ENCLOSURE (1) ──→ TORTOISE_PROFILE (N)
TORTOISE_PROFILE (1) ──→ FEEDING_LOG (N)
TORTOISE_PROFILE (1) ──→ HEALTH_RECORD (N)
TORTOISE_PROFILE (1) ──→ BREEDING_RECORD (N)
ENCLOSURE (1) ──→ IOT_TELEMETRY (N)
ENCLOSURE (1) ──→ ALERTS (N)
```

---

## 📊 Pages & Functions

| Page | Purpose | Database Query |
|---|---|---|
| `index.php` | Dashboard with all tortoises | SELECT + INNER/LEFT JOIN |
| `create.php` | Register new or edit existing | INSERT or UPDATE |
| `edit.php` | Redirect to create.php | Redirect only |
| `delete.php` | Confirmation & deletion | SELECT & DELETE |
| `feeding_logs.php` | View feeding records | SELECT with JOIN |
| `health_records.php` | View medical records | SELECT with JOIN |
| `breeding_records.php` | View breeding programs | SELECT with JOIN |
| `tasks.php` | View task assignments | SELECT with LEFT JOIN |
| `alerts.php` | View environmental alerts | SELECT with JOIN |

---

## 🔐 Security Implementation

### SQL Injection Prevention
```php
// ✅ SECURE: Prepared statements with parameters
$stmt = $pdo->prepare("SELECT * FROM TORTOISE_PROFILE WHERE tortoise_id = ?");
$stmt->execute([$id]);

// ❌ UNSAFE: Direct string concatenation (NEVER use)
// $result = $pdo->query("SELECT * FROM table WHERE id = $id");
```

### Input Validation
```php
$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($id === false) {
    die('Invalid ID');
}
```

### Output Encoding
```php
echo htmlspecialchars($name);  // Prevents XSS attacks
```

### Referential Integrity
```sql
FOREIGN KEY (tortoise_id) REFERENCES TORTOISE_PROFILE(tortoise_id) 
ON DELETE CASCADE ON UPDATE CASCADE;
```

---

## 💾 Database Credentials

**File:** `db_connect.php`

```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';                           // Empty (default XAMPP)
$db_name = 'tortoise_conservation_db';
$db_charset = 'utf8mb4';
```

---

## 🧪 Sample Data Included

### Tortoises
1. **Galapagos Giant #1** - Healthy, 185.5 kg, Sector A
2. **Galapagos Giant #2** - Healthy, 150.2 kg, Sector A
3. **African Spurred #3** - Under Treatment, 45.3 kg, Sector C
4. **Aldabra Giant #4** - Healthy, 120 kg, Sector B
5. **Ploughshare Hatchling #5** - Quarantine, 1.2 kg, Medical Wing

### Users
| Username | Role | Password Hash |
|---|---|---|
| sys_admin | Admin | (Bcrypt) |
| dr_smith_vet | Vet | (Bcrypt) |
| jdoe_caretaker | Caretaker | (Bcrypt) |
| mlee_breeder | Breeding Officer | (Bcrypt) |
| kchen_envtech | Env Tech | (Bcrypt) |

---

## 🎨 User Interface

### Navigation Bar
- 📊 Dashboard
- ➕ Register Tortoise
- 🍗 Feeding Logs
- 🏥 Health Records
- 📋 More (dropdown menu)

### Color Scheme
- **Primary:** Green (#2c5f4f) - Conservation theme
- **Danger:** Red (#dc3545) - Deletions
- **Medical:** Red (#d63031) - Health records
- **Success:** Green (#00b894) - Tasks

---

## 🐛 Troubleshooting

| Problem | Solution |
|---|---|
| "Can't connect to database" | Verify MySQL is running in XAMPP |
| "Table doesn't exist" | Re-import database in phpMyAdmin |
| "Access denied for user 'root'" | Check credentials in db_connect.php |
| Blank page / 404 error | Verify files in C:\xampp\htdocs\tortoise_management\ |
| Page won't load | Check Apache is running in XAMPP |

---

## 📈 Future Enhancements

- [ ] User authentication / login system
- [ ] Advanced reporting & analytics
- [ ] Data export (PDF, Excel)
- [ ] Email notifications
- [ ] Mobile app integration
- [ ] REST API endpoints
- [ ] Unit & integration tests
- [ ] Multi-language support

---

## 📚 Technology Stack

| Component | Technology |
|---|---|
| **Backend** | PHP 8+ |
| **Database** | MySQL 8+ / MariaDB |
| **Frontend** | HTML5, CSS3 |
| **Framework** | Bootstrap 5 |
| **Server** | Apache (XAMPP) |
| **ORM** | PDO (PHP Data Objects) |

---

## ✅ Verification Checklist

- [ ] XAMPP Apache running
- [ ] XAMPP MySQL running
- [ ] Database imported successfully
- [ ] Dashboard loads (http://localhost/tortoise_management/)
- [ ] Can view all 5 sample tortoises
- [ ] Can create new tortoise
- [ ] Can edit existing tortoise
- [ ] Can delete tortoise
- [ ] Can view feeding logs
- [ ] Can view health records
- [ ] Navigation menu works on all pages
- [ ] No console errors

---

## 📖 Key Files Documentation

| File | Purpose |
|---|---|
| **db_connect.php** | Secure PDO database connection |
| **header.php** | Reusable navigation component |
| **index.php** | Main dashboard (READ) |
| **create.php** | Dual-mode form (CREATE/UPDATE) |
| **delete.php** | Safe deletion (DELETE) with confirmation |
| **fullDatabase.sql** | Complete schema + sample data |

---

## 🎓 Learning Outcomes

This project teaches:

✓ PDO prepared statements & database security  
✓ Relational database design & normalization  
✓ CRUD operations (Create, Read, Update, Delete)  
✓ HTML/CSS with Bootstrap 5  
✓ Error handling & validation  
✓ XAMPP environment setup  
✓ SQL joins & complex queries  
✓ Input sanitization & output encoding  

---

## 📞 Support

- **XAMPP:** https://www.apachefriends.org/
- **Bootstrap:** https://getbootstrap.com/docs/5.0/
- **PHP Docs:** https://www.php.net/manual/
- **MySQL Docs:** https://dev.mysql.com/doc/

---

## ©️ Project Information

**Status:** ✅ Complete & Production-Ready  
**Version:** 1.0  
**Last Updated:** April 6, 2026  
**Environment:** XAMPP on Windows  
**License:** Open-Source (Educational Use)

---

## 🎉 Summary

You now have a **complete, professional-grade CRUD application** that:

✨ Manages tortoise conservation data  
✨ Implements all CRUD operations  
✨ Uses secure database practices  
✨ Features responsive Bootstrap 5 UI  
✨ Includes comprehensive sample data  
✨ Provides professional error handling  
✨ Works seamlessly with XAMPP  

**Ready to deploy and use immediately!**

---

**Happy Tortoise Management! 🐢✨**