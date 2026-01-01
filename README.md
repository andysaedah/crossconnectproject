# CrossConnect MY 🇲🇾

**Malaysia's Church & Christian Events Directory**

A comprehensive web application for discovering churches and Christian events across Malaysia. Built with PHP, MySQL, and vanilla JavaScript for optimal performance on shared hosting environments.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 🌟 Features

### Public Features
- **Church Directory** - Browse and search churches by state, denomination, or keyword
- **Event Listings** - Discover upcoming Christian events with multi-day support
- **SEO-Friendly URLs** - Clean URLs like `/church/church-name` and `/events/event-slug`
- **Multi-Language Support** - English and Bahasa Malaysia
- **Mobile Responsive** - Optimized for all device sizes
- **Scripture Quotes** - Inspirational verses on homepage and about page

### User Dashboard
- **My Churches** - Manage your church listings
- **My Events** - Create and manage event submissions
- **Profile Management** - Update personal information
- **Password Change** - Secure password updates

### Admin Dashboard
- **User Management** - View and manage registered users
- **Church Moderation** - Approve, edit, or delete church listings
- **Event Management** - Moderate submitted events
- **Activity Logs** - Full audit trail of all actions
- **Email Logs** - Track sent verification emails
- **Language Editor** - Manage translations in-app
- **API Integration Settings** - Configure external services

---

## 🔐 Security Features

| Feature | Implementation |
|---------|----------------|
| **Password Hashing** | bcrypt with cost 12 |
| **CSRF Protection** | Token validation on all forms |
| **Session Security** | HTTPOnly, SameSite=Strict cookies |
| **Session Timeout** | 60-minute idle logout |
| **Brute Force Protection** | 5 login attempts, 15-min lockout |
| **Rate Limiting** | 5 registrations per hour per IP |
| **Email Verification** | Required before dashboard access |
| **XSS Prevention** | `htmlspecialchars()` on all output |
| **SQL Injection Prevention** | Prepared statements throughout |
| **Sensitive File Protection** | `.htaccess` blocks `.env`, `/config/`, etc. |
| **Security Headers** | X-Frame-Options, CSP, XSS-Protection |

---

## 📁 Project Structure

```
hebats/
├── admin/                  # Admin dashboard pages
│   ├── index.php          # Admin overview
│   ├── churches.php       # Church management
│   ├── events.php         # Event management
│   ├── users.php          # User management
│   ├── logs.php           # Activity logs
│   └── ...
├── api/                    # REST API endpoints
│   ├── auth/              # Login, register
│   ├── admin/             # Admin-only APIs
│   ├── user/              # User dashboard APIs
│   ├── churches.php       # Public church API
│   ├── events.php         # Public events API
│   └── states.php         # States list API
├── auth/                   # Authentication pages
│   ├── login.php          # Login/Register form
│   ├── logout.php         # Logout handler
│   ├── verify.php         # Email verification
│   └── verify-pending.php # Verification pending
├── config/                 # Configuration files
│   ├── database.php       # DB connection & env loading
│   ├── auth.php           # Authentication functions
│   ├── email.php          # Email sending (SMTP/Brevo)
│   ├── language.php       # Language handling
│   ├── paths.php          # URL helpers
│   └── lang/              # Translation files (en.php, bm.php)
├── css/                    # Stylesheets
├── dashboard/              # User dashboard pages
├── database/               # SQL schema & migrations
├── images/                 # Static images
├── includes/               # Shared components
├── js/                     # JavaScript files
├── uploads/                # User uploads (gitignored)
├── .env.example           # Environment template
├── .htaccess              # Apache configuration
├── index.php              # Homepage
├── church.php             # Individual church page
├── event.php              # Individual event page
├── events.php             # Events listing
└── ...
```

---

## 🚀 Installation

### Requirements
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- Composer (optional, no dependencies required)

### Step 1: Clone Repository
```bash
git clone https://github.com/andysaedah/crossconnectproject.git
cd crossconnectproject
```

### Step 2: Configure Environment
```bash
cp .env.example .env
```

Edit `.env` with your settings:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_username
DB_PASS=your_password
DB_CHARSET=utf8mb4

# App Configuration
APP_ENV=production
APP_DEBUG=false
```

### Step 3: Import Database
```bash
mysql -u username -p database_name < database/schema.sql
```

Or run migrations individually:
```bash
mysql -u username -p database_name < database/migrations/add_event_format_columns.sql
mysql -u username -p database_name < database/migrations/add_service_languages.sql
# ... etc
```

### Step 4: Set Permissions
```bash
chmod 755 uploads/
```

### Step 5: Configure Apache
Ensure `.htaccess` is enabled. For subdirectory installations, update:
```apache
RewriteBase /your-subdirectory/
```

---

## ⚙️ Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database host | `localhost` |
| `DB_NAME` | Database name | `directory_db` |
| `DB_USER` | Database username | `root` |
| `DB_PASS` | Database password | (empty) |
| `DB_CHARSET` | Character set | `utf8mb4` |
| `APP_ENV` | Environment mode | `production` |
| `APP_DEBUG` | Show errors | `false` |

### Production Settings
For production, always use:
```env
APP_ENV=production
APP_DEBUG=false
```

---

## 🌐 API Endpoints

### Public APIs
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/churches.php` | GET | List all churches |
| `/api/churches.php?state=slug` | GET | Filter by state |
| `/api/events.php` | GET | List all events |
| `/api/events.php?upcoming=1` | GET | Get upcoming events |
| `/api/states.php` | GET | List all states |

### User APIs (Authenticated)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/user/profile.php` | GET/POST | Get/Update profile |
| `/api/user/churches.php` | GET/POST | Manage user's churches |
| `/api/user/events.php` | GET/POST | Manage user's events |
| `/api/user/change-password.php` | POST | Change password |

### Admin APIs
All admin endpoints require admin role authentication.

---

## 🌍 Multi-Language Support

The application supports:
- **English (en)** - Default
- **Bahasa Malaysia (bm)**

Language files are located in `config/lang/`:
- `en.php` - English translations
- `bm.php` - Bahasa Malaysia translations

Users can switch languages via the UI, and preference is saved to session/cookie.

---

## 📱 Responsive Design

The UI is fully responsive with:
- Mobile-first approach
- Collapsible sidebar on dashboard
- Touch-friendly controls
- Optimized images with lazy loading

---

## 🔧 Customization

### Adding a New Language
1. Copy `config/lang/en.php` to `config/lang/xx.php`
2. Translate all values
3. Update `config/language.php` to include new language
4. Add language switcher button in UI

### Adding New Features
- API endpoints go in `/api/`
- Dashboard pages go in `/dashboard/` or `/admin/`
- Use `requireAuth()` for user pages
- Use `requireAdmin()` for admin pages

---

## 📊 Database Schema

### Main Tables
- `users` - User accounts
- `churches` - Church listings
- `events` - Event listings
- `states` - Malaysian states
- `denominations` - Church denominations
- `activity_logs` - Audit trail
- `email_logs` - Email tracking
- `settings` - Application settings
- `amendment_requests` - Church info corrections

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the MIT License.

---

## 🙏 Acknowledgments

- Built for the Christian community in Malaysia
- Inspired by the need for a unified church directory
- Scripture quotes from NIV Bible

---

## 📞 Contact

For questions or support, please use the contact form on the website or open an issue on GitHub.

**Made with ❤️ for Malaysia**
