# Installation Guide - Telegram Userbot Panel

## Step-by-Step Installation

### Step 1: Upload Files

Upload all project files to your Hostinger hosting:
- Via FTP/SFTP to `public_html/` directory
- Or via File Manager in Hostinger control panel

### Step 2: Install Composer Dependencies

**Option A: Via SSH (Recommended)**

```bash
cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html
composer install --no-dev --optimize-autoloader
```

**Option B: Via Local Composer**

1. Install Composer on your local machine
2. Run `composer install --no-dev --optimize-autoloader`
3. Upload the `vendor/` folder to your server

### Step 3: Set File Permissions

Set the following permissions:

```bash
chmod 755 cli/worker.php
chmod -R 755 account_sessions
chmod -R 755 sessions
chmod -R 755 data
chmod -R 755 jobs
chmod -R 755 logs
```

If using File Manager:
- Right-click each folder → Properties → Set permissions to 755
- Right-click `cli/worker.php` → Properties → Set permissions to 755

### Step 4: Generate Admin Password

**Via SSH:**
```bash
php generate_password.php
```

**Via Browser:**
1. Navigate to: `https://yourdomain.com/generate_password.php`
2. Enter your desired password
3. Copy the generated hash

### Step 5: Configure Admin Password

Edit `config/config.php`:

Find this line:
```php
define('ADMIN_PASSWORD_HASH', '$2y$10$YOUR_PASSWORD_HASH_HERE');
```

Replace `$2y$10$YOUR_PASSWORD_HASH_HERE` with the hash from Step 4.

### Step 6: Configure Database (Optional)

The system uses SQLite by default (no configuration needed).

To use MySQL, edit `config/config.php`:

```php
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

### Step 7: Set Up Cron Job

1. Log into Hostinger control panel
2. Navigate to **Cron Jobs**
3. Add new cron job with:
   - **Minute**: `*`
   - **Hour**: `*`
   - **Day**: `*`
   - **Month**: `*`
   - **Weekday**: `*`
   - **Command**: 
     ```
     php /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html/cli/worker.php
     ```

Replace `YOUR_USERNAME` and `YOUR_DOMAIN` with your actual values.

### Step 8: Verify Installation

1. Navigate to your domain in a browser
2. You should see the login page
3. Log in with the password you set in Step 4
4. You should see the dashboard

## Post-Installation Checklist

- [ ] All files uploaded
- [ ] Composer dependencies installed (`vendor/` folder exists)
- [ ] File permissions set correctly
- [ ] Admin password configured
- [ ] Cron job set up and running
- [ ] Can access login page
- [ ] Can log in successfully
- [ ] Dashboard loads correctly

## Getting Telegram API Credentials

1. Go to https://my.telegram.org/apps
2. Log in with your phone number
3. Create a new application (if needed)
4. Copy:
   - **App api_id** (number)
   - **App api_hash** (string)

These are required when adding Telegram accounts.

## First Account Setup

1. Log into the panel
2. Go to **Accounts** → **Add Account**
3. Fill in:
   - Label: `My Account` (or any name)
   - Phone: `+1234567890` (with country code)
   - API ID: From my.telegram.org
   - API Hash: From my.telegram.org
4. Click **Add Account**
5. Click the **Authenticate** button (key icon)
6. Click **Send Verification Code**
7. Check your Telegram app for the code
8. Enter the code and click **Verify Code**

Your account should now be active!

## Troubleshooting

### "Composer dependencies not installed"

- Run `composer install` in the project directory
- Ensure `vendor/` folder exists and is uploaded

### "Database Error"

- For SQLite: Ensure `data/` directory is writable (755 permissions)
- For MySQL: Verify database credentials in `config/config.php`

### "Cannot access login page"

- Check `.htaccess` file exists
- Verify PHP version is 8.2+
- Check error logs in Hostinger panel

### "Worker not processing"

- Verify cron job is set up correctly
- Check cron job logs in Hostinger panel
- Ensure `cli/worker.php` has execute permissions (755)
- Test manually: `php cli/worker.php` (via SSH)

### "Account authentication fails"

- Verify API ID and Hash are correct
- Ensure phone number includes country code (+)
- Check `account_sessions/` directory is writable
- Review error logs in `logs/` directory

## Support

If you encounter issues:
1. Check `logs/error_*.log` files
2. Review Hostinger error logs
3. Verify all steps above are completed
4. Check file permissions

