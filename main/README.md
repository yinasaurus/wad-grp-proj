# Green Business Directory - Quick Start

## 🚀 Setup (5 Steps)

1. **Start WAMP/XAMPP** (Apache + MySQL)
2. **Import Database**: Run `pages/actlgreenbiz.sql` in phpMyAdmin
3. **Configure Database**: Edit `db_config.php` with your MySQL credentials
4. **Configure Firebase**: Update `pages/js/firebase-config.js` with your Firebase config
5. **Configure Google Maps**: Add API key to `pages/api/config.php`

**Access**: `http://localhost/is216_project/new_proj_folder/main/pages/index.html`

---

## 👥 User Types

- **Consumer**: Register at `register.html`, login at `login.html`
- **Business**: Register at `register_business.html`, login at `login_business.html`

---

## 📁 Key Files

- **Database**: `pages/actlgreenbiz.sql`
- **Main API**: `pages/api/data.php`, `pages/api/bridge.php`
- **Config**: `db_config.php` (database), `pages/js/firebase-config.js` (Firebase), `pages/api/config.php` (Maps)

---

## 📚 Full Documentation

**See `PROJECT_DOCUMENTATION.md` for complete documentation** including:
- Architecture overview
- API endpoints
- Database schema
- Troubleshooting
- Feature status

---

## ✅ Quick Verification

- [ ] Can register consumer account
- [ ] Can register business account
- [ ] Directory shows only businesses
- [ ] Messaging works both ways
- [ ] Address geocoding works
