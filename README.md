# GoGreenHub - Setup and Installation Guide

A comprehensive platform connecting eco-conscious consumers with sustainable businesses, featuring event management, messaging, forums, and business directories.

## 🌐 Application URL

**If hosted on cloud:**
- **Production URL:** `https://gogreenhub.rf.gd/pages/index.html`
- **First Page:** `https://gogreenhub.rf.gd/pages/index.html`

**GitHub link:**
- https://github.com/yinasaurus/wad-grp-proj

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [Step-by-Step Setup Instructions](#step-by-step-setup-instructions)
- [Environment Configuration (.env)](#environment-configuration-env)
- [Running the Application](#running-the-application)
- [Test Accounts (Username/Password)](#test-accounts-usernamepassword)
- [Project Structure](#project-structure)
- [Troubleshooting](#troubleshooting)

---

## 📦 Prerequisites

Before setting up the application, ensure you have the following installed:

1. **Web Server**
   - Apache 2.4+ or Nginx
   - PHP 7.4 or higher (PHP 8.0+ recommended)
   - PHP extensions: `mysqli`, `json`, `mbstring`, `session`

2. **Database**
   - MySQL 5.7+ or MariaDB 10.3+

3. **Web Browser**
   - Chrome (latest), Firefox, Edge, or Safari

---

## 🚀 Step-by-Step Setup Instructions

### Step 1: Download/Extract Project Files

1. **If using Git:**
   ```bash
   git clone https://github.com/yinasaurus/wad-grp-proj.git
   ```

2. **If downloading as ZIP:**
   - Extract the ZIP file to your web server directory
   - Navigate to the extracted folder: `is216_project/wad_group_proj/`

### Step 2: Set Up Database

1. **Create a MySQL Database:**
   - Open phpMyAdmin or MySQL command line
   - Create a new database (e.g., `greenbiz` or `gogreenhub`)
   - Note down the database name, username, and password

2. **Import Database Schema:**
   - Open phpMyAdmin
   - Select your database
   - Click on "Import" tab
   - Choose the `db.sql` file from the project root (`is216_project/latest/db.sql`)
   - Click "Go" to import
   - Wait for the import to complete (you should see success message)

3. **Verify Database Import:**
   - Check that the following tables exist:
     - `users`, `businesses`, `events`, `event_registrations`
     - `conversations`, `messages`
     - `forum_posts`, `forum_comments`
     - `business_favorites`, `business_reviews`
     - `certifications`, `notifications`

### Step 3: Configure Database Connection

1. **Locate Database Configuration File:**
   - Open `htdocs/db_config.php` in your text editor

2. **Update Database Credentials:**
   ```php
   $host = 'localhost';              // Change to your database host
   $user = 'your_database_user';     // Change to your database username
   $password = 'your_password';      // Change to your database password
   $database = 'your_database_name'; // Change to your database name
   ```

   **Example for local development (WAMP/XAMPP):**
   ```php
   $host = 'localhost';
   $user = 'root';
   $password = '';  // Usually empty for local development
   $database = 'greenbiz';
   ```

   **Example for InfinityFree hosting:**
   ```php
   $host = 'sql113.infinityfree.com';
   $user = 'if0_40329348';
   $password = 'your_password';
   $database = 'if0_40329348_greenbiz';
   ```

3. **Save the file**

### Step 4: Configure Environment Variables (.env)

1. **Create `.env` file:**
   - Create a new file named `.env` in the `htdocs` folder
   - Copy the following template:

   ```env
   # Database Configuration
   DB_HOST=localhost
   DB_USER=your_database_user
   DB_PASSWORD=your_database_password
   DB_NAME=your_database_name

   # Google Maps API Key
   # IMPORTANT: You need to obtain your own API key from Google Cloud Console
   # Go to: https://console.cloud.google.com/
   # Enable "Maps JavaScript API" and "Places API"
   # Create credentials (API Key)
   GOOGLE_MAPS_API_KEY=YOUR_GOOGLE_MAPS_API_KEY_HERE

   # Application Configuration
   APP_URL=http://localhost/pages
   APP_ENV=development
   ```

2. **Update the values:**
   - Replace `your_database_user`, `your_database_password`, and `your_database_name` with your actual database credentials
   - **For Google Maps API Key:** You must obtain your own API key from [Google Cloud Console](https://console.cloud.google.com/)
     - Create a new project or select existing
     - Enable "Maps JavaScript API" and "Places API"
     - Create credentials (API Key)
     - Copy the API key and paste it in the `.env` file
   - Replace `APP_URL` with your actual application URL

3. **Note:** The `.env` file contains placeholder values. **You must obtain your own Google Maps API key** as it's a security requirement. The professor/instructor will need to get their own API key.

### Step 5: Update API Configuration

1. **If using `.env` file:**
   - The application should read from `.env` (if you implement `.env` parsing)
   - Otherwise, update `htdocs/pages/api/config.php` directly

2. **Update Google Maps API Key in `htdocs/pages/api/config.php`:**
   ```php
   $googleMapsApiKey = 'YOUR_GOOGLE_MAPS_API_KEY_HERE';
   ```
   - Replace with your actual Google Maps API key from Step 4

### Step 6: Configure Web Server

#### Option A: Using WAMP/XAMPP (Local Development)

1. **Copy Project Files:**
   - Copy the entire `htdocs` folder to your WAMP/XAMPP `www` or `htdocs` directory
   - Example: `C:\wamp64\www\is216_project\latest\htdocs\`

2. **Start WAMP/XAMPP:**
   - Start Apache and MySQL services
   - Ensure both services are running (green icon)

3. **Access the Application:**
   - Open browser and go to: `http://localhost/is216_project/latest/htdocs/pages/index.html`
   - Or: `http://localhost/pages/index.html` (depending on your setup)

#### Option B: Using InfinityFree or Other Cloud Hosting

**For InfinityFree Hosting:**

1. **Access InfinityFree Control Panel:**
   - Log in to your InfinityFree account at https://infinityfree.net/
   - Go to "Control Panel" → Select your website

2. **Create Database:**
   - Go to "MySQL Databases" in the control panel
   - Click "Create New Database"
   - Note down:
     - Database name (e.g., `if0_40329348_greenbiz`)
     - Database username (e.g., `if0_40329348`)
     - Database password (you set this)
     - Database host (e.g., `sql113.infinityfree.com`)

3. **Import Database Schema:**
   - Go to "phpMyAdmin" in the control panel
   - Select your database from the left sidebar
   - Click "Import" tab
   - Choose the `db.sql` file from your project
   - Click "Go" to import
   - Wait for success message

4. **Upload Files:**
   - Go to "File Manager" in the control panel
   - Navigate to `htdocs` or `public_html` folder
   - Upload all files from your local `htdocs` folder
   - **OR** use FTP:
     - FTP Host: `ftpupload.net` (or your assigned FTP host)
     - FTP Username: Your InfinityFree username
     - FTP Password: Your InfinityFree password
     - Upload files to `htdocs` or `public_html` directory

5. **Update Database Configuration:**
   - Edit `htdocs/db_config.php` on the server
   - Update with your InfinityFree database credentials:
     ```php
     $host = 'sql113.infinityfree.com';  // Your database host
     $user = 'if0_40329348';              // Your database username
     $password = 'your_password';        // Your database password
     $database = 'if0_40329348_greenbiz'; // Your database name
     ```

6. **Set File Permissions:**
   - In File Manager, right-click on `uploads` folder
   - Set permissions to 755 (or 777 if needed)
   - Ensure PHP files have read permissions (644)

7. **Access the Application:**
   - Go to: `https://yourdomain.com/pages/index.html`
   - Example: `https://gogreenhub.rf.gd/pages/index.html`
   - **Note:** InfinityFree provides free SSL (HTTPS) automatically

**For Other Cloud Hosting:**
- Follow similar steps but use your hosting provider's control panel
- Upload files via FTP or file manager
- Create database and import `db.sql`
- Update `db_config.php` with your database credentials

### Step 7: Verify Setup

1. **Check Database Connection:**
   - Open `http://localhost/pages/index.html` (or your domain)
   - If you see the homepage, database connection is working

2. **Test Login:**
   - Try logging in with a test account (see [Test Accounts](#test-accounts-usernamepassword) section)
   - If login works, authentication is configured correctly

3. **Check for Errors:**
   - Open browser Developer Tools (F12)
   - Check Console tab for JavaScript errors
   - Check Network tab for failed API requests

---

## 🏃 Running the Application

### Starting the Application

1. **For Local Development (WAMP/MAMP/XAMPP):**
   ```bash
   # 1. Start WAMP/MAMP/XAMPP
   # 2. Ensure Apache and MySQL are running
   # 3. Open browser and navigate to:
   http://localhost/pages/index.html
   ```

2. **For Production/Remote Hosting:**
   - The application runs automatically once files are uploaded
   - Access via: `https://yourdomain.com/pages/index.html`
   - Example: `https://gogreenhub.rf.gd/pages/index.html`

### Accessing Different Pages

- **Homepage:** `http://localhost/pages/index.html`
- **Business Directory:** `http://localhost/pages/directory.html`
- **Events:** `http://localhost/pages/event.html`
- **Forum:** `http://localhost/pages/forum.html`
- **Messages (Consumer):** `http://localhost/pages/messages.html`
- **Messages (Business):** `http://localhost/pages/messages_business.html`
- **User Dashboard:** `http://localhost/pages/user_account.html`
- **Business Dashboard:** `http://localhost/pages/partner_dashboard.html`
- **Login (Consumer):** `http://localhost/pages/login.html`
- **Login (Business):** `http://localhost/pages/login_business.html`
- **Register (Consumer):** `http://localhost/pages/register.html`
- **Register (Business):** `http://localhost/pages/register_business.html`

---

## 👤 Test Accounts (Username/Password)

### Consumer Accounts

| Email | Password | Name | User Type |
|-------|----------|------|-----------|
| `consumer1@gmail.com` | `123456` | consumer1 | Consumer |

### Business Accounts

| Email | Password | Business Name | User Type |
|-------|----------|---------------|-----------|
| `business1@gmail.com` | `123456` | business1 | Business |

### How to Login

1. **For Consumers:**
   - Go to: `http://localhost/pages/login.html`
   - Enter email: `consumer1@gmail.com`
   - Enter password: `123456`
   - Click "Login"

2. **For Businesses:**
   - Go to: `http://localhost/pages/login_business.html`
   - Enter email: `business1@gmail.com`
   - Enter password: `123456`
   - Click "Login"

### Creating New Accounts

You can also create new accounts:

1. **Register as Consumer:**
   - Go to: `http://localhost/pages/register.html`
   - Fill in the registration form
   - Click "Register"

2. **Register as Business:**
   - Go to: `http://localhost/pages/register_business.html`
   - Fill in business details
   - Click "Register"

---

## 📁 Project Structure

```
is216_project/
└── latest/
    ├── htdocs/
    │   ├── pages/
    │   │   ├── api/                    # Backend API endpoints
    │   │   │   ├── auth.php            # Authentication (login, register, logout)
    │   │   │   ├── bridge.php          # User profile management
    │   │   │   ├── data.php            # Business data operations
    │   │   │   ├── event.php           # Event management
    │   │   │   ├── messages.php        # Messaging system
    │   │   │   ├── notifications.php   # Notification system
    │   │   │   ├── addReview.php       # Review management
    │   │   │   ├── displayReviews.php  # Display reviews
    │   │   │   ├── config.php          # API configuration
    │   │   │   └── forums/             # Forum API endpoints
    │   │   ├── js/                     # JavaScript modules
    │   │   │   ├── auth-mixin.js       # Authentication Vue mixin
    │   │   │   ├── notification-mixin.js # Notification Vue mixin
    │   │   │   ├── api.js              # API helper functions
    │   │   │   └── utils.js            # Utility functions
    │   │   ├── css/                    # Stylesheets
    │   │   │   ├── style.css           # Main stylesheet
    │   │   │   ├── events.css          # Event-specific styles
    │   │   │   └── forum.css           # Forum-specific styles
    │   │   ├── index.html              # Home page
    │   │   ├── directory.html           # Business directory
    │   │   ├── event.html              # Events page
    │   │   ├── forum.html              # Community forum
    │   │   ├── messages.html           # Consumer messaging
    │   │   ├── messages_business.html  # Business messaging
    │   │   ├── user_account.html       # Consumer dashboard
    │   │   ├── partner_dashboard.html  # Business dashboard
    │   │   ├── business_profile.html   # Public business profile
    │   │   ├── login.html              # Consumer login
    │   │   ├── login_business.html     # Business login
    │   │   ├── register.html           # Consumer registration
    │   │   ├── register_business.html  # Business registration
    │   │   ├── contact.html            # Contact page
    │   │   └── about.html              # About page
    │   ├── db_config.php               # Database configuration
    │   ├── config.php                  # General configuration
    │   └── .env                        # Environment variables (create this file)
    └── db.sql                          # Database schema file
```

---

## 🔧 Troubleshooting

### Common Issues and Solutions

#### 1. Browser Security Warnings ("Dangerous Site" or "Not Secure")

**Problem:** Browser shows "Not Secure" warning or blocks the site as "dangerous"

**Why This Happens:**
- The site is using HTTP instead of HTTPS
- No SSL certificate is installed
- Self-signed certificate is being used
- Browser security settings are blocking the site

**Solutions for Local Development:**

**Chrome/Edge - Allow HTTP for localhost:**
- The warning is normal for local development
- Click "details" → "Only visit this unsafe site if you're sure you understand the risks."
- Click on "this unsafe site"

**Note:** For local development (localhost), the "Not Secure" warning is expected and safe to ignore. For production sites, always use HTTPS.

#### 2. Database Connection Error

**Problem:** "Database connection failed" or "Access denied"

**Solutions:**
- Check database credentials in `htdocs/db_config.php`
- Ensure MySQL service is running
- Verify database name exists
- Check username and password are correct
- For local development, try `root` with empty password

#### 3. API Returns HTML Instead of JSON

**Problem:** Console shows "Unexpected token '<', "<html><bod"... is not valid JSON"

**Solutions:**
- Check that `api/messages.php` file exists and is accessible
- Verify PHP is running correctly
- Check for PHP errors in the API file
- Ensure session is started in PHP files
- Check file permissions on API files
- Verify database connection is working

#### 4. Page Not Found (404 Error)

**Problem:** Pages return 404 error

**Solutions:**
- Check file paths are correct
- Ensure you're accessing via web server (not file://)
- Verify Apache/Nginx is running
- Check `.htaccess` file exists (if using Apache)
- Try accessing: `http://localhost/pages/index.html`

#### 5. Login Not Working

**Problem:** Cannot login with test accounts

**Solutions:**
- Verify database was imported correctly
- Check `users` table has data
- Ensure password hash is correct (use `123456` for consumer1, `654321` for business1)
- Check browser console for JavaScript errors
- Verify session is working (check PHP session settings)

#### 6. Google Maps Not Loading

**Problem:** Maps don't appear on directory page

**Solutions:**
- Verify Google Maps API key is set in `htdocs/pages/api/config.php` or `.env` file
- **IMPORTANT:** You must obtain your own API key from Google Cloud Console
- Check API key has proper permissions (Maps JavaScript API, Places API)
- Ensure API key billing is enabled (if required)
- Check browser console for API errors

#### 7. Session Issues

**Problem:** Logged out immediately or session not persisting

**Solutions:**
- Check PHP session configuration
- Verify `session_start()` is called in PHP files
- Check session storage directory permissions
- Clear browser cookies and try again
- Check server timezone settings

### Getting Help

If you encounter issues not listed here:

1. **Check Browser Console:**
   - Press F12 to open Developer Tools
   - Check Console tab for JavaScript errors
   - Check Network tab for failed requests

2. **Check Database:**
   - Verify tables exist
   - Check data is present
   - Test database connection manually

3. **Check Server Logs:**
   - Check Apache/Nginx error logs
   - Check PHP error logs
   - Look for specific error messages

---

## 🛠️ Technologies Used

### Frontend
- **HTML5/CSS3** - Structure and styling
- **Vue.js 3** - Reactive JavaScript framework
- **Bootstrap 5** - Responsive UI components
- **Font Awesome** - Icons
- **Google Maps API** - Location services and address autocomplete

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL** - Database management

---

## 📝 Additional Notes

### Important Features

1. **Event Management:**
   - Events must be created at least 7 days in advance
   - Users cannot register for their own events
   - Event organizers can edit and delete their events

2. **Messaging System:**
   - Consumer ↔ Business messaging
   - Business ↔ Business messaging
   - Real-time conversation management

3. **Authentication:**
   - Separate login pages for consumers and businesses
   - Session-based authentication
   - Secure password hashing

### Security Features

- Password hashing using PHP's `password_hash()`
- Prepared statements to prevent SQL injection
- Session management for authentication
- CORS headers for API security
- Input validation on both client and server side

### Environment Variables (.env)

**IMPORTANT:** The `.env` file contains placeholder values. You must:
1. Create your own `.env` file in the `htdocs` folder
2. Obtain your own Google Maps API key from Google Cloud Console
3. Update all placeholder values with your actual credentials

**Security Note:** Never commit the `.env` file to version control. It contains sensitive information.

---
