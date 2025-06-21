# 🚀 InfinityFree Deployment Guide

## Your Hosting Details
- **Website URL**: https://company-movie.great-site.net
- **Database**: if0_39260841_movienight
- **Username**: if0_39260841
- **MySQL Host**: sql305.infinityfree.com

## 📋 Step-by-Step Deployment

### Step 1: Set Up Database
1. Go to your InfinityFree control panel
2. Click **phpMyAdmin** next to your database `if0_39260841_movienight`
3. Copy and paste the SQL from `database-setup-infinityfree.sql`
4. Click **Go** to execute

### Step 2: Upload Files
1. In your InfinityFree control panel, click **File Manager**
2. Navigate to `/htdocs/` directory
3. **Delete** the existing `index2.html` file
4. Upload all these files to `/htdocs/`:

\`\`\`
htdocs/
├── .htaccess
├── index.html
├── confirmation.html
├── admin-login.php
├── admin.php
├── logout.php
├── config.php
├── security.php
├── register.php
├── get-settings.php
├── get-seats.php
├── update-settings.php
├── delete-registration.php
├── script.js
├── admin-script.js
├── data/                    # Create this folder
└── images/
    └── wd-logo.png
\`\`\`

### Step 3: Create Data Directory
1. In File Manager, create a new folder called `data` in `/htdocs/`
2. Set permissions to **755** (if option available)

### Step 4: Test Your Website
1. Visit: https://company-movie.great-site.net
2. You should see the WD Movie Night registration form
3. Test admin access: https://company-movie.great-site.net/admin-login.php
   - Username: `Western-Digital`
   - Password: `WDAdmin123`

## 🔧 File Upload Methods

### Method 1: File Manager (Recommended)
- Use InfinityFree's built-in File Manager
- Upload files one by one or in small batches
- Create folders as needed

### Method 2: FTP Client
- **Host**: files.infinityfree.com
- **Username**: if0_39260841
- **Password**: SCOfwiT35hpA1f
- **Port**: 21 (FTP) or 22 (SFTP)

## 🛡️ Security Features Active
- ✅ Rate limiting (file-based)
- ✅ SQL injection protection
- ✅ XSS prevention
- ✅ CSRF protection
- ✅ Session security
- ✅ Input validation
- ✅ Security logging

## 📱 URLs After Deployment
- **Main Registration**: https://company-movie.great-site.net/
- **Admin Login**: https://company-movie.great-site.net/admin-login.php
- **Admin Dashboard**: https://company-movie.great-site.net/admin.php
- **Confirmation Page**: https://company-movie.great-site.net/confirmation.html

## 🔍 Testing Checklist
- [ ] Database connection works
- [ ] Registration form loads
- [ ] Seat selection works
- [ ] Form submission works
- [ ] Admin login works
- [ ] Admin dashboard loads
- [ ] Settings can be updated
- [ ] Registration data appears
- [ ] Rate limiting works (try multiple failed logins)

## 🚨 Troubleshooting

### Database Connection Issues
- Verify database name: `if0_39260841_movienight`
- Check if tables were created in phpMyAdmin
- Ensure config.php has correct credentials

### File Upload Issues
- InfinityFree has file size limits
- Upload files in smaller batches
- Check file permissions

### Security Issues
- If rate limiting doesn't work, check if `data/` folder exists
- Verify .htaccess file was uploaded correctly
- Check error logs in control panel

## 📞 Support
If you encounter issues:
1. Check InfinityFree's error logs in control panel
2. Verify all files uploaded correctly
3. Test database connection in phpMyAdmin
4. Contact InfinityFree support if needed

## 🎉 Go Live!
Once everything is working:
1. Update event settings in admin panel
2. Share the URL with your team
3. Monitor registrations in admin dashboard
4. Export data as needed

Your WD Movie Night registration system is now live and secure! 🎬✨
