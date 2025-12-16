# Quick Start Guide - Real Estate Website

## 🚀 Getting Started

### 1. Database Setup
```sql
-- Import the complete database schema
-- File: Database/realestate.sql
-- This includes all tables, roles, and initial data
```

### 2. Default Login Credentials
After importing the database, you can use:
- **Admin**: `admin@realestate.com` / `admin123` (check database for actual password hash)
- **Agent**: Register as agent (requires admin approval)
- **Client**: Register as client

### 3. Role-Based Access

#### Admin Access
- URL: `/admin/dashboard.php`
- Can manage all properties, users, inquiries
- Can approve agent properties
- Full system control

#### Agent Access
- URL: `/agent/dashboard.php`
- Can upload properties (submitted as pending)
- Can edit/delete only their own properties
- Can view inquiries for their properties

#### Client Access
- URL: `/client/dashboard.php`
- Can browse all properties
- Can save favorites
- Can send inquiries to agents

## 📋 Registration Process

1. Go to `register.php`
2. Fill in personal information
3. **Select Account Type:**
   - **Client**: Immediate access
   - **Agent**: Requires admin approval (is_active = 0)
   - **Admin**: Only if logged in as admin
4. Submit registration
5. Login with selected role

## 🔑 Key Features by Role

### Admin Features
- ✅ Full property CRUD
- ✅ User management
- ✅ Approve agent properties
- ✅ Feature properties
- ✅ View all inquiries
- ✅ System statistics

### Agent Features
- ✅ Upload properties (pending approval)
- ✅ Edit own properties
- ✅ Delete own properties
- ✅ View inquiries for own properties
- ✅ Property statistics
- ✅ Performance analytics

### Client Features
- ✅ Browse properties
- ✅ Advanced search & filters
- ✅ Save favorites
- ✅ Send inquiries
- ✅ View property details
- ✅ Update profile

## 🛠️ File Locations

### Admin Files
- Dashboard: `admin/dashboard.php`
- Add Property: `admin/add_property.php` (also accessible to agents)
- Edit Property: `admin/edit_property.php` (with ownership check)
- Manage Properties: `admin/properties.php`
- Inquiries: `admin/inquiries.php`

### Agent Files
- Dashboard: `agent/dashboard.php`
- Upload Property: `agent/upload_property.php`
- My Properties: `agent/properties.php`
- Edit Property: `agent/edit_property.php`
- Inquiries: `agent/inquiries.php`

### Client Files
- Dashboard: `client/dashboard.php`
- Favorites: `client/favorites.php`
- Inquiries: `client/inquiries.php`
- Saved Searches: `client/saved_searches.php`

## 🔒 Security Features

1. **CSRF Protection**: All forms use CSRF tokens
2. **SQL Injection Prevention**: Prepared statements throughout
3. **XSS Protection**: htmlspecialchars on all outputs
4. **Password Hashing**: bcrypt via password_hash()
5. **Session Security**: Session regeneration on login
6. **Role Verification**: Every page checks user role

## 📝 Property Workflow

### Agent Uploads Property
1. Agent fills form in `agent/upload_property.php`
2. Property saved with `status = 'pending'`
3. Property `user_id` = agent's ID
4. Property not visible to clients yet

### Admin Approves Property
1. Admin views pending properties
2. Admin changes status to `'available'`
3. Property now visible to clients
4. Clients can view, bookmark, inquire

### Client Interaction
1. Client browses available properties
2. Client can bookmark favorites
3. Client sends inquiry via property details page
4. Inquiry sent to property owner (agent)
5. Agent responds via inquiries page

## 🎨 UI Components

- **Bootstrap 5.3.7**: Already included
- **Font Awesome 6.5.0**: Icons throughout
- **Inter Font**: Modern typography
- **Responsive Design**: Mobile-friendly
- **Color Scheme**: 
  - Admin: Blue (#2563eb)
  - Agent: Purple (#7c3aed)
  - Client: Blue with variations

## ⚠️ Important Notes

1. **Agent Approval**: New agent accounts are inactive until admin approves
2. **Property Approval**: Agent-uploaded properties are pending until admin approves
3. **Ownership**: Agents can only edit/delete their own properties
4. **Admin Override**: Admins can manage all properties regardless of owner

## 🐛 Troubleshooting

### Login Issues
- Check database connection in `Database/connection.php`
- Verify user exists in `users` table
- Check `is_active` status (agents may be inactive)
- Verify `role_id` matches `roles` table

### Property Not Showing
- Check property `status` (must be 'available')
- Verify property `user_id` matches agent
- Check database foreign keys

### Permission Denied
- Verify user role in session
- Check `includes/route.php` functions
- Ensure proper role checks in page files

## 📞 Support

For issues or questions:
1. Check `PROJECT_ANALYSIS_AND_FIXES.md` for known issues
2. Review `IMPLEMENTATION_SUMMARY.md` for feature list
3. Check database schema in `Database/realestate.sql`

