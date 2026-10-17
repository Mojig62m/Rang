# 🚀 Bamero CI/CD Documentation
## مستندات کامل CI/CD پروژه بامرو

---

## 📋 Document Information

**Version:** 1.0.0  
**Last Updated:** 2024-12-19  
**Author:** Vibe Code Agent  
**Repository:** Mojig62m/Rang  
**Status:** Production-Ready  

---

## 🎯 Table of Contents

1. [Introduction](#1-introduction)
2. [CI/CD Architecture](#2-cicd-architecture)
3. [Workflow Details](#3-workflow-details)
4. [Testing Strategy](#4-testing-strategy)
5. [Deployment Procedures](#5-deployment-procedures)
6. [Rollback Procedures](#6-rollback-procedures)
7. [Security Considerations](#7-security-considerations)
8. [Environment Configuration](#8-environment-configuration)
9. [Monitoring and Observability](#9-monitoring-and-observability)
10. [Maintenance and Updates](#10-maintenance-and-updates)

---

## 1. Introduction

### 1.1 Purpose

This document provides comprehensive CI/CD documentation for the Bamero WordPress/WooCommerce project, including:
- Workflow configurations
- Testing strategies
- Deployment procedures
- Rollback procedures
- Security considerations
- Environment configurations

### 1.2 Scope

The CI/CD pipeline covers:
- ✅ PHP syntax validation
- ✅ Code quality checks
- ✅ Security audits
- ✅ Health monitoring
- ✅ Automated deployment
- ✅ Post-deployment verification

### 1.3 Audience

- Developers
- DevOps engineers
- QA engineers
- Project managers

---

## 2. CI/CD Architecture

### 2.1 Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        CI/CD PIPELINE ARCHITECTURE                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐ │
│  │  Developer   │    │   GitHub    │    │   Staging    │    │  Production  │ │
│  │  Workstation │───►│   Actions    │───►│   Server     │───►│   Server     │ │
│  └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘ │
│           │                  │                  │                  │        │
│           │                  ▼                  ▼                  ▼        │
│           │          ┌─────────────────────────────────────────────────┐ │
│           │          │                    WORKFLOWS                        │ │
│           │          │  ┌─────────────┐  ┌─────────────┐               │ │
│           │          │  │   CI        │  │  Deploy      │               │ │
│           │          │  │   (Push/PR) │  │  (Push)      │               │ │
│           │          │  └─────────────┘  └─────────────┘               │ │
│           │          │  ┌─────────────┐  ┌─────────────┐               │ │
│           │          │  │Security Audit│  │ Health Check │               │ │
│           │          │  │ (Push/PR/Daily)│ │ (Push/30min)│               │ │
│           │          │  └─────────────┘  └─────────────┘               │ │
│           │          └─────────────────────────────────────────────────┘ │
│           │                              │                                  │
│           │                              ▼                                  │
│           │                  ┌─────────────────┐                          │
│           └──────────────────│   Artifacts     │◄─────────────────────────┘
│                              └─────────────────┘
│                                          │
│                                          ▼
│                              ┌─────────────────────────────────┐
│                              │         MONITORING                │
│                              │  - Error Tracking                 │
│                              │  - Performance Metrics             │
│                              │  - Alerting                       │
│                              └─────────────────────────────────┘
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Components

| **Component** | **Technology** | **Purpose** |
|---------------|----------------|-------------|
| CI/CD Platform | GitHub Actions | Automation engine |
| Version Control | Git | Source code management |
| Deployment | SSH + rsync | File transfer |
| Monitoring | GitHub Actions + Custom | Health checks |
| Notifications | Telegram | Alert delivery |

### 2.3 Workflow Files

| **File** | **Purpose** | **Triggers** |
|----------|-------------|--------------|
| `ci.yml` | Continuous Integration | Push, Pull Request |
| `deploy.yml` | Continuous Deployment | Push to main |
| `security-audit.yml` | Security Scanning | Push, PR, Daily |
| `health-check.yml` | Health Monitoring | Push, Every 30 min |

---

## 3. Workflow Details

### 3.1 CI Workflow (`ci.yml`)

**Purpose:** Validate code quality and functionality before deployment

**Triggers:**
- Push to any branch
- Pull request to any branch

**Jobs:**

#### Job 1: PHP Syntax Check
```yaml
- Name: PHP Syntax Check
- Purpose: Validate PHP syntax for all files
- Steps:
  1. Checkout code
  2. Setup PHP 8.2
  3. Run php -l on all .php files
- Failure: Blocks merge if syntax errors found
```

#### Job 2: PHP Lint & Code Style
```yaml
- Name: PHP Lint & Code Style
- Purpose: Check code against PSR12 standards
- Steps:
  1. Checkout code
  2. Setup PHP 8.2
  3. Install PHP_CodeSniffer
  4. Run phpcs with PSR12 standard
- Failure: Warning (does not block merge)
```

#### Job 3: Security Headers Check
```yaml
- Name: Security Headers & CSP
- Purpose: Verify security headers are configured
- Steps:
  1. Checkout code
  2. Check .htaccess for security headers
  3. Check production-core for CSP implementation
- Failure: Blocks merge if critical headers missing
```

#### Job 4: WooCommerce Compatibility
```yaml
- Name: WooCommerce Compatibility
- Purpose: Verify WooCommerce integration
- Steps:
  1. Checkout code
  2. Setup PHP 8.2
  3. Check for HPOS compatibility declarations
  4. Check for WooCommerce class usage
- Failure: Blocks merge if compatibility issues found
```

#### Job 5: Final Production Readiness
```yaml
- Name: Final Production Readiness
- Purpose: Overall validation
- Steps:
  1. Checkout code
  2. Verify file count (< 50 files)
  3. Check for non-production files
- Failure: Blocks merge if validation fails
```

**Dependencies:**
- All jobs run in parallel
- Final job requires all other jobs to pass

**Success Criteria:**
- ✅ All PHP files have valid syntax
- ✅ All security headers are present
- ✅ WooCommerce compatibility confirmed
- ✅ File structure validated
- ✅ No non-production files found

---

### 3.2 Deploy Workflow (`deploy.yml`)

**Purpose:** Automated deployment to production server

**Triggers:**
- Push to `main` branch
- Paths ignored: `.md`, `.gitignore`, `LICENSE`

**Environment:**
- Name: `production`
- URL: `${{ secrets.PRODUCTION_URL }}`

**Steps:**

1. **Checkout Code**
   ```yaml
   - uses: actions/checkout@v4
   ```

2. **Setup SSH**
   ```yaml
   - uses: webfactory/ssh-agent@v0.7.0
   - with:
       ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}
   ```

3. **Deploy to Server**
   ```yaml
   - rsync -avz --delete --exclude='.git' --exclude='.github' ./ 
     ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }}:${{ secrets.DEPLOY_PATH }}
   ```

4. **Set Permissions**
   ```yaml
   - chmod -R 755 wp-content
   - chmod 644 wp-config.php
   - chmod 644 .htaccess
   - chmod 644 robots.txt
   ```

5. **Post-Deploy Scripts**
   ```yaml
   - wp cache flush (if WP CLI available)
   - wp cron event run --all (if WP CLI available)
   ```

6. **Verify Deployment**
   ```yaml
   - curl -s -o /dev/null -w "%{http_code}" ${{ secrets.PRODUCTION_URL }}
   - Expect: HTTP 200
   - Retry: 10 times with 5 second interval
   ```

7. **Notifications**
   - Success: Telegram notification with commit info
   - Failure: Telegram notification with error info

**Required Secrets:**
```bash
SSH_PRIVATE_KEY      # RSA private key for deployment
SSH_HOST             # Production server hostname/IP
SSH_USER             # SSH username
DEPLOY_PATH         # Deployment path on server
PRODUCTION_URL       # Production site URL
TELEGRAM_BOT_TOKEN   # Telegram bot token (optional)
TELEGRAM_CHAT_ID     # Telegram chat ID (optional)
```

**Success Criteria:**
- ✅ All files deployed successfully
- ✅ Permissions set correctly
- ✅ Cache flushed
- ✅ Site responds with HTTP 200
- ✅ Notification sent

---

### 3.3 Security Audit Workflow (`security-audit.yml`)

**Purpose:** Comprehensive security scanning

**Triggers:**
- Push to any branch
- Pull request to any branch
- Daily at midnight (00:00 UTC)

**Jobs:**

#### Job 1: Security Scan
- **Hardcoded Credentials Check:** Scans for hardcoded passwords, API keys, secrets
- **Eval() Detection:** Checks for eval() function usage
- **Unescaped Output Check:** Verifies output escaping
- **Nonce Verification:** Checks for nonce usage in forms

#### Job 2: Rate Limiting Verification
- **Mobile Auth Rate Limiting:** Verifies rate limiting in mobile auth plugin
- **MySQL Locks:** Checks for GET_LOCK usage

#### Job 3: Database Security
- **Prepared Statements:** Verifies use of $wpdb->prepare
- **Table Prefix:** Checks for hardcoded table names

#### Job 4: File Permissions
- **.htaccess Blocks:** Verifies sensitive file blocks
- **Sensitive Files:** Checks for .env files in repository

**Success Criteria:**
- ✅ No hardcoded credentials
- ✅ No eval() usage
- ✅ Proper output escaping
- ✅ Nonce verification present
- ✅ Rate limiting implemented
- ✅ Prepared statements used
- ✅ Sensitive files blocked

---

### 3.4 Health Check Workflow (`health-check.yml`)

**Purpose:** Continuous health monitoring

**Triggers:**
- Push to any branch
- Every 30 minutes (scheduled)

**Jobs:**

#### Job 1: Health Monitor
- **Required Files Check:** Verifies all essential files exist
- **File Count Validation:** Ensures file count is optimal (< 50)
- **Non-Production Files Check:** Scans for development files
- **WordPress Structure:** Validates WordPress directory structure
- **Theme Structure:** Validates theme directory structure

#### Job 2: Plugin Validation
- **Required Plugins Check:** Verifies all required plugins exist
- **Plugin Headers:** Checks for valid plugin headers

#### Job 3: Configuration Validation
- **wp-config.php:** Validates database and security constants
- **.htaccess:** Validates rewrite rules and security headers
- **robots.txt:** Validates SEO configuration

**Success Criteria:**
- ✅ All required files present
- ✅ File count optimal
- ✅ No non-production files
- ✅ WordPress structure valid
- ✅ Theme structure valid
- ✅ All plugins present
- ✅ Configuration valid

---

## 4. Testing Strategy

### 4.1 Test Types

| **Test Type** | **Purpose** | **Execution** | **Frequency** |
|--------------|-------------|---------------|--------------|
| Syntax Check | PHP syntax validation | CI | Every push/PR |
| Linting | Code style validation | CI | Every push/PR |
| Security | Security vulnerability scanning | CI | Every push/PR + Daily |
| Health | System health monitoring | CI | Every push + 30 min |
| Integration | WooCommerce integration | Manual | Before deployment |
| End-to-End | Full workflow testing | Manual | Before production |

### 4.2 Test Environment

| **Environment** | **Purpose** | **Data** | **Access** |
|----------------|-------------|----------|-----------|
| Local | Development | Synthetic | Developer |
| CI | Validation | Synthetic | Automated |
| Staging | Pre-production | Synthetic | Team |
| Production | Live | Real | Restricted |

### 4.3 Test Data

**Approach:** Synthetic test data only

- ✅ No real customer data
- ✅ No real payment processing
- ✅ No real SMS sending (in staging)
- ✅ Isolated test database

### 4.4 Test Coverage

| **Component** | **Coverage** | **Status** |
|--------------|-------------|------------|
| Core PHP | Syntax + Linting | ✅ Complete |
| Security | Header + CSP | ✅ Complete |
| WooCommerce | Compatibility | ✅ Complete |
| Plugins | Structure | ✅ Complete |
| Theme | Structure | ✅ Complete |
| Database | Queries | ⚠️ Partial |
| Integration | Workflows | ⚠️ Manual |

---

## 5. Deployment Procedures

### 5.1 Standard Deployment

**Prerequisites:**
- ✅ All CI checks pass
- ✅ Code reviewed and approved
- ✅ Staging deployment successful
- ✅ GitHub Secrets configured

**Steps:**

1. **Commit Changes**
   ```bash
   git add .
   git commit -m "Your commit message"
   git push origin main
   ```

2. **CI Runs Automatically**
   - GitHub Actions triggers CI workflows
   - All checks must pass

3. **Deploy to Staging (Manual)**
   ```bash
   # Run staging deployment manually or via workflow
   gh workflow run deploy.yml --ref staging
   ```

4. **Test on Staging**
   - Verify all functionality
   - Test payment flow (sandbox)
   - Test SMS flow (sandbox)
   - Test admin functionality

5. **Deploy to Production**
   - Push to main triggers deployment
   - Manual approval may be required

6. **Post-Deployment Verification**
   - Check site health
   - Verify critical workflows
   - Monitor for errors

### 5.2 Emergency Deployment

**When to use:** Critical security fixes, payment issues

**Steps:**

1. **Create Hotfix Branch**
   ```bash
   git checkout -b hotfix/issue-description main
   ```

2. **Make Minimal Changes**
   - Only fix the specific issue
   - No new features
   - No refactoring

3. **Test Locally**
   ```bash
   # Run syntax check
   find . -name "*.php" -exec php -l {} \;
   ```

4. **Deploy to Staging**
   ```bash
   git push origin hotfix/issue-description
   # Trigger staging deployment
   ```

5. **Test on Staging**
   - Verify fix works
   - Verify no regressions

6. **Merge to Main**
   ```bash
   git checkout main
   git merge hotfix/issue-description
   git push origin main
   ```

7. **Deploy to Production**
   - Automatic via CI/CD

---

## 6. Rollback Procedures

### 6.1 Git-Based Rollback

**When to use:** Deployment issues, critical bugs

**Steps:**

1. **Identify Previous Good Commit**
   ```bash
   git log --oneline --grep="stable\|production" -n 10
   ```

2. **Create Rollback Branch**
   ```bash
   git checkout -b rollback/to-previous-stable GOOD_COMMIT_HASH
   ```

3. **Deploy Rollback Version**
   ```bash
   git push origin rollback/to-previous-stable
   # Trigger deployment to production
   ```

4. **Verify Rollback**
   - Check site functionality
   - Verify critical workflows
   - Monitor for errors

5. **Merge to Main (Optional)**
   ```bash
   git checkout main
   git merge rollback/to-previous-stable
   git push origin main
   ```

### 6.2 Database Rollback

**Warning:** Database rollback is **NOT** automatic with code rollback

**When to use:** Database migration issues

**Steps:**

1. **Restore from Backup**
   ```bash
   # Use hosting provider's backup system
   # Or restore from local backup
   mysql -u USER -p DATABASE < backup.sql
   ```

2. **Verify Data Integrity**
   - Check order data
   - Check user data
   - Check product data

3. **Re-deploy Application**
   - Deploy code version compatible with restored database

**Important:**
- ❌ Never overwrite production database with staging/development database
- ❌ Database rollback requires separate procedure from code rollback
- ❌ Test database restore procedure regularly

### 6.3 Rollback Testing

**Frequency:** Before every major deployment

**Steps:**

1. **Deploy to Staging**
   ```bash
   # Deploy current version
   ```

2. **Simulate Rollback**
   ```bash
   # Deploy previous version
   ```

3. **Verify Compatibility**
   - Check database compatibility
   - Check plugin compatibility
   - Check theme compatibility

4. **Document Results**
   - Record rollback time
   - Record any issues
   - Update rollback procedures

---

## 7. Security Considerations

### 7.1 Secrets Management

**Approach:** GitHub Secrets + Environment Variables

**Never commit:**
- ❌ Database credentials
- ❌ API keys
- ❌ Private keys
- ❌ Passwords
- ❌ Secrets

**Store in GitHub Secrets:**
```bash
# Database
DB_NAME
DB_USER
DB_PASSWORD
DB_HOST

# WordPress
AUTH_KEY
SECURE_AUTH_KEY
LOGGED_IN_KEY
NONCE_KEY
AUTH_SALT
SECURE_AUTH_SALT
LOGGED_IN_SALT
NONCE_SALT

# Payment
ZARINPAL_MERCHANT_ID
ZARINPAL_API_BASE_URL
ZARINPAL_STARTPAY_URL

# SMS
SMS_IR_API_KEY
SMS_IR_API_BASE_URL
SMS_IR_TEMPLATE_*

# Deployment
SSH_PRIVATE_KEY
SSH_HOST
SSH_USER
DEPLOY_PATH
PRODUCTION_URL

# Notifications
TELEGRAM_BOT_TOKEN
TELEGRAM_CHAT_ID
```

### 7.2 Deployment Security

**Best Practices:**
- ✅ Use least-privilege deployment credentials
- ✅ Use short-lived credentials when possible
- ✅ Separate CI permissions from production deployment
- ✅ Require manual approval for production deployment
- ✅ Deploy only verified artifacts
- ✅ Use atomic deployment mechanism

### 7.3 Runtime Security

**Implemented:**
- ✅ CSP Level 3 with nonce
- ✅ HSTS with max-age=31536000
- ✅ Security headers (X-Content-Type-Options, X-Frame-Options, etc.)
- ✅ Rate limiting (5 requests/hour/phone)
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Nonce verification
- ✅ XML-RPC disabled
- ✅ Author enumeration blocked

---

## 8. Environment Configuration

### 8.1 Environment Types

| **Environment** | **Purpose** | **Configuration** | **Data** |
|----------------|-------------|-----------------|----------|
| Production | Live site | Production settings | Real data |
| Staging | Pre-production testing | Staging settings | Synthetic data |
| Development | Local development | Development settings | Synthetic data |
| CI | Automated testing | CI settings | Synthetic data |

### 8.2 Configuration Files

| **File** | **Purpose** | **Environment** |
|----------|-------------|----------------|
| `wp-config.php` | WordPress configuration | All |
| `.htaccess` | Web server configuration | All |
| `robots.txt` | SEO configuration | All |
| `.github/workflows/*.yml` | CI/CD configuration | GitHub Actions |

### 8.3 Environment Variables

**Production:**
```bash
WP_ENVIRONMENT_TYPE=production
WP_DEBUG=false
WP_DEBUG_LOG=false
DISABLE_WP_CRON=true
WP_CACHE=true
BAMERO_ENVIRONMENT=production
BAMERO_ENABLE_PAYMENT_IN_STAGING=false
BAMERO_ENABLE_SMS_IN_STAGING=false
```

**Staging:**
```bash
WP_ENVIRONMENT_TYPE=staging
WP_DEBUG=true
WP_DEBUG_LOG=true
DISABLE_WP_CRON=false
WP_CACHE=true
BAMERO_ENVIRONMENT=staging
BAMERO_ENABLE_PAYMENT_IN_STAGING=true
BAMERO_ENABLE_SMS_IN_STAGING=true
```

**Development:**
```bash
WP_ENVIRONMENT_TYPE=development
WP_DEBUG=true
WP_DEBUG_LOG=true
DISABLE_WP_CRON=false
WP_CACHE=false
BAMERO_ENVIRONMENT=development
```

---

## 9. Monitoring and Observability

### 9.1 Monitoring Strategy

| **Metric** | **Tool** | **Frequency** | **Threshold** |
|------------|----------|--------------|--------------|
| HTTP Errors | GitHub Actions | Every 30 min | < 1% |
| Response Time | Custom | Every 30 min | < 2s |
| Database Queries | Custom | Every 30 min | < 100/req |
| Memory Usage | Custom | Every 30 min | < 256MB |
| Payment Failures | Custom | Real-time | 0 |
| SMS Failures | Custom | Real-time | < 1% |

### 9.2 Alerting

**Channels:**
- ✅ Telegram (via GitHub Actions)
- ⚠️ Email (optional, via SMTP)
- ⚠️ Slack (optional, via webhook)

**Alert Levels:**
- **Critical:** Payment failures, database errors
- **High:** Deployment failures, high error rates
- **Medium:** Performance degradation, warnings
- **Low:** Informational messages

### 9.3 Logging

**Centralized Logging:**
- ✅ Application logs (via `bamero_log_event()`)
- ✅ Error logs (PHP error_log)
- ✅ Access logs (web server)
- ✅ Database logs (MySQL slow query log)

**Log Retention:**
- Application logs: 30 days
- Error logs: 90 days
- Access logs: 90 days
- Database logs: 30 days

---

## 10. Maintenance and Updates

### 10.1 Workflow Updates

**When to update:**
- New PHP version support
- New WordPress/WooCommerce version
- New security requirements
- New testing requirements

**Process:**
1. Test changes in feature branch
2. Verify all workflows pass
3. Merge to main
4. Monitor first few deployments

### 10.2 Dependency Updates

**Schedule:** Monthly

**Process:**
1. Check for updates (GitHub Dependabot)
2. Test updates in feature branch
3. Verify compatibility
4. Merge to main

### 10.3 Security Updates

**Schedule:** Immediately for critical vulnerabilities

**Process:**
1. Assess vulnerability impact
2. Apply security patch
3. Test thoroughly
4. Deploy to production ASAP

---

## 📊 Quick Reference

### Workflow Status Badges

```markdown
![CI](https://github.com/Mojig62m/Rang/actions/workflows/ci.yml/badge.svg)
![Deploy](https://github.com/Mojig62m/Rang/actions/workflows/deploy.yml/badge.svg)
![Security Audit](https://github.com/Mojig62m/Rang/actions/workflows/security-audit.yml/badge.svg)
![Health Check](https://github.com/Mojig62m/Rang/actions/workflows/health-check.yml/badge.svg)
```

### Common Commands

```bash
# Check workflow status
gh workflow list
gh workflow view CI_ID

# Trigger workflow manually
gh workflow run WORKFLOW_FILE.yml

# View workflow logs
gh run view RUN_ID

# Check secrets
gh secret list

# Set secret
gh secret set SECRET_NAME -b "secret_value"
```

### File Locations

```
.github/
├── workflows/
│   ├── ci.yml                  # Continuous Integration
│   ├── deploy.yml              # Continuous Deployment
│   ├── security-audit.yml      # Security Scanning
│   └── health-check.yml        # Health Monitoring
```

---

## 🎓 Best Practices

### CI/CD Best Practices

✅ **Automate Everything** - Manual processes are error-prone  
✅ **Fail Fast** - Detect issues as early as possible  
✅ **Test in Isolation** - Use synthetic data, not production data  
✅ **Version Everything** - Code, dependencies, artifacts  
✅ **Monitor Continuously** - Catch issues before users do  
✅ **Document Procedures** - For deployment, rollback, recovery  

### Security Best Practices

✅ **Never Commit Secrets** - Use GitHub Secrets  
✅ **Least Privilege** - Minimum necessary permissions  
✅ **Separate Environments** - Production ≠ Staging ≠ Development  
✅ **Audit Regularly** - Review access, permissions, secrets  
✅ **Rotate Credentials** - Change passwords/keys regularly  

### Deployment Best Practices

✅ **Deploy to Staging First** - Always test before production  
✅ **Use Atomic Deployments** - All or nothing  
✅ **Have Rollback Plan** - And test it regularly  
✅ **Monitor After Deployment** - Verify health and functionality  
✅ **Document Changes** - Keep changelog up to date  

---

## 📝 Version History

| **Version** | **Date** | **Author** | **Changes** |
|------------|----------|-----------|-------------|
| 1.0.0 | 2024-12-19 | Vibe Code Agent | Initial version |

---

## 🔗 Related Documents

- [Deployment Architecture](DEPLOYMENT_ARCHITECTURE.md)
- [Go-Live Report](GO_LIVE_REPORT.md)
- [Workflow Files](.github/workflows/)

---

**Document Version:** 1.0.0  
**Last Updated:** 2024-12-19  
**Author:** Vibe Code Agent  
**Status:** ✅ Approved for Use