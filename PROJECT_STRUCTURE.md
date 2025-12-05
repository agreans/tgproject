# Project Structure

```
TGPROJECT/
├── account_sessions/          # Telegram account session directories (auto-created)
│   └── account_<PHONE>.session/
├── cli/
│   └── worker.php             # Cron worker (runs every minute)
├── config/
│   └── config.php             # Main configuration file
├── data/                       # Database files (SQLite)
│   └── userbot.db
├── jobs/                       # Job queue files (if needed)
├── logs/                       # Error and info logs
│   ├── error_YYYY-MM-DD.log
│   └── info_YYYY-MM-DD.log
├── pages/                      # Web interface pages
│   ├── layout.php             # Main layout template
│   ├── login.php              # Admin login
│   ├── logout.php             # Logout handler
│   ├── dashboard.php          # Main dashboard
│   ├── accounts.php           # Account list
│   ├── account-add.php        # Add account form
│   ├── account-auth.php       # Authenticate account
│   ├── account-status.php     # Check account status
│   ├── account-edit.php       # Edit account limits
│   ├── account-delete.php     # Delete account
│   ├── templates.php          # Template list
│   ├── template-add.php       # Add template
│   ├── template-edit.php      # Edit template
│   ├── template-delete.php    # Delete template
│   ├── scraper.php            # Group scraper
│   ├── broadcasts.php         # Broadcast jobs list
│   ├── broadcast-create.php   # Create broadcast
│   ├── broadcast-view.php     # View broadcast details
│   ├── queue.php              # Message queue
│   └── logs.php               # Activity logs
├── sessions/                   # Admin session files
├── src/                        # Core PHP classes
│   ├── bootstrap.php          # Application bootstrap
│   ├── helpers.php             # Helper functions
│   ├── Database.php           # Database abstraction
│   ├── TelegramAccount.php   # Account management
│   ├── MessageSender.php      # Message sending
│   ├── BroadcastManager.php   # Broadcast management
│   └── GroupScraper.php       # Group scraping
├── vendor/                     # Composer dependencies (after install)
├── .htaccess                  # Apache configuration
├── .gitignore                 # Git ignore rules
├── composer.json              # Composer dependencies
├── generate_password.php      # Password hash generator
├── index.php                  # Main router
├── README.md                  # Main documentation
├── INSTALLATION.md            # Installation guide
└── PROJECT_STRUCTURE.md       # This file
```

## Key Directories

### account_sessions/
- Contains session directories for each Telegram account
- Format: `account_<PHONENUMBER>.session/`
- Each directory contains MadelineProto session files
- **CRITICAL**: Never modify session paths after creation

### cli/
- Contains command-line scripts
- `worker.php` runs via cron every minute
- Processes message queue and manages broadcasts

### config/
- Configuration files
- `config.php` contains database and admin settings
- **DO NOT** commit with real credentials

### data/
- SQLite database files (if using SQLite)
- Auto-created on first run

### logs/
- Application logs
- `error_*.log` - Error logs
- `info_*.log` - Info logs

### pages/
- All web interface pages
- Use `layout.php` for consistent UI
- Protected by admin authentication

### src/
- Core application classes
- All business logic
- Database operations
- Telegram API interactions

## File Flow

1. **Request** → `index.php` (router)
2. **Router** → Loads appropriate page from `pages/`
3. **Page** → Uses classes from `src/`
4. **Classes** → Interact with database via `Database.php`
5. **Worker** → `cli/worker.php` processes queue via cron

## Session Management

### Admin Sessions
- Stored in `sessions/` directory
- Managed by PHP session handler
- Protected by password hash

### Telegram Sessions
- Stored in `account_sessions/account_<PHONE>.session/`
- Managed by MadelineProto
- Each account has its own directory
- Path stored as absolute path in database

## Database Tables

1. `telegram_accounts` - Telegram account information
2. `message_templates` - Message templates
3. `scraped_users` - Scraped user data
4. `broadcast_jobs` - Broadcast job definitions
5. `message_queue` - Message queue items
6. `activity_logs` - System activity logs

