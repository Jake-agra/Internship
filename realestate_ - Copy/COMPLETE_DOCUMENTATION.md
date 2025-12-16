# 🏠 Real Estate Website - Complete Documentation

**Professional Real Estate Platform** - A comprehensive property management system similar to Zillow/Realtor with advanced features, role-based dashboards, and production-ready architecture.

---

## 🚀 **QUICK START - READY IN 5 MINUTES**

### 1. **Setup Database**
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create database: `realestate`
3. Import: `Database/realestate_complete.sql`

### 2. **Configure Connection**
Edit `Database/connection.php`:
```php
'host' => 'localhost',
'database' => 'realestate',
'username' => 'root',
'password' => '',
```

### 3. **Install Dependencies**
```bash
cd composer
composer install
```

### 4. **Access Your Website**
- **🌐 Website**: http://localhost/realestate_/
- **👑 Admin Panel**: http://localhost/realestate_/admin/
- **🧑‍💼 Agent Panel**: http://localhost/realestate_/agent/
- **👤 Client Panel**: http://localhost/realestate_/client/
- **🔐 Default Login**: admin@realestate.com / password

### 5. **Environment Configuration (Optional)**
Create `.env` file for email and advanced settings:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
FROM_EMAIL=noreply@realestate.com
FROM_NAME=Real Estate
ADMIN_EMAIL=admin@realestate.com
COMPANY_NAME=Real Estate Company
```

---

## ✨ **COMPREHENSIVE FEATURES**

### 🏡 **Frontend Features (Enhanced)**
- ✅ **Advanced Homepage** - Hero section, multi-filter search, featured properties with sorting
- ✅ **Smart Property Search** - Price range, bedrooms, bathrooms, area, year built, parking
- ✅ **Property Listings** - Grid/list view, sorting by price/date/popularity, advanced pagination
- ✅ **Property Details** - Professional image galleries, virtual tour support, contact forms
- ✅ **User System** - Registration, login, profile management with saved searches
- ✅ **Bookmarks System** - Save and manage favorite properties
- ✅ **Review System** - Property reviews and star ratings
- ✅ **Contact System** - Inquiry forms with property-specific messaging
- ✅ **Responsive Design** - Mobile-first Bootstrap 5 with modern animations

### 👑 **Admin Panel (Professional)**
- ✅ **Analytics Dashboard** - Real-time statistics, charts, performance metrics
- ✅ **Property Management** - Add, edit, delete with rich media support
- ✅ **Advanced Image Manager** - Drag & drop upload, thumbnail generation, bulk operations
- ✅ **User Management** - Manage users, roles, permissions with detailed profiles
- ✅ **Inquiry Management** - Handle customer inquiries with status tracking
- ✅ **Reports & Analytics** - Property views, user behavior, conversion tracking
- ✅ **System Settings** - Configure website preferences and features

### 🧑‍💼 **Agent Panel (Role-Based)**
- ✅ **Agent Dashboard** - Personal property management and client communication
- ✅ **Property Assignment** - Manage assigned properties and leads
- ✅ **Client Interaction** - Handle inquiries and manage client relationships

### 👤 **Client Panel (User Dashboard)**
- ✅ **Personal Dashboard** - Overview of activities and saved items
- ✅ **Browse Properties** - Advanced search and filtering capabilities
- ✅ **Favorites Management** - Save and organize favorite properties
- ✅ **Inquiry Tracking** - View and manage property inquiries
- ✅ **Saved Searches** - Create and manage custom search criteria

### 🛡️ **Security & Performance (Enterprise-Grade)**
- ✅ **CSRF Protection** - All forms secured with token validation
- ✅ **SQL Injection Prevention** - Prepared statements throughout
- ✅ **Advanced Input Validation** - Comprehensive data sanitization
- ✅ **Rate Limiting** - Login attempt protection and DDoS mitigation
- ✅ **Secure File Uploads** - Type validation, size limits, virus scanning
- ✅ **Performance Optimized** - Efficient queries, result caching, image optimization
- ✅ **Error Handling** - Graceful error management with logging

### 📧 **Enhanced SMTP Email System**
- ✅ **Composer Integration** - Professional PHPMailer with dependency management
- ✅ **Automated Notifications** - Contact forms, registration, inquiries
- ✅ **Professional Templates** - Responsive HTML email templates
- ✅ **Security Features** - Environment variables, secure authentication
- ✅ **Email Features**:
  - Contact form notifications to admin
  - Welcome emails for new registrations
  - Property inquiry notifications
  - Email confirmations for users

---

## 📋 **SYSTEM REQUIREMENTS**

**Server Requirements:**
- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ (8.0+ recommended)
- Apache/Nginx web server

**PHP Extensions:**
- mysqli, gd, fileinfo, json, session

**Development Environment:**
- XAMPP, WAMP, MAMP, or similar local server

**Email Dependencies (Composer):**
- PHPMailer ^6.8
- PHPDotenv ^5.6

---

## 📁 **PROJECT STRUCTURE**

```
realestate_/
├── 📂 Database/
│   ├── connection.php              # Database connection
│   └── realestate_complete.sql     # Complete database schema
│
├── 📂 admin/                       # Admin Panel
│   ├── dashboard.php               # Admin dashboard
│   ├── properties.php              # Property management
│   ├── users.php                   # User management
│   ├── inquiries.php               # Inquiry management
│   ├── reports.php                 # Reports & analytics
│   ├── settings.php                # System settings
│   ├── add_property.php            # Add new property
│   ├── edit_property.php           # Edit property
│   ├── image_manager.php           # Image management
│   ├── upload_handler.php          # File upload handler
│   └── includes/
│       └── admin_nav.php           # Admin navigation
│
├── 📂 agent/                       # Agent Panel
│   └── dashboard.php               # Agent dashboard
│
├── 📂 client/                      # Client Panel
│   ├── dashboard.php               # Client dashboard
│   ├── browse_properties.php       # Browse properties
│   ├── favorites.php               # User favorites
│   ├── inquiries.php               # User inquiries
│   └── saved_searches.php          # Saved searches
│
├── 📂 includes/                    # Shared Components
│   ├── EmailService.php            # Email service (Composer-based)
│   ├── enhanced_auth.php           # Enhanced authentication
│   ├── security.php                # Security functions
│   ├── route.php                   # Routing & access control
│   ├── smtp_config.php             # SMTP configuration
│   ├── header.php                  # Common header
│   ├── footer.php                  # Common footer
│   ├── nav.php                     # Navigation
│   └── toast.php                   # Toast notifications
│
├── 📂 composer/                    # Composer Dependencies
│   ├── composer.json               # Composer configuration
│   ├── composer.lock               # Dependency lock file
│   └── vendor/                     # Auto-generated packages
│
├── 📂 bootstrap-5.3.7-dist/        # Bootstrap Framework
│   ├── css/                        # Bootstrap CSS
│   └── js/                         # Bootstrap JavaScript
│
├── 📂 css/                         # Custom Styles
│   └── main.css                    # Main stylesheet
│
├── 📂 uploads/                     # File Uploads
│   └── properties/                 # Property images
│
├── 🏠 index.php                    # Homepage
├── 🔐 login.php                    # User login
├── 📝 register.php                 # User registration
├── 🚪 logout.php                   # Logout handler
├── 📞 contact.php                  # Contact form
├── 🏘️ property.php                # Property listings
├── 🏡 property_details.php         # Property details
├── 👤 profile.php                  # User profile
├── ⭐ bookmarks.php                # User bookmarks
├── 🔧 bookmark_handler.php         # Bookmark operations
├── 💬 reviews.php                  # Property reviews
├── .env                            # Environment variables
└── 📖 README.md                    # Main documentation
```

---

## 📧 **EMAIL SYSTEM CONFIGURATION**

### **Composer Setup**
```bash
cd composer
composer install
```

### **SMTP Configuration**
The email system uses `includes/smtp_config.php` with environment variable support:

```php
// Environment variables (optional .env file)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
FROM_EMAIL=noreply@realestate.com
FROM_NAME=Real Estate
ADMIN_EMAIL=admin@realestate.com
COMPANY_NAME=Real Estate Company
```

### **Email Features**
1. **Contact Form Notifications** (`contact.php`)
   - Admin notifications for new contact submissions
   - User confirmation emails
   
2. **Registration Welcome Emails** (`register.php`)
   - Automatic welcome emails for new users
   - Account features overview
   
3. **Property Inquiry Notifications** (`client/inquiries.php`)
   - Admin alerts for property inquiries
   - Includes property and user details

### **EmailService Usage**
```php
// Contact form emails
$emailService->sendContactForm($name, $email, $phone, $message, $subject);
$emailService->sendContactConfirmation($name, $email, $subject);

