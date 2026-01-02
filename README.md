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
- **Multi-Language Support** - English and Bahasa Malaysia with full translation coverage
- **Mobile Responsive** - Optimized for all device sizes
- **Scripture Quotes** - Inspirational verses on homepage and about page
- **Social Sharing** - Share events to WhatsApp, Facebook, and X (Twitter)
- **Report Amendments** - Users can report incorrect church information

### User Dashboard
- **My Churches** - Manage your church listings
- **My Events** - Create and manage event submissions
- **Profile Management** - Update personal information
- **Password Change** - Secure password updates

### Admin Dashboard
- **User Management** - View and manage registered users
- **Church Moderation** - Approve, edit, or delete church listings with amendment requests
- **Event Management** - Moderate submitted events
- **Activity Logs** - Full audit trail of all actions
- **Email Logs** - Track sent verification emails with delivery status
- **Language Editor** - Manage translations in-app
- **API Integration Settings** - Configure SMTP2GO, Brevo email, and Telegram notifications
- **Site Configuration** - Manage app settings, debug mode, demo data

---

## 🔔 Notifications

### Email Notifications (SMTP2GO / Brevo)
- User verification emails
- Password reset emails
- Welcome emails on verification
- Admin notifications for new churches, events, amendments, and bug reports

### Telegram Notifications
- Real-time admin alerts for:
  - New church submissions
  - New event submissions
  - Amendment requests
  - Bug reports

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
| **Browser Translation Control** | Disabled on login page to preserve localization |

---

## 📁 Project Structure

```
hebats/
├── admin/                  # Admin dashboard pages
│   ├── index.php          # Admin overview
│   ├── churches.php       # Church management with amendments
│   ├── events.php         # Event management
│   ├── users.php          # User management
│   ├── logs.php           # Activity logs
│   ├── email-logs.php     # Email delivery tracking
│   ├── api-settings.php   # SMTP2GO, Brevo, Telegram config
│   ├── site-config.php    # Application settings
│   ├── language.php       # Translation manager
│   └── ...
├── api/                    # REST API endpoints
│   ├── auth/              # Login, register
│   ├── admin/             # Admin-only APIs
│   ├── user/              # User dashboard APIs
│   ├── webhook/           # Email provider webhooks (SMTP2GO, Brevo)
│   ├── churches.php       # Public church API
│   ├── events.php         # Public events API
│   ├── states.php         # States list API
│   ├── report-amendment.php # Amendment reporting
│   └── report-bug.php     # Bug/feedback reporting
├── auth/                   # Authentication pages
│   ├── login.php          # Login/Register form (translation-protected)
│   ├── logout.php         # Logout handler
│   ├── verify.php         # Email verification
│   └── verify-pending.php # Verification pending
├── config/                 # Configuration files
│   ├── database.php       # DB connection & env loading
│   ├── auth.php           # Authentication functions
│   ├── email.php          # Email sending (SMTP2GO/Brevo with fallback)
│   ├── telegram.php       # Telegram notification integration
│   ├── language.php       # Language handling
│   ├── settings.php       # Database settings management
│   ├── paths.php          # URL helpers with clean URL support
│   └── lang/              # Translation files (en.php, bm.php)
├── css/                    # Stylesheets
├── dashboard/              # User dashboard pages
├── database/               # SQL schema & migrations
├── images/                 # Static images (favicon, og-default)
├── includes/               # Shared components
├── js/                     # JavaScript files
├── uploads/                # User uploads (gitignored)
├── .env.example           # Environment template
├── .htaccess              # Apache configuration
├── index.php              # Homepage
├── church.php             # Individual church page
├── event.php              # Individual event page
├── events.php             # Events listing
├── state.php              # Churches by state
├── denomination.php       # Churches by denomination
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

### Step 4: Set Permissions
```bash
chmod 755 uploads/
```

### Step 5: Configure Apache
Ensure `.htaccess` is enabled. For subdirectory installations, update:
```apache
RewriteBase /your-subdirectory/
```

### Step 6: Configure Admin Settings
1. Login as admin
2. Go to **Admin > API Settings** and configure:
   - SMTP2GO API key and sender details
   - (Optional) Brevo fallback
   - (Optional) Telegram bot token and chat ID
3. Go to **Admin > Site Config** and set:
   - Admin notification email
   - Enable/disable demo data
   - Enable/disable debug mode

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

### Database Settings (via Admin Panel)
- `admin_notification_email` - Email for admin notifications
- `smtp2go_api_key` - SMTP2GO API key
- `telegram_bot_token` - Telegram bot token
- `telegram_chat_id` - Telegram chat ID for notifications
- `enable_demo_data` - Show demo churches/events when database is empty
- `debug_mode` - Enable debug logging
- `clean_urls` - Enable SEO-friendly URL routing

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
| `/api/report-amendment.php` | POST | Submit church amendment |
| `/api/report-bug.php` | POST | Submit bug report |

### User APIs (Authenticated)
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/user/profile.php` | GET/POST | Get/Update profile |
| `/api/user/churches.php` | GET/POST | Manage user's churches |
| `/api/user/events.php` | GET/POST | Manage user's events |
| `/api/user/change-password.php` | POST | Change password |

### Webhook Endpoints
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/webhook/smtp2go.php` | POST | SMTP2GO delivery tracking |
| `/api/webhook/brevo.php` | POST | Brevo delivery tracking |

---

## 🌍 Multi-Language Support

The application supports:
- **English (en)** - Default
- **Bahasa Malaysia (bm)**

Language files are located in `config/lang/`:
- `en.php` - English translations (~800+ keys)
- `bm.php` - Bahasa Malaysia translations

Users can switch languages via the header UI, and preference is saved to session/cookie.

---

## 📱 Responsive Design

The UI is fully responsive with:
- Mobile-first approach
- Collapsible sidebar on dashboard
- Touch-friendly controls
- Optimized images with lazy loading
- Mobile-specific titles for church/event pages

---

## 📊 Database Schema

### Main Tables
- `users` - User accounts with email verification
- `churches` - Church listings with social links
- `events` - Event listings with multi-day support
- `states` - Malaysian states (16 states)
- `denominations` - Church denominations
- `activity_logs` - Full audit trail
- `email_logs` - Email delivery tracking
- `settings` - Application settings (key-value store)
- `amendment_requests` - Church info correction requests

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
- A CoreFLAME Community Project

---

## 📞 Contact

For questions or support, please use the contact form on the website or open an issue on GitHub.

**Made with ❤️ for Malaysia**
