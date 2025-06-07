# Rocket Production Management System

This repository contains a prototype PHP application for managing rocket tube preparation (MC02) data. It includes basic user authentication and data entry pages. See `sql/schema_mysql.sql` for the database schema.

## 📋 Documentation

Complete documentation is available in the `/docs` folder:

- **[System Overview](docs/01-overview/system-overview.md)** - วัตถุประสงค์และขอบเขตระบบ
- **[Installation Guide](docs/02-installation/installation-guide.md)** - คู่มือการติดตั้งระบบ  
- **[User Manual](docs/03-user-manual/user-manual.md)** - คู่มือการใช้งานสำหรับผู้ใช้

## 🚀 Quick Start

1. Install XAMPP with PHP 8.0+
2. Clone this project to `C:\xampp\htdocs\testjules`
3. Import database: `sql/schema_mysql.sql`
4. Access: `http://localhost/testjules/public/`
5. Login: username: `admin`, password: `password`

## 🏗️ Project Structure

```
testjules/
├── config.php              # Database configuration
├── public/                  # Web accessible files
│   ├── index.php           # Dashboard
│   ├── login.php           # User authentication
│   ├── create_order.php    # Create new orders
│   ├── edit_order.php      # Edit existing orders
│   ├── view_order.php      # View order details
│   └── assets/             # CSS, JS, images
├── src/                    # PHP classes
│   └── Database.php        # Database connection
├── sql/                    # Database schemas
│   ├── schema.sql          # MS SQL Server version
│   └── schema_mysql.sql    # MySQL version
└── docs/                   # Documentation
    ├── 01-overview/
    ├── 02-installation/
    └── 03-user-manual/
```

## 🔐 Default Users

- **Admin**: username: `admin`, password: `password`
- **Operator**: username: `operator1`, password: `password`

---

**For detailed installation and usage instructions, please refer to the documentation in the `/docs` folder.**

