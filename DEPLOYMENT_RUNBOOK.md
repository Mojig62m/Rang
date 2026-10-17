# 📖 Bamero Deployment Runbook
## راهنمای کامل استقرار پروژه بامرو

---

## 📋 Document Information

**Version:** 1.0.0  
**Last Updated:** 2024-12-19  
**Author:** Vibe Code Agent  
**Repository:** Mojig62m/Rang  
**Status:** Production-Ready  

---

## 🎯 Table of Contents

1. [Pre-Deployment Checklist](#1-pre-deployment-checklist)
2. [Deployment Procedures](#2-deployment-procedures)
3. [Post-Deployment Verification](#3-post-deployment-verification)
4. [Rollback Procedures](#4-rollback-procedures)
5. [Emergency Procedures](#5-emergency-procedures)
6. [Troubleshooting Guide](#6-troubleshooting-guide)
7. [Maintenance Tasks](#7-maintenance-tasks)
8. [Contact Information](#8-contact-information)

---

## 1. Pre-Deployment Checklist

### 1.1 Code Quality Checks

- [ ] ✅ All PHP files pass syntax check (`php -l`)
- [ ] ✅ All security headers present in .htaccess
- [ ] ✅ CSP implementation verified
- [ ] ✅ WooCommerce compatibility confirmed
- [ ] ✅ File structure validated (< 50 files)
- [ ] ✅ No non-production files (docker, scripts, docs, tests)
- [ ] ✅ No hardcoded credentials or secrets
- [ ] ✅ All inputs sanitized
- [ ] ✅ All outputs escaped
- [ ] ✅ Nonce verification present

### 1.2 Environment Checks

- [ ] ✅ GitHub Secrets configured
- [ ] ✅ SSH access to production server verified
- [ ] ✅ Deployment path exists and writable
- [ ] ✅ Database connection verified
- [ ] ✅ Redis connection verified (if used)
- [ ] ✅ CDN configuration verified (if used)

### 1.3 Configuration Checks

- [ ] ✅ `wp-config.php` has production settings
- [ ] ✅ `.htaccess` has security rules
- [ ] ✅ `robots.txt` has SEO rules
- [ ] ✅ All environment variables set
- [ ] ✅ Payment gateway configured (ZarinPal)
- [ ] ✅ SMS provider configured (SMS.ir)

### 1.4 Backup Checks

- [ ] ✅ Database backup created
- [ ] ✅ File backup created
- [ ] ✅ Backup verification completed
- [ ] ✅ Rollback procedure documented

---

## 2. Deployment Procedures

### 2.1 Standard Deployment

**Prerequisites:**
- All pre-deployment checks passed
- Code reviewed and approved
- Staging deployment successful

**Steps:**

#### Step 1: Commit Changes
```bash
# Navigate to project directory
cd /path/to/bamero

# Add all changes
git add .

# Commit with descriptive message
git commit -m "feat: Add new product category filter"

# Push to main branch
git push origin main
```

#### Step 2: Monitor CI Pipeline
```bash
# Check CI status
gh workflow list

# View specific workflow
gh workflow view CI_ID

# Check logs
gh run view RUN_ID
```

**Expected:** All CI checks should pass (green ✅)

#### Step 3: Deploy to Staging (Manual)
```bash
# Trigger staging deployment
gh workflow run deploy.yml --ref staging

# Or deploy manually via SSH
rsync -avz --delete --exclude='.git' --exclude='.github' ./ user@staging-server:/var/www/staging
```

#### Step 4: Test on Staging
```bash
# Run health check
curl -s -o /dev/null -w "%{http_code}" https://staging.yourdomain.com

# Test critical workflows
- Homepage load
- Product browsing
- Cart functionality
- Checkout flow (sandbox)
- OTP authentication (sandbox)
- Payment flow (sandbox)
- Admin dashboard
```

#### Step 5: Deploy to Production
```bash
# Deployment is automatic on push to main
# Monitor deployment progress
gh workflow list

# Check deployment logs
gh run view DEPLOY_RUN_ID
```

#### Step 6: Post-Deployment Verification
```bash
# Check site health
curl -s -o /dev/null -w "%{http_code}" https://yourdomain.com

# Verify critical workflows (see Section 3)
```

**Expected Duration:** 5-10 minutes

---

### 2.2 Hotfix Deployment

**When to use:** Critical security fixes, payment issues, major bugs

**Steps:**

#### Step 1: Create Hotfix Branch
```bash
# Create hotfix branch from main
git checkout main
git pull origin main
git checkout -b hotfix/fix-payment-issue
```

#### Step 2: Make Minimal Changes
```bash
# Make only necessary changes to fix the issue
# DO NOT:
# - Add new features
# - Refactor code
# - Update dependencies (unless related to the issue)
```

#### Step 3: Test Locally
```bash
# Run syntax check
find . -name "*.php" -exec php -l {} \;

# Test the specific fix
php -r "include('wp-load.php'); /* test your fix */"
```

#### Step 4: Commit and Push
```bash
# Commit the fix
git add .
git commit -m "hotfix: Fix payment callback verification"

# Push to remote
git push origin hotfix/fix-payment-issue
```

#### Step 5: Deploy to Staging
```bash
# Trigger staging deployment
gh workflow run deploy.yml --ref hotfix/fix-payment-issue

# Or deploy manually
rsync -avz --delete --exclude='.git' ./ user@staging-server:/var/www/staging
```

#### Step 6: Test on Staging
```bash
# Verify the fix works
# Verify no regressions introduced
```

#### Step 7: Merge to Main
```bash
# Checkout main
git checkout main

# Merge hotfix
git merge hotfix/fix-payment-issue

# Push to main (triggers production deployment)
git push origin main
```

#### Step 8: Delete Hotfix Branch
```bash
# Delete local branch
git branch -d hotfix/fix-payment-issue

# Delete remote branch
git push origin --delete hotfix/fix-payment-issue
```

**Expected Duration:** 15-30 minutes

---

### 2.3 Scheduled Deployment

**When to use:** Regular updates, new features

**Schedule:** Every 2 weeks (recommended)

**Steps:**
1. Complete all development on feature branches
2. Merge to main after code review
3. Let CI/CD handle deployment
4. Monitor post-deployment

---

## 3. Post-Deployment Verification

### 3.1 Health Checks

#### HTTP Status Check
```bash
# Check main page
curl -s -o /dev/null -w "%{http_code}\n" https://yourdomain.com

# Expected: 200
```

#### Database Connectivity
```bash
# Check database connection via WP CLI
wp db check

# Or via PHP script
php -r "require('wp-load.php'); global \$wpdb; \$wpdb->get_var('SELECT 1');"
```

#### Cache Connectivity (Redis)
```bash
# Check Redis connection
redis-cli -h 127.0.0.1 -p 6379 ping

# Expected: PONG
```

### 3.2 Critical Workflow Tests

#### Test 1: Homepage Load
```bash
# Test homepage
curl -s -o /dev/null -w "%{http_code}\n" https://yourdomain.com

# Test with cURL for more details
curl -v https://yourdomain.com 2>&1 | grep "HTTP"
```

#### Test 2: Product Browsing
```bash
# Test product page
curl -s -o /dev/null -w "%{http_code}\n" https://yourdomain.com/product/sample-product

# Test category page
curl -s -o /dev/null -w "%{http_code}\n" https://yourdomain.com/product-category/sample-category
```

#### Test 3: Cart Functionality
```bash
# Add to cart (requires session)
# This is a manual test - verify in browser
```

#### Test 4: OTP Authentication
```bash
# Test OTP request (sandbox mode)
curl -X POST https://yourdomain.com/wp-admin/admin-post.php \
  -d "action=bamero_request_otp&mobile=09123456789&bamero_otp_nonce=NONCE_VALUE"

# Expected: Redirect with success message
```

#### Test 5: Payment Flow (Sandbox)
```bash
# Test ZarinPal payment (sandbox)
# This is a manual test - verify in browser with sandbox credentials
```

#### Test 6: Admin Dashboard
```bash
# Test admin login
curl -s -o /dev/null -w "%{http_code}\n" https://yourdomain.com/wp-admin

# Expected: 200 (or 302 redirect to login)
```

### 3.3 Monitoring Checks

#### Error Rate Check
```bash
# Check for HTTP 5xx errors in last 5 minutes
# This requires log access on the server
ssh user@yourdomain.com "grep ' 50[0-9] ' /var/log/nginx/access.log | wc -l"

# Expected: 0
```

#### Response Time Check
```bash
# Measure response time
curl -o /dev/null -s -w "Total: %{time_total}s\n" https://yourdomain.com

# Expected: < 2s
```

#### Database Query Check
```bash
# Check slow queries (if MySQL slow query log enabled)
ssh user@yourdomain.com "tail -n 10 /var/log/mysql/mysql-slow.log"

# Expected: No slow queries (> 2s)
```

### 3.4 Verification Checklist

- [ ] ✅ Homepage loads (HTTP 200)
- [ ] ✅ Product pages load
- [ ] ✅ Category pages load
- [ ] ✅ Cart functionality works
- [ ] ✅ Checkout flow works
- [ ] ✅ OTP authentication works
- [ ] ✅ Payment flow works (sandbox)
- [ ] ✅ Admin dashboard accessible
- [ ] ✅ No HTTP 5xx errors
- [ ] ✅ Response time < 2s
- [ ] ✅ Database connectivity OK
- [ ] ✅ Cache connectivity OK (if used)

---

## 4. Rollback Procedures

### 4.1 Git-Based Rollback

**When to use:** Deployment issues, critical bugs, failed verification

**Steps:**

#### Step 1: Identify Previous Stable Commit
```bash
# List recent commits
git log --oneline -n 20

# Find the last known stable commit
git log --oneline --grep="stable\|production\|release" -n 10
```

#### Step 2: Create Rollback Branch
```bash
# Create rollback branch from stable commit
git checkout -b rollback/to-stable COMMIT_HASH
```

#### Step 3: Deploy Rollback Version
```bash
# Push rollback branch
git push origin rollback/to-stable

# Trigger deployment
gh workflow run deploy.yml --ref rollback/to-stable

# Or deploy manually
rsync -avz --delete --exclude='.git' ./ user@server:/var/www/html
```

#### Step 4: Verify Rollback
```bash
# Run all verification checks (Section 3)
```

#### Step 5: Merge to Main (Optional)
```bash
# If rollback is permanent
git checkout main
git merge rollback/to-stable
git push origin main
```

#### Step 6: Cleanup
```bash
# Delete rollback branch
git branch -d rollback/to-stable
git push origin --delete rollback/to-stable
```

**Expected Duration:** 10-15 minutes

---

### 4.2 Database Rollback

**Warning:** ⚠️ Database rollback is **NOT** automatic with code rollback

**When to use:** Database migration issues, data corruption

**Prerequisites:**
- Recent database backup available
- Compatible code version available

**Steps:**

#### Step 1: Restore Database Backup
```bash
# Using hosting provider's backup system
# Or restore from local backup
mysql -u DB_USER -p DB_NAME < /path/to/backup.sql
```

#### Step 2: Verify Data Integrity
```bash
# Check order count
wp db query "SELECT COUNT(*) FROM wp_bamero_posts WHERE post_type = 'shop_order'"

# Check user count
wp db query "SELECT COUNT(*) FROM wp_bamero_users"

# Check product count
wp db query "SELECT COUNT(*) FROM wp_bamero_posts WHERE post_type = 'product'"
```

#### Step 3: Deploy Compatible Code
```bash
# Deploy code version compatible with restored database
git checkout COMPATIBLE_COMMIT_HASH
rsync -avz --delete --exclude='.git' ./ user@server:/var/www/html
```

#### Step 4: Verify Compatibility
```bash
# Run all verification checks (Section 3)
# Pay special attention to:
# - Database connectivity
# - Data consistency
# - Application functionality
```

**Important Notes:**
- ❌ **Never** overwrite production database with staging/development database
- ❌ Database rollback requires **separate procedure** from code rollback
- ❌ **Test** database restore procedure regularly
- ❌ **Document** all rollback procedures

---

### 4.3 Partial Rollback

**When to use:** Only specific components need rollback

**Steps:**

#### Rollback Theme Only
```bash
# Deploy previous theme version
rsync -avz --delete wp-content/themes/bamero/ user@server:/var/www/html/wp-content/themes/bamero/
```

#### Rollback Plugin Only
```bash
# Deploy previous plugin version
rsync -avz --delete wp-content/plugins/bamero-production-core/ user@server:/var/www/html/wp-content/plugins/bamero-production-core/
```

#### Rollback Specific Files
```bash
# Deploy specific files from previous commit
git checkout PREVIOUS_COMMIT -- wp-config.php
rsync -avz wp-config.php user@server:/var/www/html/wp-config.php
```

---

## 5. Emergency Procedures

### 5.1 Site Down

**Symptoms:**
- HTTP 500 errors
- Database connection errors
- White screen of death

**Steps:**

1. **Check Server Status**
```bash
# Check if server is running
ping yourdomain.com

# Check web server
curl -I https://yourdomain.com
```

2. **Check PHP Errors**
```bash
# Check PHP error log
ssh user@server "tail -n 50 /var/log/php8.2-fpm.log"
```

3. **Check Web Server Logs**
```bash
# Check Nginx error log
ssh user@server "tail -n 50 /var/log/nginx/error.log"

# Check Apache error log
ssh user@server "tail -n 50 /var/log/apache2/error.log"
```

4. **Check Database Status**
```bash
# Check if MySQL is running
ssh user@server "systemctl status mysql"

# Check database connectivity
mysql -u DB_USER -p -e "SELECT 1"
```

5. **Restart Services**
```bash
# Restart PHP-FPM
ssh user@server "sudo systemctl restart php8.2-fpm"

# Restart web server
ssh user@server "sudo systemctl restart nginx"
# or
ssh user@server "sudo systemctl restart apache2"

# Restart MySQL
ssh user@server "sudo systemctl restart mysql"
```

6. **Enable Maintenance Mode**
```bash
# Create maintenance file
ssh user@server "touch /var/www/html/.maintenance"

# Or via WordPress
wp maintenance-mode activate
```

7. **Rollback to Previous Version**
```bash
# Use rollback procedure (Section 4)
```

8. **Contact Support**
```bash
# If issue persists, contact hosting support
```

---

### 5.2 Payment Issues

**Symptoms:**
- Payment failures
- Callback errors
- Duplicate payments

**Steps:**

1. **Check ZarinPal Status**
```bash
# Check ZarinPal API status
curl -I https://payment.zarinpal.com/pg/v4
```

2. **Check Payment Logs**
```bash
# Check for payment-related errors
grep -r "zarinpal\|payment" /var/log/php8.2-fpm.log
```

3. **Check Database Orders**
```bash
# Check recent orders
wp db query "SELECT * FROM wp_bamero_posts WHERE post_type = 'shop_order' ORDER BY post_date DESC LIMIT 10"

# Check order meta
wp db query "SELECT * FROM wp_bamero_postmeta WHERE meta_key LIKE '%zarinpal%' ORDER BY meta_id DESC LIMIT 10"
```

4. **Test Payment in Sandbox**
```bash
# Test with sandbox credentials
# Verify callback URL is correct
```

5. **Check Callback URL**
```bash
# Verify callback URL in ZarinPal merchant panel
# Should be: https://yourdomain.com/wc-api/bamero_zarinpal
```

6. **Check Idempotency**
```bash
# Verify idempotency keys are stored
wp db query "SELECT * FROM wp_bamero_postmeta WHERE meta_key = '_idempotency_key'"
```

---

### 5.3 SMS Issues

**Symptoms:**
- OTP not received
- SMS delivery failures
- Queue not processing

**Steps:**

1. **Check SMS Provider Status**
```bash
# Check SMS.ir API status
curl -I https://api.sms.ir/v1
```

2. **Check SMS Logs**
```bash
# Check for SMS-related errors
grep -r "sms\|otp" /var/log/php8.2-fpm.log
```

3. **Check Notification Outbox**
```bash
# Check queued notifications
wp db query "SELECT * FROM wp_bamero_notification_outbox WHERE status = 'pending' LIMIT 10"
```

4. **Test SMS in Sandbox**
```bash
# Enable sandbox mode
BAMERO_ENVIRONMENT=staging
BAMERO_ENABLE_SMS_IN_STAGING=true

# Test OTP request
```

5. **Check SMS Credentials**
```bash
# Verify SMS.ir API key is correct
# Verify template IDs are correct
```

6. **Process Queue Manually**
```bash
# Trigger notification worker
wp cron event run bamero_notification_worker
```

---

### 5.4 Security Incident

**Symptoms:**
- Suspicious activity
- Unauthorized access
- Data breach

**Steps:**

1. **Isolate System**
```bash
# Disable public access (temporarily)
ssh user@server "iptables -A INPUT -p tcp --dport 80 -j DROP"
ssh user@server "iptables -A INPUT -p tcp --dport 443 -j DROP"
```

2. **Preserve Evidence**
```bash
# Create backup of logs
ssh user@server "tar -czvf /tmp/logs-$(date +%Y%m%d).tar.gz /var/log"

# Download logs
scp user@server:/tmp/logs-*.tar.gz ./
```

3. **Change All Credentials**
```bash
# Rotate all passwords and keys:
# - Database passwords
# - WordPress salts
# - API keys
# - SSH keys
# - GitHub tokens
```

4. **Scan for Malware**
```bash
# Run malware scan
ssh user@server "clamscan -r /var/www/html"
```

5. **Check for Suspicious Files**
```bash
# Check for recently modified files
ssh user@server "find /var/www/html -type f -mtime -1"

# Check for suspicious PHP files
ssh user@server "find /var/www/html -name '*.php' -exec grep -l 'eval\|base64_decode\|gzinflate' {} \;"
```

6. **Restore from Backup**
```bash
# Restore from known-good backup
mysql -u DB_USER -p DB_NAME < /path/to/clean-backup.sql
rsync -avz /path/to/clean-code/ user@server:/var/www/html
```

7. **Contact Security Team**
```bash
# Notify security team immediately
```

---

## 6. Troubleshooting Guide

### 6.1 Common Issues

#### Issue: White Screen of Death (WSOD)

**Symptoms:** Blank white page, no error message

**Causes:**
- PHP syntax error
- PHP fatal error
- Memory limit exceeded

**Debugging:**
```bash
# Enable WP_DEBUG
# In wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# Check debug log
ssh user@server "tail -n 50 /var/www/html/wp-content/debug.log"

# Check PHP error log
ssh user@server "tail -n 50 /var/log/php8.2-fpm.log"
```

**Solution:**
- Fix the syntax error
- Increase memory limit
- Check for plugin conflicts

---

#### Issue: HTTP 500 Error

**Symptoms:** Internal Server Error

**Causes:**
- .htaccess error
- PHP error
- Permission issue
- Missing file

**Debugging:**
```bash
# Check web server error log
ssh user@server "tail -n 50 /var/log/nginx/error.log"

# Check PHP error log
ssh user@server "tail -n 50 /var/log/php8.2-fpm.log"

# Test .htaccess
mv .htaccess .htaccess.bak
curl -I https://yourdomain.com
mv .htaccess.bak .htaccess
```

**Solution:**
- Fix .htaccess syntax
- Fix PHP errors
- Fix file permissions
- Restore missing files

---

#### Issue: Database Connection Error

**Symptoms:** "Error establishing a database connection"

**Causes:**
- Incorrect database credentials
- Database server down
- Network issue

**Debugging:**
```bash
# Check database credentials in wp-config.php
# Verify database server is running
ssh user@server "systemctl status mysql"

# Test database connection
mysql -u DB_USER -p -h DB_HOST -e "SELECT 1"

# Check MySQL error log
ssh user@server "tail -n 50 /var/log/mysql/error.log"
```

**Solution:**
- Fix database credentials
- Restart MySQL service
- Check network connectivity

---

#### Issue: Payment Not Working

**Symptoms:** Payment fails, callback not received

**Causes:**
- Incorrect ZarinPal credentials
- Callback URL misconfigured
- Network issue
- SSL certificate issue

**Debugging:**
```bash
# Check ZarinPal API response
curl -X POST https://payment.zarinpal.com/pg/v4/payment/request.json \
  -H "Content-Type: application/json" \
  -d '{"merchant_id": "YOUR_MERCHANT_ID", "amount": 1000, "currency": "IRR", "description": "Test", "callback_url": "https://yourdomain.com/wc-api/bamero_zarinpal"}'

# Check callback URL accessibility
curl -I https://yourdomain.com/wc-api/bamero_zarinpal

# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com | openssl x509 -noout -dates
```

**Solution:**
- Fix ZarinPal credentials
- Fix callback URL
- Renew SSL certificate
- Check network connectivity

---

#### Issue: OTP Not Received

**Symptoms:** User doesn't receive OTP SMS

**Causes:**
- SMS provider issue
- Rate limiting
- Incorrect mobile number
- Queue not processing

**Debugging:**
```bash
# Check SMS provider API
curl -I https://api.sms.ir/v1

# Check notification outbox
wp db query "SELECT * FROM wp_bamero_notification_outbox WHERE template_key = 'login_otp' ORDER BY created_at DESC LIMIT 10"

# Check rate limiting
wp transient get bamero_otp_rate_p_*

# Test OTP manually
wp user meta get 1 bamero_verified_mobile
```

**Solution:**
- Check SMS provider credentials
- Check rate limiting settings
- Process queue manually
- Test with different mobile number

---

#### Issue: Slow Performance

**Symptoms:** Slow page load, high server load

**Causes:**
- Database queries
- PHP execution
- External API calls
- Caching issues

**Debugging:**
```bash
# Check server load
ssh user@server "uptime"
ssh user@server "top -b -n 1"

# Check MySQL slow queries
ssh user@server "tail -n 20 /var/log/mysql/mysql-slow.log"

# Check PHP slow requests
ssh user@server "tail -n 20 /var/log/php8.2-fpm-slow.log"

# Measure response time
curl -o /dev/null -s -w "Total: %{time_total}s\n" https://yourdomain.com
```

**Solution:**
- Optimize database queries
- Enable caching
- Optimize PHP code
- Check external API performance

---

### 6.2 Error Code Reference

| **Error Code** | **Meaning** | **Action** |
|----------------|-------------|------------|
| HTTP 200 | OK | None |
| HTTP 301 | Moved Permanently | Update links |
| HTTP 302 | Found (Redirect) | Follow redirect |
| HTTP 400 | Bad Request | Check request |
| HTTP 401 | Unauthorized | Authenticate |
| HTTP 403 | Forbidden | Check permissions |
| HTTP 404 | Not Found | Check URL |
| HTTP 429 | Too Many Requests | Wait and retry |
| HTTP 500 | Internal Server Error | Check logs |
| HTTP 502 | Bad Gateway | Check proxy |
| HTTP 503 | Service Unavailable | Check server |
| HTTP 504 | Gateway Timeout | Check timeout |

---

## 7. Maintenance Tasks

### 7.1 Regular Maintenance

| **Task** | **Frequency** | **Command** |
|----------|--------------|-------------|
| Database Backup | Daily | `mysqldump -u DB_USER -p DB_NAME > backup.sql` |
| Log Rotation | Daily | `logrotate /etc/logrotate.conf` |
| Cache Clear | Weekly | `wp cache flush` |
| Plugin Update | Monthly | `wp plugin update --all` |
| Theme Update | Monthly | Manual check |
| WordPress Update | As needed | Manual check |
| Security Scan | Weekly | Run security-audit workflow |
| Health Check | Every 30 min | Automatic (health-check workflow) |

### 7.2 Database Maintenance

```bash
# Optimize tables
wp db optimize

# Repair tables
wp db repair

# Check database size
wp db size

# Clean up revisions
wp post delete $(wp post list --post_type='revision' --format=ids) --force
```

### 7.3 Cache Maintenance

```bash
# Clear object cache
wp cache flush

# Clear Redis cache
redis-cli -h 127.0.0.1 -p 6379 FLUSHALL

# Check Redis memory usage
redis-cli -h 127.0.0.1 -p 6379 INFO memory
```

### 7.4 Log Maintenance

```bash
# View recent errors
ssh user@server "tail -n 100 /var/log/php8.2-fpm.log | grep ERROR"

# Archive old logs
ssh user@server "tar -czvf /var/log/old-logs-$(date +%Y%m%d).tar.gz /var/log/*.log.*"

# Check disk space
ssh user@server "df -h"
```

---

## 8. Contact Information

### 8.1 Team Contacts

| **Role** | **Name** | **Email** | **Phone** |
|----------|----------|-----------|-----------|
| Project Manager | - | - | - |
| Lead Developer | - | - | - |
| DevOps Engineer | - | - | - |
| QA Engineer | - | - | - |

### 8.2 External Contacts

| **Service** | **Contact** | **Support URL** |
|------------|-------------|----------------|
| GitHub | GitHub Support | https://support.github.com |
| ZarinPal | ZarinPal Support | https://www.zarinpal.com/support |
| SMS.ir | SMS.ir Support | https://www.sms.ir/support |
| Hosting | Your Hosting Provider | Check your hosting panel |

### 8.3 Emergency Contacts

| **Issue Type** | **Contact** | **Response Time** |
|---------------|-------------|-------------------|
| Security Incident | Security Team | Immediate |
| Payment Issues | ZarinPal Support | < 1 hour |
| SMS Issues | SMS.ir Support | < 1 hour |
| Server Issues | Hosting Support | < 30 min |

---

## 📝 Version History

| **Version** | **Date** | **Author** | **Changes** |
|------------|----------|-----------|-------------|
| 1.0.0 | 2024-12-19 | Vibe Code Agent | Initial version |

---

## 🔗 Related Documents

- [CI/CD Documentation](CI_CD_DOCUMENTATION.md)
- [Deployment Architecture](DEPLOYMENT_ARCHITECTURE.md)
- [Go-Live Report](GO_LIVE_REPORT.md)
- [Workflow Files](.github/workflows/)

---

**Document Version:** 1.0.0  
**Last Updated:** 2024-12-19  
**Author:** Vibe Code Agent  
**Status:** ✅ Approved for Use