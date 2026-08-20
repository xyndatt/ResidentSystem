# Resident Information Updating System

A comprehensive, production-ready web application for managing resident information at a Barangay Health Center. Built with PHP, MySQL, HTML5, CSS3, and JavaScript.

## Features

### Resident Features
- **Secure Login System**: Password-protected access with role-based authentication
- **Personal Information Management**: Update and manage personal details
- **Family Information**: Manage spouse, children, and parents information
- **Character References**: Add and manage character references
- **Photo Upload**: Upload and manage passport-size photos
- **Profile Printing**: Generate and print professional A4 profile documents
- **Password Management**: Change password with first-login requirement
- **Dashboard**: View profile completion status and recent updates

### Staff Features
- **Administrative Dashboard**: View comprehensive statistics and analytics
- **Resident Management**: Add, edit, view, and delete resident records
- **Staff Management**: Create and manage multiple administrative staff accounts
- **Search Functionality**: Search residents by various criteria
- **Report Generation**: Generate detailed reports in multiple formats
- **Activity Logging**: Track all system activities and user actions
- **Bulk Operations**: Manage multiple resident records efficiently

## System Requirements

- **Web Server**: Apache with mod_rewrite enabled
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 5.7 or higher
- **Browser**: Modern browser with JavaScript enabled (Chrome, Firefox, Safari, Edge)

## Installation Guide

### Step 1: Download and Extract
1. Download the ResidentSystem folder
2. Extract it to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\ResidentSystem
   ```

### Step 2: Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named `resident_db`
3. Import the SQL file:
   - Go to the `database` folder
   - Import `resident_db.sql` into the `resident_db` database

### Step 3: Configure Database Connection
1. Open `includes/config.php`
2. Update the database credentials if needed:
   ```php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', '');
   define('DB_NAME', 'resident_db');
   ```

### Step 4: Set Permissions
1. Create an `assets/uploads` directory if it doesn't exist
2. Set proper permissions (755 or 777) for the uploads folder

### Step 5: Access the Application
1. Start XAMPP (Apache and MySQL)
2. Open your browser and navigate to:
   ```
   http://localhost/ResidentSystem
   ```

## Default Login Credentials

### Admin/Staff Login
- **Username**: `admin`
- **Password**: `admin123`

### Resident Login
- **Username**: Resident Number (e.g., `RES001`)
- **Password**: Resident Number (same as username)
- **Note**: Residents are forced to change their password on first login

## Project Structure

```
ResidentSystem/
├── assets/
│   ├── css/
│   │   └── style.css              # Main stylesheet
│   ├── js/
│   │   └── main.js                # Main JavaScript file
│   ├── images/                    # Image assets
│   └── uploads/                   # User-uploaded files
├── database/
│   └── resident_db.sql            # Database schema
├── includes/
│   ├── config.php                 # Database configuration
│   └── session.php                # Session management
├── auth/
│   ├── login.php                  # Login page
│   └── logout.php                 # Logout handler
├── resident/
│   ├── dashboard.php              # Resident dashboard
│   ├── personal_info.php          # Personal information management
│   ├── family_info.php            # Family information management
│   ├── references.php             # Character references
│   ├── photo_upload.php           # Photo upload page
│   ├── print_profile.php          # Printable profile
│   └── change_password.php        # Password change
├── staff/
│   ├── dashboard.php              # Staff dashboard
│   ├── residents.php              # Residents list
│   ├── add_resident.php           # Add new resident
│   ├── manage_staff.php           # Manage other staff accounts
│   ├── view_resident.php          # View resident details
│   ├── edit_resident.php          # Edit resident information
│   ├── search.php                 # Search residents
│   ├── reports.php                # Generate reports
│   └── activity_logs.php          # View activity logs
├── process/                       # Form handlers (future expansion)
├── reports/                       # Report generation (future expansion)
├── index.php                      # Landing page
└── README.md                      # This file
```

## Database Schema

### Users Table
Stores login credentials and user roles

### Residents Table
Main table for resident information including personal, contact, and employment details

### Spouse Table
Stores spouse information for married residents

### Children Table
Stores information about resident's children

### Parents Table
Stores information about resident's parents

### References Table
Stores character reference information

### Activity Logs Table
Tracks all system activities for audit purposes

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` for secure password storage
- **Prepared Statements**: Prevents SQL injection attacks
- **Session Management**: Secure session handling with timeout
- **Input Sanitization**: All user inputs are validated and sanitized
- **File Upload Validation**: Validates file types and sizes
- **Role-Based Access Control**: Different access levels for residents and staff

## Features in Detail

### Soft Clinical Neomorphism Design
The application features a modern, professional design with:
- Soft shadows and rounded corners
- Glassmorphism effects
- Smooth animations and transitions
- Responsive layout for all devices
- Medical-themed color scheme

### Responsive Design
- Desktop (1920px and above)
- Laptop (1024px to 1919px)
- Tablet (768px to 1023px)
- Mobile (below 768px)

### File Upload
- Supported formats: JPG, JPEG, PNG
- Maximum file size: 5MB
- Automatic file renaming
- Image preview before upload

### Reports
- All Residents Report
- Senior Citizens Report
- Male/Female Residents Report
- Recently Updated Records Report
- CSV export functionality
- Print-friendly layout

## Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check database credentials in `config.php`
- Ensure database `resident_db` exists

### Upload Folder Issues
- Create `assets/uploads` directory manually
- Set proper permissions (755 or 777)
- Ensure web server has write permissions

### Session Issues
- Clear browser cookies
- Check PHP session settings
- Verify session timeout configuration

### Page Not Found (404)
- Verify file paths are correct
- Check file extensions (.php)
- Ensure Apache mod_rewrite is enabled

## Performance Optimization

- Database indexes on frequently searched fields
- Prepared statements for efficient queries
- CSS and JavaScript minification ready
- Image optimization for uploads
- Caching headers configured

## Maintenance

### Regular Backups
1. Backup database regularly:
   ```bash
   mysqldump -u root resident_db > backup.sql
   ```

2. Backup uploaded files:
   ```bash
   cp -r assets/uploads backup_uploads/
   ```

### Database Optimization
1. Run periodic table optimization:
   ```sql
   OPTIMIZE TABLE residents;
   OPTIMIZE TABLE users;
   ```

### Activity Log Cleanup
Periodically archive or delete old activity logs to maintain performance

## Future Enhancements

- SMS notifications
- Email notifications
- Advanced reporting with charts
- Data export to Excel
- Mobile app integration
- API development
- Two-factor authentication
- Biometric login

## Support and Troubleshooting

For issues or questions:
1. Check the README.md file
2. Review error messages in browser console
3. Check PHP error logs
4. Verify database connection
5. Test with sample data

## License

This project is provided as-is for use by the Barangay Health Center.

## Version

**Version**: 1.0.0
**Last Updated**: 2024
**Compatibility**: PHP 8.0+, MySQL 5.7+, XAMPP

## Credits

Developed for Barangay Health Center Resident Information System

---

**Important**: Always maintain regular backups of your database and uploaded files. Test all changes in a development environment before deploying to production.
