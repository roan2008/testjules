# Installation Guide - Rocket Production Management System

## 1. ความต้องการของระบบ (Prerequisites)

### 1.1 ความต้องการขั้นต่ำ
- **Operating System**: Windows 10/11 (64-bit)
- **RAM**: 8 GB
- **Storage**: 10 GB พื้นที่ว่าง
- **Network**: การเชื่อมต่ออินเทอร์เน็ตสำหรับการดาวน์โหลด

### 1.2 Software Requirements
- **XAMPP 8.0+** หรือใหม่กว่า
- **PHP 8.0+**
- **MySQL 8.0+**
- **Web Browser**: Chrome, Firefox, หรือ Edge เวอร์ชันล่าสุด

## 2. การติดตั้ง XAMPP

### 2.1 ดาวน์โหลด XAMPP
1. เข้าไปที่ https://www.apachefriends.org/
2. คลิก "Download" สำหรับ Windows
3. เลือกเวอร์ชัน PHP 8.0 หรือใหม่กว่า
4. ดาวน์โหลดไฟล์ installer

### 2.2 ติดตั้ง XAMPP
1. รันไฟล์ `xampp-windows-x64-8.x.x-installer.exe`
2. เลือกภาษา English
3. เลือก Components ที่ต้องการ:
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP
   - ✅ phpMyAdmin
4. เลือกโฟลเดอร์ติดตั้ง (แนะนำ: `C:\xampp`)
5. คลิก "Next" และ "Install"
6. รอการติดตั้งให้เสร็จสิ้น

### 2.3 เริ่มต้น XAMPP Services
1. เปิด XAMPP Control Panel
2. Start Apache และ MySQL services
3. ตรวจสอบว่า Status เป็นสี เขียว

## 3. การตั้งค่าฐานข้อมูล

### 3.1 สร้างฐานข้อมูล
#### วิธีที่ 1: ใช้ phpMyAdmin
1. เปิดเบราว์เซอร์ไปที่ `http://localhost/phpmyadmin`
2. คลิก "New" ในแถบด้านซ้าย
3. ป้อนชื่อฐานข้อมูล: `rocketprod`
4. เลือก Collation: `utf8mb4_general_ci`
5. คลิก "Create"

#### วิธีที่ 2: ใช้ Command Line
```bash
# เปิด Command Prompt และรันคำสั่ง
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE rocketprod CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### 3.2 Import Database Schema
1. คัดลอกโฟลเดอร์โปรเจค `testjules` ไปยัง `C:\xampp\htdocs\`
2. เปิด Command Prompt
3. รันคำสั่ง:
```bash
cmd.exe /c "C:\xampp\mysql\bin\mysql.exe -u root rocketprod < C:\xampp\htdocs\testjules\sql\schema_mysql.sql"
```

### 3.3 ตรวจสอบการติดตั้ง
1. เปิดเบราว์เซอร์ไปที่ `http://localhost/testjules/test_db.php`
2. ควรเห็นข้อความ "Database connection successful!"
3. และรายชื่อตารางในฐานข้อมูล

## 4. การตั้งค่า Configuration

### 4.1 ตรวจสอบไฟล์ config.php
ไฟล์ `config.php` ควรมีการตั้งค่าดังนี้:
```php
<?php
return [
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'rocketprod',
        'username' => 'root',
        'password' => '',
    ],
];
?>
```

### 4.2 ตั้งค่าไฟล์ Virtual Host (Optional)
หากต้องการใช้ domain name แทน localhost:

1. แก้ไขไฟล์ `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. เพิ่มบรรทัดดังนี้:
```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/testjules/public"
    ServerName rocketprod.local
</VirtualHost>
```

3. แก้ไขไฟล์ `C:\Windows\System32\drivers\etc\hosts`
4. เพิ่มบรรทัด:
```
127.0.0.1 rocketprod.local
```

## 5. การทดสอบระบบ

### 5.1 ทดสอบการเข้าถึงระบบ
1. เปิดเบราว์เซอร์ไปที่ `http://localhost/testjules/public/`
2. ควรเห็นหน้า Login

### 5.2 ทดสอบการเข้าสู่ระบบ
ใช้ข้อมูลผู้ใช้ตัวอย่าง:
- **Username**: `admin`
- **Password**: `password`

หรือ
- **Username**: `operator1`
- **Password**: `password`

### 5.3 ทดสอบฟีเจอร์พื้นฐาน
1. เข้าสู่ระบบสำเร็จ → ควรเห็นหน้า Dashboard
2. ดู Production Orders → ควรเห็นรายการ Orders ตัวอย่าง
3. สร้าง Order ใหม่ → ทดสอบการเพิ่มข้อมูล
4. ออกจากระบบ → ทดสอบการ Logout

## 6. การแก้ไขปัญหาเบื้องต้น

### 6.1 Apache ไม่สามารถ Start ได้
**สาเหตุ**: Port 80 ถูกใช้งานโดยโปรแกรมอื่น
**วิธีแก้**:
1. เปิด XAMPP Control Panel
2. คลิก "Config" ข้าง Apache
3. เลือก "httpd.conf"
4. แก้ไข `Listen 80` เป็น `Listen 8080`
5. แก้ไข `ServerName localhost:80` เป็น `ServerName localhost:8080`
6. บันทึกและ Restart Apache

### 6.2 MySQL ไม่สามารถ Start ได้
**สาเหตุ**: Port 3306 ถูกใช้งาน
**วิธีแก้**:
1. เปิด XAMPP Control Panel
2. คลิก "Config" ข้าง MySQL
3. เลือก "my.ini"
4. แก้ไข `port=3306` เป็น `port=3307`
5. บันทึกและ Restart MySQL

### 6.3 เข้าเว็บไซต์ไม่ได้
**ตรวจสอบ**:
1. Apache service ทำงานหรือไม่
2. URL ถูกต้องหรือไม่
3. Firewall blocking หรือไม่
4. ไฟล์อยู่ในโฟลเดอร์ `htdocs` หรือไม่

### 6.4 Database Connection Error
**ตรวจสอบ**:
1. MySQL service ทำงานหรือไม่
2. ฐานข้อมูล `rocketprod` มีอยู่หรือไม่
3. การตั้งค่าใน `config.php` ถูกต้องหรือไม่
4. Schema import สำเร็จหรือไม่

## 7. การ Backup และ Restore

### 7.1 Backup Database
```bash
C:\xampp\mysql\bin\mysqldump.exe -u root rocketprod > backup_YYYYMMDD.sql
```

### 7.2 Restore Database
```bash
C:\xampp\mysql\bin\mysql.exe -u root rocketprod < backup_YYYYMMDD.sql
```

---

**หากพบปัญหาในการติดตั้ง**: กรุณาติดต่อทีมสนับสนุนเทคนิค  
**Email**: support@rocketprod.local  
**Documentation Version**: 1.0
