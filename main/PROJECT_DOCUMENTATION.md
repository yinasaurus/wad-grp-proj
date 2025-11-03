# Green Business Directory - Project Documentation

## 📋 Table of Contents
1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Technology Stack](#technology-stack)
4. [Database Schema](#database-schema)
5. [User Types & Authentication](#user-types--authentication)
6. [Key Features](#key-features)
7. [File Structure](#file-structure)
8. [Setup Instructions](#setup-instructions)
9. [API Endpoints](#api-endpoints)
10. [CSS Standardization](#css-standardization)
11. [Security Features](#security-features)
12. [Common Issues & Solutions](#common-issues--solutions)
13. [Feature Status](#feature-status)
14. [API Keys & Configuration](#api-keys--configuration)
15. [Recent Fixes & Improvements](#recent-fixes--improvements)

---

## 🎯 Project Overview

The **Green Business Directory** is a web application that connects consumers with sustainable businesses in Singapore. The platform features:

- **Consumer Accounts**: Browse, search, and interact with green businesses
- **Business Accounts**: Manage business profiles, certifications, products, and communicate with customers
- **Real-time Messaging**: Direct communication between consumers and businesses
- **Directory with Maps**: Interactive map showing business locations
- **Favorites System**: Save favorite businesses
- **Community Forum**: Discussion platform for sustainability topics

---

## 🏗️ Architecture

### Frontend-Backend Communication Flow

```
┌─────────────────┐
│   HTML/Vue.js   │  (Frontend - main/pages/)
│   Client Pages  │
└────────┬────────┘
         │ AJAX/Fetch API Calls
         ▼
┌─────────────────┐
│   PHP API       │  (Backend - main/pages/api/)
│   (bridge.php,  │
│    data.php)    │
└────────┬────────┘
         │ MySQLi Queries
         ▼
┌─────────────────┐
│   MySQL         │  (Database - green_directory)
│   Database      │
└─────────────────┘

         │
         │ Firebase Authentication
         ▼
┌─────────────────┐
│   Firebase      │  (Firestore for user data)
│   Auth/Firestore│
└─────────────────┘
```

### Authentication Flow

1. **User registers/logs in via Firebase** (handled by Firebase Auth)
2. **Firebase token sent to PHP backend** (`bridge.php?action=session_login`)
3. **Backend verifies token and syncs with MySQL** (creates/updates user record)
4. **Session created** (PHP session stores user_id, user_type, business_id)
5. **Frontend uses session** for all subsequent API calls

---

## 🛠️ Technology Stack

### Frontend
- **Vue.js 3** - Reactive JavaScript framework
- **Bootstrap 5** - CSS framework
- **Font Awesome** - Icons
- **Google Maps JavaScript API** - Interactive maps with Places Autocomplete

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL** - Relational database
- **Firebase Authentication** - User authentication
- **Firestore** - User profile storage (syncs with MySQL)

### Infrastructure
- **WAMP** - Local development server
- **MySQL** - Database server

---

## 🗄️ Database Schema

### Core Tables

#### `users`
- Stores both consumer and business accounts
- **user_type**: `ENUM('consumer', 'business')` - Determines account type
- Links to `businesses` via `user_id` (optional for businesses)

#### `businesses`
- Business profiles with location data (lat/lng)
- Links to `users` via `user_id` (nullable - allows unlinked businesses)
- **Filtering**: Only businesses with `user_type = 'business'` appear in directory

#### `certifications`
- Business certifications (ISO 14001, Green Mark, etc.)
- Links to `businesses` via `business_id`

#### `greenpns` (Products/Services)
- Products/services offered by businesses
- Links to `businesses` via `bid`

#### `conversations` & `messages` (Firestore)
- Real-time messaging between consumers and businesses
- Stored in Firestore, not MySQL

### Key Relationships

```
users (1) ──── (0..1) businesses
businesses (1) ──── (*) certifications
businesses (1) ──── (*) greenpns
users (1) ──── (*) saved_companies (favorites)
```

---

## 👥 User Types & Authentication

### User Types

#### 1. **Consumer** (`user_type = 'consumer'`)
- **Registration**: `register.html`
- **Login**: `login.html`
- **Features**:
  - Browse business directory
  - View business profiles
  - Message businesses
  - Save favorites
  - Access forum
  - View account dashboard

#### 2. **Business** (`user_type = 'business'`)
- **Registration**: `register_business.html`
- **Login**: `login_business.html`
- **Features**:
  - Manage business profile
  - Update address/location (auto-geocoded)
  - Manage certifications
  - Manage products/services
  - Respond to customer messages
  - View partner dashboard

### Authentication Flow

#### Consumer Registration
1. User fills form in `register.html`
2. Firebase creates account (`createUserWithEmailAndPassword`)
3. Firestore document created in `users` collection with `userType: 'consumer'`
4. MySQL record created via `api/data.php?action=register_consumer` with `user_type = 'consumer'`

#### Business Registration
1. User fills form in `register_business.html`
2. Firebase creates account
3. Firestore document created in `businesses` collection with `userType: 'business'`
4. MySQL records created:
   - `users` table with `user_type = 'business'`
   - `businesses` table with basic info
   - Linked via `user_id`

#### Login Process
1. User authenticates with Firebase (`signInWithEmailAndPassword`)
2. Firebase ID token obtained
3. Token sent to `api/bridge.php?action=session_login`
4. Backend:
   - Verifies Firebase token
   - Checks if business record exists (for businesses)
   - Checks if user exists in MySQL
   - Creates user if doesn't exist (preserves `user_type`)
   - Creates PHP session
5. Frontend receives session info and redirects

**⚠️ Important**: Consumers are never converted to businesses automatically. If a consumer account exists, business login flow is skipped.

---

## ✨ Key Features

### 1. **Directory Page** (`directory.html`)
- Displays all businesses on map and list
- **Filter**: Only shows businesses where `user_type = 'business'`
- Features:
  - Search by name, category, location
  - Filter by certification
  - Interactive Google Maps
  - Click business to view on map
  - Message button (consumers only)
  - Favorite button

### 2. **Messaging System**
- **Consumer**: `messages.html`
  - Lists conversations with businesses
  - Can start new conversations
  - Real-time message updates
  
- **Business**: `messages_business.html`
  - Lists conversations with customers
  - Can respond to customer messages
  - Shows customer names

- **Storage**: Firestore collections `conversations` and `messages`
- **Integration**: Links MySQL `user_id` with Firestore messages

### 3. **Partner Dashboard** (`partner_dashboard.html`)
- Business profile management
- **Address Geocoding**:
  - Uses Google Maps Places Autocomplete
  - Automatically converts address to lat/lng
  - Updates coordinates when address changes
- Features:
  - Update business info
  - Manage certifications
  - Manage products/services
  - View analytics

### 4. **User Account** (`user_account.html`)
- Consumer dashboard
- Features:
  - View saved businesses (favorites)
  - View carbon offsets
  - Update profile

### 5. **Forum** (`forum.html`)
- Community discussion platform
- Features:
  - Create posts
  - Comment and like
  - Category filtering

---

## 📁 File Structure

```
main/
├── pages/
│   ├── index.html              # Homepage
│   ├── directory.html          # Business directory with map
│   ├── register.html           # Consumer registration
│   ├── register_business.html  # Business registration
│   ├── login.html              # Consumer login
│   ├── login_business.html     # Business login
│   ├── messages.html           # Consumer messaging
│   ├── messages_business.html  # Business messaging
│   ├── partner_dashboard.html  # Business dashboard
│   ├── user_account.html       # Consumer dashboard
│   ├── business_profile.html   # Public business profile
│   ├── forum.html              # Community forum
│   ├── about.html              # About page
│   ├── contact.html            # Contact page
│   │
│   ├── css/                    # Stylesheets
│   │   ├── style.css           # Main styles (includes variables)
│   │   ├── register.css        # Shared registration styles
│   │   ├── login.css           # Consumer login styles
│   │   ├── login_business.css  # Business login styles
│   │   ├── messages.css        # Messaging styles (shared)
│   │   ├── partner_dashboard.css # Business dashboard styles
│   │   ├── user_account.css    # Consumer dashboard styles
│   │   ├── forum.css           # Forum styles
│   │   └── business_profile.css # Business profile styles
│   │
│   ├── js/                     # JavaScript modules
│   │   ├── firebase-config.js   # Firebase initialization
│   │   ├── auth-mixin.js       # Vue.js auth mixin
│   │   ├── api.js              # API utilities
│   │   └── utils.js            # Helper functions
│   │
│   └── api/                    # PHP API endpoints
│       ├── bridge.php          # Firebase-MySQL bridge (session login) - MAIN AUTH
│       ├── data.php            # Main data operations (CRUD)
│       ├── firebase_auth.php   # Firebase token verification (used by bridge.php)
│       ├── config.php          # Configuration (API keys)
│       ├── addReview.php       # Review submission
│       ├── displayReviews.php  # Review display
│       └── forum/              # Forum API endpoints
│
├── db_config.php               # Database connection config
├── config.php                  # Environment config (optional)
├── PROJECT_DOCUMENTATION.md    # Complete documentation (this file)
└── README.md                   # Quick start guide

Database:
└── pages/actlgreenbiz.sql      # Database schema (run this in phpMyAdmin)
```

---

## 🚀 Setup Instructions

### Prerequisites
1. **WAMP** installed and running
2. **PHP 7.4+** enabled
3. **MySQL** server running
4. **Firebase Project** created with Authentication enabled
5. **Google Maps API Key** obtained

### Step 1: Database Setup

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create database: `green_directory`
3. Import schema:
   ```sql
   SOURCE path/to/actlgreenbiz.sql;
   ```
   Or run the SQL file directly in phpMyAdmin

### Step 2: Database Configuration

Edit `main/db_config.php`:
```php
$host = 'localhost';
$user = 'root';              // Your MySQL username
$password = '';              // Your MySQL password
$database = 'green_directory';
```

### Step 3: Firebase Configuration

1. Create Firebase project at https://console.firebase.google.com
2. Enable Authentication (Email/Password)
3. Enable Firestore Database
4. Get Firebase config (Settings → General → Your apps)
5. Update `main/pages/js/firebase-config.js`:
```javascript
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT.appspot.com",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
};
```

### Step 4: Google Maps API

1. Create Google Cloud project
2. Enable:
   - Maps JavaScript API
   - Places API
   - Geocoding API
3. Create API key
4. Update `main/pages/api/config.php` OR set environment variable:
   ```php
   $GOOGLE_MAPS_API_KEY = 'YOUR_API_KEY';
   ```

### Step 5: Deploy Files

1. Copy `main/` folder to WAMP `www/` directory:
   ```
   C:\wamp64\www\is216_project\new_proj_folder\main\
   ```
2. Access via browser:
   ```
   http://localhost/is216_project/new_proj_folder/main/pages/index.html
   ```

---

## 🔌 API Endpoints

### Authentication (`bridge.php`)

#### `POST /api/bridge.php?action=session_login`
Firebase token-based login
- **Input**: `{ idToken: string }`
- **Output**: `{ success: true, user: {...}, business: {...} }`
- **Creates**: PHP session with user_id, user_type, business_id

#### `GET /api/bridge.php?action=get_user`
Get current session user
- **Output**: `{ success: true, user: {...}, business: {...} }`

---

### Data Operations (`data.php`)

#### `GET /api/data.php?action=all_businesses`
Get all businesses for directory
- **Filter**: Only businesses with `user_type = 'business'`
- **Output**: `{ success: true, businesses: [...] }`

#### `POST /api/data.php?action=register_consumer`
Register new consumer
- **Input**: `{ name, email, password }`
- **Output**: `{ success: true, userId: int }`

#### `POST /api/data.php?action=register_business`
Register new business
- **Input**: `{ name, email, phone, category, description }`
- **Output**: `{ success: true, businessId: int }`

#### `POST /api/data.php?action=update_business_profile`
Update business profile (business only)
- **Input**: Business data (name, address, lat, lng, etc.)
- **Output**: `{ success: true }`

#### `GET /api/data.php?action=business_profile&id={id}`
Get business profile
- **Output**: `{ success: true, business: {...} }`

#### `POST /api/data.php?action=save_business`
Add business to favorites (consumer only)
- **Input**: `{ business_id: int }`

#### `POST /api/data.php?action=remove_business`
Remove business from favorites
- **Input**: `{ business_id: int }`

#### `GET /api/data.php?action=get_saved_businesses`
Get user's saved businesses
- **Output**: `{ success: true, savedCompanies: [...] }`

#### `POST /api/data.php?action=get_user_names_batch`
Get multiple user names (for messaging)
- **Input**: `{ user_ids: [int, int, ...] }`
- **Output**: `{ success: true, names: { userId: name, ... } }`

---

## 🎨 CSS Standardization

### Color Variables

All CSS files use standardized color variables defined in `:root`:

```css
:root {
    --primary-green: #2d5016;    /* Dark green - navbars, headings */
    --light-green: #4a7c23;     /* Medium green - accents */
    --accent-green: #7cb342;     /* Bright green - buttons, highlights */
}
```

### Standard Font
- **Font Family**: `'Segoe UI', Tahoma, Geneva, Verdana, sans-serif`

### Consistent Patterns

1. **Navbar**: Uses `var(--primary-green)` gradient
2. **Buttons**: Use `var(--accent-green)` with gradients
3. **Cards**: White background, subtle shadows, green left border
4. **Forms**: Consistent border radius (10px), green focus states

### Files Updated
- ✅ `style.css` - Main styles (already standardized)
- ✅ `register.css` - Standardized colors
- ✅ `login.css` - Standardized colors
- ✅ `login_business.css` - Standardized colors
- ✅ `partner_dashboard.css` - Standardized colors and variables
- ✅ `user_account.css` - Standardized colors and variables

---

## 🔒 Security Features

### SQL Injection Prevention
- ✅ All queries use **prepared statements** with parameter binding
- ✅ No direct string concatenation in SQL

### XSS Prevention
- ✅ Output encoding in PHP
- ✅ Vue.js auto-escapes template values

### Authentication
- ✅ Firebase token verification
- ✅ PHP session management
- ✅ Password hashing (`password_hash()`)

### Authorization
- ✅ Session-based authorization checks
- ✅ Business-only endpoints check `user_type = 'business'`
- ✅ User ID validation on all operations

### API Security
- ✅ CORS headers configured
- ✅ Session cookie settings
- ✅ Error messages don't expose sensitive info

---

## 🐛 Common Issues & Solutions

### Issue 1: "Consumer showing as business"
**Cause**: Incorrect `user_type` in database
**Solution**: 
```sql
UPDATE users SET user_type = 'consumer' WHERE email = 'user@example.com';
```

### Issue 2: "Consumers appearing in directory"
**Cause**: Query not filtering by `user_type`
**Solution**: Already fixed - query joins with `users` and filters `user_type = 'business'`

### Issue 3: "Google Maps not loading"
**Cause**: Missing or invalid API key
**Solution**: 
1. Check `api/config.php` for API key
2. Ensure Maps JavaScript API, Places API, Geocoding API are enabled
3. Check browser console for errors

### Issue 4: "Address not geocoding"
**Cause**: Google Maps API not loaded or invalid address
**Solution**:
1. Check Places Autocomplete is initialized
2. Verify address format
3. Check browser console for geocoding errors

### Issue 5: "Messages not working"
**Cause**: Authentication or Firestore setup issue
**Solution**:
1. Verify Firebase config is correct
2. Check Firestore rules allow read/write
3. Verify user is logged in (check session)

### Issue 6: "CSS not consistent"
**Cause**: Old hardcoded colors
**Solution**: Already fixed - all CSS files now use standardized variables

---

## 📝 Testing Checklist

### Consumer Flow
- [ ] Register new consumer account
- [ ] Login with consumer account
- [ ] Browse business directory
- [ ] Search and filter businesses
- [ ] View business profile
- [ ] Message a business
- [ ] Save business to favorites
- [ ] Access user account dashboard

### Business Flow
- [ ] Register new business account
- [ ] Login with business account
- [ ] Access partner dashboard
- [ ] Update business profile
- [ ] Update address (verify geocoding)
- [ ] Add/remove certifications
- [ ] Manage products/services
- [ ] View and respond to messages

### Cross-Functional
- [ ] Directory only shows businesses (not consumers)
- [ ] Business and consumer views are separate
- [ ] CSS is consistent across pages
- [ ] Maps load correctly
- [ ] Messaging works both ways

---

## 👥 For Teammates

### Quick Start
1. Set up WAMP/XAMPP
2. Import `actlgreenbiz.sql` to MySQL
3. Configure `db_config.php` with your MySQL credentials
4. Set up Firebase project and update `firebase-config.js`
5. Get Google Maps API key and add to `config.php`
6. Start Apache and MySQL in WAMP
7. Open `index.html` in browser

### Key Files to Modify

**Database**: `pages/actlgreenbiz.sql` (in `main/pages/` directory)
**Database Config**: `main/db_config.php`
**Firebase Config**: `main/pages/js/firebase-config.js`
**API Config**: `main/pages/api/config.php`
**Main API**: `main/pages/api/data.php`, `main/pages/api/bridge.php`

### Development Tips
- Use browser console for debugging
- Check Apache error logs (`C:\wamp64\logs\apache_error.log`)
- Check PHP error logs (configured in `db_config.php`)
- Use phpMyAdmin to inspect database directly
- Test both consumer and business flows separately

---

## 📚 Additional Resources

- **Vue.js Docs**: https://vuejs.org/
- **Firebase Docs**: https://firebase.google.com/docs
- **Google Maps API**: https://developers.google.com/maps/documentation
- **Bootstrap 5 Docs**: https://getbootstrap.com/docs/5.3/
- **MySQL Docs**: https://dev.mysql.com/doc/

---

## 📊 Feature Status

### ✅ Fully Implemented Features

| Feature | Status | Details |
|---------|--------|---------|
| **Saved Favorites** | ✅ **WORKING** | Users can save/unsave businesses. Uses `saved_companies` table. Fully functional. |
| **Messaging System** | ✅ **WORKING** | Real-time messaging between consumers and businesses via Firestore. Both views work. |
| **Review System** | ✅ **WORKING** | Users can rate and review businesses. Star ratings, comments, average rating. |
| **Forum/Community** | ✅ **WORKING** | Full forum with posts, comments, likes. Categories, edit/delete functionality. |
| **Business Directory** | ✅ **WORKING** | Interactive map, search, filters, business profiles. Only shows businesses. |
| **Authentication** | ✅ **WORKING** | Firebase Auth + MySQL sync. Consumer and business accounts separate. |
| **Business Dashboard** | ✅ **WORKING** | Profile management, address geocoding, certifications, products. |
| **Consumer Dashboard** | ✅ **WORKING** | Profile editing, saved businesses, carbon offsets display. |

### ⚠️ Partially Implemented

| Feature | Status | Notes |
|---------|--------|-------|
| **User Interests** | ⚠️ **REMOVED** | `user_interests` table removed. Not needed - only `saved_companies` is used. |
| **Personalization** | ⚠️ **BASIC** | Profile works. Advanced preference selection not implemented (not needed). |
| **Password Change** | ⚠️ **UI ONLY** | UI exists but not connected to backend. Low priority. |

---

## 🔑 API Keys & Configuration

### Firebase Configuration
1. Create Firebase project at https://console.firebase.google.com
2. Enable Authentication (Email/Password)
3. Enable Firestore Database
4. Update `pages/js/firebase-config.js` with your Firebase config

### Google Maps API
1. Create Google Cloud project
2. Enable: Maps JavaScript API, Places API, Geocoding API
3. Create API key with restrictions
4. Add to `pages/api/config.php` OR set in `.env` file

### Environment Variables (Optional)
Create `.env` in `main/` directory:
```env
GOOGLE_MAPS_API_KEY=your_key_here
```

**Note**: Firebase config must be in `firebase-config.js` (public keys). Google Maps key can be in `.env` or `config.php`.

---

## ✅ Recent Fixes & Improvements

1. ✅ **Fixed consumer registration** - Now correctly sets `user_type = 'consumer'`
2. ✅ **Fixed business login** - Prevents consumers from being converted to businesses
3. ✅ **Fixed directory filtering** - Only shows businesses, not consumers
4. ✅ **Standardized CSS** - All files use consistent color variables
5. ✅ **Fixed messaging** - Both consumer and business messaging work correctly
6. ✅ **Fixed address geocoding** - Addresses properly convert to lat/lng with Places Autocomplete
7. ✅ **Fixed forum 500 error** - Corrected database path in forum API
8. ✅ **Improved login contrast** - Darker background for better visibility
9. ✅ **Fixed favorite button alignment** - Properly aligned on business profile
10. ✅ **Cleaned up SQL** - Removed unnecessary `user_interests` and `saved_interests` tables
11. ✅ **Fixed Bootstrap dropdowns** - Properly initialized in Vue components

---

**Last Updated**: December 2024
**Version**: 1.0
**Maintained By**: Development Team

