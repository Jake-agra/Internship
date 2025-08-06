# Real Estate Management System

A PHP-based real estate management system with user authentication and property management features.

## 📋 Prerequisites

Before setting up this project, ensure you have the following installed:

- **XAMPP** (or similar LAMP/WAMP stack)
  - PHP 7.4 or higher
  - MySQL 5.7 or higher
  - Apache Web Server
- **Git** (for cloning the repository)
- **Web Browser** (Chrome, Firefox, Safari, etc.)

## 🚀 Getting Started

### Step 1: Clone the Repository

```bash
# Clone the repository
git clone <your-repository-url>

# Navigate to the project directory
cd RealEsate
```

### Step 2: Set Up Local Server Environment

1. **Start XAMPP Services:**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL** services
   - Ensure both services are running (green status)

2. **Move Project to Web Directory:**
   ```bash
   # Copy the project to your XAMPP htdocs directory
   # Windows: C:\xampp\htdocs\
   # macOS: /Applications/XAMPP/htdocs/
   # Linux: /opt/lampp/htdocs/
   
   cp -r RealEsate /path/to/xampp/htdocs/
   ```

### Step 3: Database Setup

#### Option 1: Import the Complete Database (Recommended)

1. **Access phpMyAdmin:**
   - Open your browser and go to `http://localhost/phpmyadmin`
   - Login with username: `root` (leave password empty for default XAMPP)

2. **Create Database:**
   - Click "New" in the left sidebar
   - Create a database named `realestate`
   - Set collation to `utf8_general_ci`

3. **Import Database:**
   - Select the `realestate` database
   - Click on the "Import" tab
   - Choose file: `realestate.sql` (located in project root)
   - Click "Go" to import

#### Option 2: Manual Database Setup

If you prefer to set up the database manually:

1. Create the database as described above
2. Import the setup file: `Database/setup.sql`
3. This will create the basic table structure

### Step 4: Configure Database Connection

The database configuration is already set up in `Database/connection.php` with default XAMPP settings:

```php
$dbhost = "localhost";
$dbname = "realestate";
$dbuser = "root";
$dbpassword = "";
$dbport = 3306;
```

**If your setup is different, modify these values accordingly.**

### Step 5: Access the Application

1. **Open your web browser**
2. **Navigate to:** `http://localhost/RealEsate/`
3. **You should see the application homepage**

## 📁 Project Structure

```
RealEsate/
├── Database/
│   ├── connection.php      # Database connection configuration
│   └── setup.sql          # Database schema setup
├── includes/
│   ├── header.php         # Common header component
│   ├── footer.php         # Common footer component
│   ├── 
├── bootstrap-5.3.7-dist/  # Bootstrap CSS framework
├── index.php              # Homepage
├── login.php              # User login page
├── register.php           # User registration page
├── index.css              # Custom styles
├── realestate.sql         # Complete database export
└── README.md              # This file
```

## 🔧 Configuration

### Database Configuration
- **Host:** localhost
- **Database:** realestate
- **Username:** root
- **Password:** (empty for default XAMPP)
- **Port:** 3306

### Web Server Configuration
- **Document Root:** `/path/to/xampp/htdocs/RealEsate/`
- **URL:** `http://localhost/RealEsate/`

## 🚨 Troubleshooting

### Common Issues

1. **"Connection failed" Error:**
   - Ensure MySQL service is running in XAMPP
   - Verify database credentials in `Database/connection.php`
   - Check if `realestate` database exists

2. **404 Not Found:**
   - Verify the project is in the correct htdocs directory
   - Check Apache service is running
   - Ensure correct URL: `http://localhost/RealEsate/`

3. **Database Import Failed:**
   - Check file permissions on `realestate.sql`
   - Ensure sufficient MySQL privileges
   - Try importing smaller chunks if file is large

4. **PHP Errors:**
   - Check PHP version compatibility (7.4+)
   - Enable error reporting in PHP configuration
   - Check Apache error logs

### Getting Help

If you encounter issues:
1. Check XAMPP error logs
2. Enable PHP error reporting
3. Verify all prerequisites are met
4. Ensure proper file permissions

## 📝 Usage

1. **Registration:** Create a new user account via `register.php`
2. **Login:** Access the system through `login.php`
3. **Navigation:** Use the main interface to manage real estate properties

##  License

This project is for educational/development purposes. Please ensure proper licensing for production use.

---

**Happy Coding! 🏠✨**