// User registration
$emailService->sendWelcomeEmail($name, $email);

// Error handling
$error = $emailService->getError();
```

---

## 🔧 **COMMON ISSUES & SOLUTIONS**

### ❌ **Database Connection Failed**
**Problem**: "Could not connect to database"

**Solutions**:
1. Start MySQL server (XAMPP/WAMP)
2. Check database name: `realestate`
3. Verify username/password in `Database/connection.php`
4. Ensure database is imported correctly

### ❌ **Images Not Uploading**
**Problem**: "Failed to upload image"

**Solutions**:
1. Set folder permissions: `chmod 777 uploads/`
2. Check PHP settings: `upload_max_filesize = 10M`
3. Verify disk space available
4. Check file types are allowed

### ❌ **Admin Access Denied**
**Problem**: "Access denied" in admin panel

**Solutions**:
1. Check user role in database (should be 'admin')
2. Clear browser cookies and session
3. Verify login credentials
4. Check if user account is active

### ❌ **Email Not Sending**
**Problem**: SMTP emails failing

**Solutions**:
1. Run `composer install` in composer directory
2. Check SMTP credentials in `.env` or `smtp_config.php`
3. Enable 2FA and use App Password for Gmail
4. Try port 465 with SSL instead of 587 with TLS
5. Check firewall/antivirus blocking SMTP ports

### ❌ **Favorites Table Error**
**Problem**: "Table 'realestate.favorites' doesn't exist"

**Solutions**:
1. Re-import the complete database schema: `Database/realestate_complete.sql`
2. Verify all tables were created properly
3. Check database user permissions

### 🔍 **Debug Mode**
Enable detailed errors in `Database/connection.php`:
```php
define('DEBUG_MODE', true);
```

---

## 🎯 **HOW TO USE**

### **For Administrators**
1. **Managing Properties**:
   - Access admin panel → Properties
   - Add new properties with images
   - Edit existing property details
   - Manage property status and featured listings

2. **User Management**:
   - Access Users section
   - View/edit user accounts
   - Assign roles (admin/agent/client)
   - Activate/deactivate accounts

3. **Viewing Analytics**:
   - Access Reports section
   - Monitor property views and user activity
   - Track conversion metrics
   - Export analytics data

### **For Agents**
1. **Property Management**:
   - Access agent dashboard
   - Manage assigned properties
   - Update property information
   - Handle client communications

2. **Client Interaction**:
   - Respond to property inquiries
   - Manage client relationships
   - Schedule property viewings

### **For Clients**
1. **Property Search**:
   - Use advanced search filters
   - Save favorite properties
   - Create saved searches for alerts

2. **Account Management**:
   - Update profile information
   - Manage bookmarks and favorites
   - Track inquiry history

---

## 🔒 **SECURITY BEST PRACTICES**

### ✅ **Required Production Steps**
1. **Change Default Passwords**: Update all default login credentials
2. **Enable HTTPS**: Secure all communications with SSL/TLS
3. **Regular Backups**: Schedule automated database and file backups
4. **Update Dependencies**: Keep PHP, MySQL, and Composer packages updated
5. **Monitor Logs**: Set up logging and monitoring for security events
6. **Environment Variables**: Use `.env` file for sensitive configuration

### 📊 **Backup Commands**
```bash
# Database backup
mysqldump -u root -p realestate > backup_$(date +%Y%m%d).sql

