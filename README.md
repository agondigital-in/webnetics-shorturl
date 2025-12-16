# Ads Platform

A comprehensive advertising management system that allows Super Admins, Admins, Publishers, and Advertisers to manage advertising campaigns.

## Features

- **Super Admin Dashboard**: Full access to all platform features
- **Admin Dashboard**: Manage campaigns, advertisers, and publishers
- **Publisher Dashboard**: View and track assigned campaigns
- **Campaign Management**: Create and manage advertising campaigns with short URLs
- **Analytics**: Track campaign performance and generate reports
- **User Management**: Role-based access control system

## Installation

1. **Prerequisites**:
   - XAMPP or LAMP stack with PHP 7+, Apache, and MySQL
   - Make sure Apache mod_rewrite is enabled

2. **Setup**:
   - Clone or copy the project files to your web server directory (e.g., `htdocs/tracking`)
   - Update the [.env](file:///C:/xampp/htdocs/tracking/.env) file with your database credentials
   - Run the installation script:
     ```
     cd c:\xampp\htdocs\tracking
     C:\xampp\php\php.exe install.php
     ```
   - The script will create the database and tables, and set up a default super admin account

3. **Default Login Credentials**:
   - Username: `admin`
   - Password: `Agondigital@2020`

## URL Structure

- **Main Pages**:
  - [index.php](file:///C:/xampp/htdocs/tracking/index.php) - Main landing page
  - [login.php](file:///C:/xampp/htdocs/tracking/login.php) - Admin login
  - [publisher_login.php](file:///C:/xampp/htdocs/tracking/publisher_login.php) - Publisher login

- **Dashboard Routes**:
  - [/admin/dashboard.php](file:///C:/xampp/htdocs/tracking/admin/dashboard.php) - Admin dashboard
  - [/super_admin/dashboard.php](file:///C:/xampp/htdocs/tracking/super_admin/dashboard.php) - Super admin dashboard
  - [/publisher_dashboard.php](file:///C:/xampp/htdocs/tracking/publisher_dashboard.php) - Publisher dashboard

- **Campaign Tracking**:
  - Short URLs in the format: `http://yourdomain.com/{shortcode}`
  - Processed by [redirect.php](file:///C:/xampp/htdocs/tracking/redirect.php)

## User Roles

1. **Super Admin**:
   - Full access to all platform features
   - Can manage admins, advertisers, publishers, and campaigns
   - Access to comprehensive analytics and reports

2. **Admin**:
   - Manage campaigns, advertisers, and publishers
   - View analytics and reports
   - Cannot manage other admins

3. **Publisher**:
   - View assigned campaigns
   - Track campaign performance
   - Limited to their own campaign data

## Security

- Passwords are hashed using PHP's `password_hash()`
- Session-based authentication
- Role-based access control
- Prepared statements to prevent SQL injection
- Input validation on all forms

## Troubleshooting

- If short URLs don't work, ensure mod_rewrite is enabled in Apache
- Check that the [.env](file:///C:/xampp/htdocs/tracking/.env) file has correct database credentials
- Verify that all required PHP extensions are installed