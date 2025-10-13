# 🚀 Real Estate Website - Deployment Guide

## 📋 **PRE-DEPLOYMENT CHECKLIST**

### ✅ **System Requirements Verified**
- **PHP**: 8.2.12 ✅ (Meets requirement: PHP 7.4+)
- **Database**: MySQL/MariaDB (Install if not available)
- **Web Server**: Apache/Nginx
- **Composer**: For email dependencies

---

## 🛠️ **STEP-BY-STEP DEPLOYMENT**

### **Step 1: Database Setup**
```sql
-- Create database
CREATE DATABASE realestate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p realestate < Database/realestate_complete.sql
```

### **Step 2: Configure Database Connection**
Edit `Database/connection.php`:
```php
<?php
$host = "localhost";
$dbname = "realestate";
$username = "your_db_username";
$password = "your_db_password";

$conn = new mysqli($host, $username, $password, $dbname);
// ... rest of configuration
```

### **Step 3: Install Dependencies**
```bash
cd composer
composer install
```

### **Step 4: Set File Permissions**
```bash
# Make uploads directory writable
chmod 755 uploads/
chmod 755 uploads/properties/
```

### **Step 5: Configure Email (Optional)**
Create `.env` file in root directory:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
FROM_EMAIL=noreply@yourdomain.com
FROM_NAME=Your Real Estate Company
ADMIN_EMAIL=admin@yourdomain.com
```

---

## 🌐 **WEB SERVER CONFIGURATION**

### **Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

### **Nginx Configuration**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/realestate_;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 🔐 **SECURITY CONFIGURATION**

### **Environment Variables**
- Move sensitive data to `.env` file
- Never commit credentials to version control
- Use strong passwords for database

### **File Permissions**
```bash
# Secure file permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 600 .env
chmod 644 Database/connection.php
```

---

## 🧪 **TESTING CHECKLIST**

### **Frontend Testing**
- [ ] Homepage loads correctly
- [ ] Property listings display
- [ ] Search functionality works
- [ ] Property details page loads
- [ ] Contact forms submit successfully

### **Authentication Testing**
- [ ] User registration works
- [ ] Login/logout functions
- [ ] Role-based access (Admin/Agent/Client)
- [ ] Password reset (if implemented)

### **Dashboard Testing**
- [ ] Admin dashboard loads
- [ ] Agent dashboard loads
- [ ] Client dashboard loads
- [ ] Statistics display correctly

### **Functionality Testing**
- [ ] Property bookmarking
- [ ] Inquiry submission
- [ ] Email notifications
- [ ] File uploads (property images)

---

## 📊 **PERFORMANCE OPTIMIZATION**

### **Database Optimization**
```sql
-- Add indexes for better performance
CREATE INDEX idx_properties_status ON properties(status);
CREATE INDEX idx_properties_featured ON properties(is_featured);
CREATE INDEX idx_properties_created ON properties(created_at);
```

### **Caching**
- Enable PHP OPcache
- Use browser caching for static assets
- Consider Redis/Memcached for session storage

### **Image Optimization**
- Compress uploaded images
- Use WebP format where possible
- Implement lazy loading

---

## 🔧 **TROUBLESHOOTING**

### **Common Issues & Solutions**

#### **Database Connection Error**
```php
// Check connection in Database/connection.php
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
```

#### **File Upload Issues**
```bash
# Check PHP upload settings
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

#### **Email Not Sending**
- Verify SMTP credentials
- Check firewall settings
- Use app-specific passwords for Gmail

#### **Permission Errors**
```bash
# Fix ownership
sudo chown -R www-data:www-data /path/to/realestate_
```

---

## 🚀 **PRODUCTION DEPLOYMENT**

### **SSL Certificate**
```bash
# Install Let's Encrypt SSL
sudo certbot --apache -d yourdomain.com
```

### **Backup Strategy**
```bash
# Database backup
mysqldump -u root -p realestate > backup_$(date +%Y%m%d).sql

# File backup
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/realestate_
```

### **Monitoring**
- Set up error logging
- Monitor disk space
- Track user analytics
- Regular security updates

---

## 📱 **MOBILE OPTIMIZATION**

The website is fully responsive with Bootstrap 5. Test on:
- [ ] Mobile phones (iOS/Android)
- [ ] Tablets (iPad/Android tablets)
- [ ] Desktop browsers (Chrome/Firefox/Safari/Edge)

---

## 🎯 **POST-DEPLOYMENT TASKS**

1. **Update Admin Credentials**
   - Change default admin password
   - Add additional admin users

2. **Content Setup**
   - Add sample properties
   - Configure company information
   - Set up email templates

3. **SEO Optimization**
   - Add meta tags
   - Configure sitemap
   - Set up Google Analytics

4. **Security Audit**
   - Run security scans
   - Update dependencies
   - Review access logs

---

## 📞 **SUPPORT & MAINTENANCE**

### **Regular Maintenance**
- Weekly database backups
- Monthly security updates
- Quarterly performance reviews
- Annual security audits

### **Monitoring Tools**
- Error logs: `/var/log/apache2/error.log`
- Access logs: `/var/log/apache2/access.log`
- PHP logs: Check `php.ini` error_log setting

---

## ✅ **DEPLOYMENT SUCCESS**

Your real estate website is now ready for production! 

**Default Login Credentials:**
- **Admin**: admin@realestate.com / password
- **Agent**: agent@realestate.com / password

**Next Steps:**
1. Change default passwords
2. Add your company branding
3. Upload property listings
4. Configure email notifications
5. Set up monitoring and backups

**🎉 Congratulations! Your professional real estate website is live!**