# File backup
tar -czf site_backup_$(date +%Y%m%d).tar.gz realestate_/
```

### 🛡️ **Security Features Included**
- CSRF token protection on all forms
- SQL injection prevention with prepared statements
- Input validation and sanitization
- Rate limiting for login attempts
- Secure file upload validation
- Session security and timeout management

---

## 📞 **SUPPORT & TROUBLESHOOTING**

### 🆘 **Getting Help**
1. Check this documentation first
2. Enable debug mode for detailed error information
3. Check server error logs
4. Verify all requirements are met
5. Test database connection separately

### 📋 **System Information**
- **Version**: 2.0.0 (Production Ready)
- **PHP Compatibility**: 7.4 - 8.2
- **MySQL Compatibility**: 5.7 - 8.0
- **Framework**: Bootstrap 5.3.7
- **Email System**: PHPMailer 6.8+ with Composer

### 🌐 **Browser Support**
- Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- Mobile responsive design for all devices
- Progressive web app features

### 📊 **Performance Monitoring**
- Database query optimization
- Image compression and lazy loading
- Caching for frequently accessed data
- CDN integration ready

---

## ✨ **FEATURES OVERVIEW TABLE**

| Feature Category | Component | Status | Description |
|-----------------|-----------|--------|-------------|
| 🏠 **Frontend** | Homepage | ✅ Complete | Hero section, search, featured properties |
| | Property Listings | ✅ Complete | Grid/list view, advanced filtering |
| | Property Details | ✅ Complete | Image galleries, contact forms |
| | User Registration | ✅ Complete | Secure signup with email verification |
| | User Authentication | ✅ Complete | Login/logout with session management |
| 👑 **Admin Panel** | Dashboard | ✅ Complete | Analytics, statistics, overview |
| | Property Management | ✅ Complete | CRUD operations, image management |
| | User Management | ✅ Complete | Role assignment, account management |
| | Reports & Analytics | ✅ Complete | Performance metrics, user behavior |
| | System Settings | ✅ Complete | Configuration management |
| 🧑‍💼 **Agent Panel** | Agent Dashboard | ✅ Complete | Personal property and client management |
| 👤 **Client Panel** | Client Dashboard | ✅ Complete | Personal account overview |
| | Favorites System | ✅ Complete | Save and manage favorite properties |
| | Inquiry Management | ✅ Complete | Track property inquiries |
| | Saved Searches | ✅ Complete | Custom search criteria management |
| 📧 **Email System** | SMTP Integration | ✅ Complete | Composer-based PHPMailer |
| | Contact Notifications | ✅ Complete | Admin alerts, user confirmations |
| | Registration Emails | ✅ Complete | Welcome emails for new users |
| | Inquiry Notifications | ✅ Complete | Property inquiry alerts |
| 🛡️ **Security** | CSRF Protection | ✅ Complete | Token-based form security |
| | SQL Injection Prevention | ✅ Complete | Prepared statements |
| | Input Validation | ✅ Complete | Comprehensive sanitization |
| | Rate Limiting | ✅ Complete | Login attempt protection |
| | File Upload Security | ✅ Complete | Type and size validation |
| 📱 **Responsive Design** | Mobile Optimization | ✅ Complete | Bootstrap 5 responsive framework |
| | Cross-browser Support | ✅ Complete | Modern browser compatibility |

---

## 🎉 **CONCLUSION**

**Your professional real estate website is now complete and production-ready!** 

This comprehensive platform provides:
- **Role-based access** for administrators, agents, and clients
- **Advanced property management** with professional image handling
- **Secure user authentication** and session management
- **Professional email integration** with automated notifications
- **Mobile-responsive design** for all devices
- **Enterprise-grade security** features
- **Scalable architecture** for future enhancements

The system is built with modern PHP practices, follows security best practices, and provides a seamless user experience across all user roles. The clean, modular codebase ensures easy maintenance and future enhancements while reusing and enhancing existing functionality.

For ongoing support, refer to the troubleshooting section, enable debug mode for detailed error information, and ensure all system requirements are met for optimal performance.