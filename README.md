# Telegram Userbot Panel - MadelineProto v8

A complete Telegram Userbot Web Panel system compatible with Hostinger shared hosting.

## Features

- ✅ **Telegram Account Management** - Add, authenticate, and manage multiple Telegram accounts
- ✅ **Message Templates** - Create reusable message templates with variables
- ✅ **Group Scraper** - Join groups and scrape member lists
- ✅ **Broadcast System** - Send messages to multiple users with rate limiting
- ✅ **Queue Management** - Background processing via cron worker
- ✅ **Activity Logs** - Track all system activities
- ✅ **Hostinger Compatible** - Works on shared hosting without websockets

## Requirements

- PHP 8.2 or higher
- SQLite (default) or MySQL
- Composer (for installation only)
- Cron access (for worker)

## Installation

### 1. Upload Files

Upload all files to your Hostinger public_html directory (or subdirectory).

### 2. Install Dependencies

SSH into your server and run:

```bash
cd /home/USER/domains/DOMAIN/public_html
composer install --no-dev --optimize-autoloader
```

If you don't have SSH access, you can install Composer locally and upload the `vendor` folder.

### 3. Set Permissions

```bash
chmod 755 cli/worker.php
chmod -R 755 account_sessions
chmod -R 755 sessions
chmod -R 755 data
chmod -R 755 jobs
chmod -R 755 logs
```

### 4. Configure Admin Password

Run the password generator:

```bash
php generate_password.php
```

Copy the generated hash and paste it into `config/config.php`:

```php
define('ADMIN_PASSWORD_HASH', 'YOUR_GENERATED_HASH_HERE');
```

### 5. Configure Database

Edit `config/config.php`:

- For SQLite (default): No changes needed
- For MySQL: Update DB_TYPE, DB_HOST, DB_NAME, DB_USER, DB_PASS

### 6. Set Up Cron Job

In your Hostinger control panel, add a cron job:

```
* * * * * php /home/USER/domains/DOMAIN/public_html/cli/worker.php
```

Replace `USER` and `DOMAIN` with your actual values.

### 7. Access the Panel

Navigate to your domain in a browser. You'll be redirected to the login page.

## Usage

### Adding Telegram Accounts

1. Go to **Accounts** → **Add Account**
2. Enter:
   - Label (for identification)
   - Phone number (with country code, e.g., +1234567890)
   - API ID and API Hash (from https://my.telegram.org/apps)
3. Click **Add Account**
4. Click **Authenticate** to send verification code
5. Enter the code from Telegram

### Creating Message Templates

1. Go to **Templates** → **Add Template**
2. Enter template name and content
3. Use variables: `{firstname}`, `{lastname}`, `{username}`, `{fullname}`

### Scraping Group Members

1. Go to **Scraper**
2. Select an active account
3. Enter group username (with or without @)
4. Click **Join Group** (if not already a member)
5. Click **Scrape Members** to extract user list

### Creating Broadcasts

1. Go to **Broadcasts** → **Create Broadcast**
2. Enter job name
3. Select message template
4. Select accounts to use
5. Select users to message
6. Click **Create Broadcast Job**

The worker will process messages automatically via cron.

## Important Notes

### Session Management

- Each account stores a single `.madeline` file inside `account_sessions` (e.g., `account_sessions/account_<PHONE>.madeline`)
- Session paths are stored as absolute paths in the database
- **NEVER modify session paths after creation**

### Account Limits

- **Daily Limit**: Maximum messages per day per account
- **Per Run Limit**: Maximum messages per worker execution
- **Delay**: Random delay between messages (min-max seconds)

### Worker

The worker runs every minute and:
- Processes pending queue items
- Respects account limits
- Applies randomized delays
- Checks account status
- Resets daily counters

### Error Prevention

The system prevents common errors:
- ✅ "Invalid code: I'm not waiting for the code" - Proper session handling
- ✅ "This instance is already logged in" - Status checks before auth
- ✅ MadelineProto web UI - Disabled in settings
- ✅ Session directory errors - Proper path handling
- ✅ Undefined index errors - Session validation

## Troubleshooting

### Account Authentication Fails

- Ensure API ID and Hash are correct
- Check that phone number includes country code
- Verify session directory is writable
- Check error logs in `logs/` directory

### Worker Not Processing

- Verify cron job is set up correctly
- Check cron logs in Hostinger panel
- Ensure `cli/worker.php` is executable
- Check PHP error logs

### Messages Not Sending

- Verify account status is "active"
- Check account limits (daily/per-run)
- Review queue status in **Queue** page
- Check error messages in queue items

## Security

- Admin password is hashed using bcrypt
- Session files are protected via .htaccess
- CSRF protection available (can be enabled)
- All user input is sanitized

## Support

For issues or questions:
1. Check error logs in `logs/` directory
2. Review activity logs in the panel
3. Verify all configuration settings

## License

This project is provided as-is for educational purposes.

