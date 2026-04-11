
# 🐢 Tortoise Conservation Management System - XAMPP Setup Guide

## Quick Start: Complete Installation & Setup

### **STEP 1: Prerequisites**
- **XAMPP** installed (download from https://www.apachefriends.org/)
- **MySQL/MariaDB** running via XAMPP
- **Apache** running via XAMPP
- **PHP 8+** (comes with XAMPP)

---

## **STEP 2: Installation Steps**

### **2.1 Start XAMPP Services**
1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
   - Both should show green status indicators ✅

### **2.2 Create Project Directory**
```bash
Navigate to: C:\xampp\htdocs\

Create folder: tortoise_management
```

**Result:** `C:\xampp\htdocs\tortoise_management\`

### **2.3 Copy Project Files**
1. Copy all PHP files to: `C:\xampp\htdocs\tortoise_management\`
   - `index.php`
   - `create.php`
   - `edit.php`
   - `delete.php`
   - `db_connect.php`

2. Create a `/database` subfolder: `C:\xampp\htdocs\tortoise_management\database\`

3. Place the SQL script there:
   - `fullDatabase.sql`

### **2.4 Import Database**

#### **Option A: Using phpMyAdmin (Recommended)**

1. Open browser → `http://localhost/phpmyadmin`
2. Click **Import** tab at the top
3. Click **Choose File**
4. Navigate to: `C:\xampp\htdocs\tortoise_management\database\fullDatabase.sql`
5. Click **Open**, then **Import**
6. Wait for success message ✅

#### **Option B: Using Command Line**

```bash
cd C:\xampp\mysql\bin

mysql -u root -p < "C:\xampp\htdocs\tortoise_management\database\fullDatabase.sql"

(Press Enter when prompted for password - it's empty by default)
```

### **2.5 Verify Database Created**

1. Go to `http://localhost/phpmyadmin`
2. In left sidebar, look for **tortoise_conservation_db**
3. Click to expand and verify tables:
   - ✅ ROLES
   - ✅ SPECIES_DICT
   - ✅ ENCLOSURE
   - ✅ USERS
   - ✅ TORTOISE_PROFILE
   - ✅ FEEDING_LOG
   - ✅ HEALTH_RECORD
   - ✅ BREEDING_RECORD
   - ✅ TASK_SCHEDULE
   - ✅ IOT_TELEMETRY
   - ✅ ALERTS

---

## **STEP 3: Test Database Connection**

1. Open: `http://localhost/tortoise_management/db_connect.php`

2. You should see a blank page (no errors = success ✅)

3. To enable test output:
   - Edit `db_connect.php`
   - Uncomment: `// echo "Connected successfully";`
   - Refresh browser
   - Should display: **"Connected successfully"**
   - Comment it back out when done

---

## **STEP 4: Launch the Application**

### **Access the Dashboard**
```
http://localhost/tortoise_management/index.php
```

Or simply:
```
http://localhost/tortoise_management/
```

### **Expected Features**

| Feature | URL | Description |
|---|---|---|
| 📊 Dashboard | `/index.php` | View all tortoises |
| ➕ Register Tortoise | `/create.php` | Add new tortoise |
| ✏️ Edit Tortoise | `/edit.php?id=1` | Modify existing tortoise |
| 🗑️ Delete Tortoise | `/delete.php?id=1` | Remove tortoise permanently |

---

## **STEP 5: Test Data**

Your database already includes **5 sample tortoises** with realistic data:

1. **Galapagos Giant #1** (Healthy, 185.5 kg)
2. **Galapagos Giant #2** (Healthy, 150.2 kg)
3. **African Spurred #3** (Under Treatment, 45.3 kg)
4. **Aldabra Giant #4** (Healthy, 120 kg)
5. **Ploughshare Hatchling #5** (Quarantine, 1.2 kg)

Visit `http://localhost/tortoise_management/` to see them displayed.

---

## **Troubleshooting**

### **"Connection refused" or "Can't connect to database"**
- ✔️ Verify MySQL service is **Running** in XAMPP Control Panel
- ✔️ Check port: MySQL should use **Port 3306**
- ✔️ Verify database name: `tortoise_conservation_db`

### **"Table doesn't exist"**
- ✔️ Re-import the SQL file in phpMyAdmin
- ✔️ Ensure no errors occurred during import

### **"Access denied for user 'root'@'localhost'"**
- ✔️ In `db_connect.php`, verify:
  - `$db_user = 'root'`
  - `$db_pass = ''` (empty, unless you set a password)

### **Blank page or 404 errors**
- ✔️ Verify file location: `C:\xampp\htdocs\tortoise_management\`
- ✔️ Check Apache is running
- ✔️ Ensure file extensions are `.php`

---

## **File Structure**

```
C:\xampp\htdocs\tortoise_management\
│
├── index.php                 [Dashboard - View all tortoises]
├── create.php                [Register/Edit tortoise]
├── edit.php                  [Edit redirect]
├── delete.php                [Delete confirmation & execution]
├── db_connect.php            [Database connection bridge]
│
└── database/
    └── fullDatabase.sql      [Complete schema + dummy data]
```

---

## **Key Credentials**

| Component | Value |
|---|---|
| Database Host | `localhost` |
| Database Name | `tortoise_conservation_db` |
| Database User | `root` |
| Database Password | (empty) |
| phpMyAdmin URL | `http://localhost/phpmyadmin` |

---

## **Next Steps (Future Enhancements)**

After verifying the system works, you can:

1. ✨ Add authentication/login system
2. ✨ Implement feeding logs management
3. ✨ Build health records dashboard
4. ✨ Create breeding program reports
5. ✨ Add IoT telemetry visualization
6. ✨ Set up alert notifications
7. ✨ Export data to PDF/Excel

---

## **Support**

For issues or questions:
1. Check error logs: `C:\xampp\logs\php_error.log`
2. Review browser console: Press `F12` → Console tab
3. Use phpMyAdmin to verify data integrity

---

**Happy Tortoise Management! 🐢✨**
