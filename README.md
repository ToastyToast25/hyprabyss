# 🚀 HyperAbyss ARK Cluster Website

Modern, high-performance website for ARK: Survival Ascended server clusters with real-time monitoring, player analytics, and Discord integration.

## 🎯 Features

- **Real-time Server Monitoring** - Live player counts, server status, and performance metrics
- **Modern PHP 8.4** - Type-safe code with enums, readonly classes, and strict typing
- **Unified API System** - Single endpoint handling all server data
- **Responsive Design** - Mobile-first with accessibility features
- **Discord Integration** - Live member counts and community stats
- **Player Analytics** - Track unique players, peak counts, and trends
- **Performance Optimized** - Built-in caching, health monitoring
- **Security Enhanced** - Rate limiting, input validation, SQL injection protection

## 📋 Requirements

### Windows (Laragon)
- Windows 10/11
- [Laragon](https://laragon.org/download/) (Latest version)
- PHP 8.2+ 
- MySQL 8.0+
- Administrator privileges

### Linux (Ubuntu/Debian)
- Ubuntu 20.04+ or Debian 11+
- PHP 8.2+
- MySQL 8.0+
- Nginx
- Root access

## 🚀 Quick Installation

### Windows (Laragon)

1. **Download & Run Setup**
   ```cmd
   # Download the project
   git clone https://github.com/your-repo/hyperabyss.git
   cd hyperabyss
   
   # Run as Administrator
   setup\install-windows.bat
   ```

2. **Start Laragon Services**
   - Open Laragon
   - Click "Start All"

3. **Visit Your Site**
   - HTTP: `http://hyperabyss.test`
   - HTTPS: `https://hyperabyss.test`

### Linux (Ubuntu/Debian)

1. **Download & Run Setup**
   ```bash
   # Download the project
   git clone https://github.com/your-repo/hyperabyss.git
   cd hyperabyss
   
   # Run setup script
   sudo bash setup/install-linux.sh
   ```

2. **Follow Setup Prompts**
   - Enter your domain name
   - Choose SSL option
   - Wait for completion

## 🔧 Manual Configuration

### 1. Environment Setup

Edit `.env` file:
```env
# Database
DB_HOST=localhost
DB_NAME=hyperabyss_cluster
DB_USER=your_user
DB_PASS=your_password

# RCON Passwords (update these!)
RAGNAROK_RCON_PASSWORD=your_rcon_password
THEISLAND_RCON_PASSWORD=your_rcon_password
THECENTER_RCON_PASSWORD=your_rcon_password
```

### 2. Server Configuration

Edit `servers.json`:
```json
{
  "servers": [
    {
      "key": "ragnarok",
      "name": "Your Server Name",
      "ip": "your.server.ip",
      "port": 7777,
      "rcon_port": 27015,
      "rcon_password_env": "RAGNAROK_RCON_PASSWORD"
    }
  ]
}
```

### 3. Database Setup

```sql
# Create database
CREATE DATABASE hyperabyss_cluster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Import schema
mysql hyperabyss_cluster < db/schema.sql
```

## 🔐 SSL/HTTPS Setup

### Option 1: Cloudflare Origin Certificate (Recommended)

1. **Generate Certificate in Cloudflare Dashboard**
   - Go to SSL/TLS → Origin Server
   - Create Certificate
   - Choose RSA, 15 years
   - Download certificate files

2. **Windows (Laragon)**
   ```cmd
   # Copy certificates to Laragon
   copy cloudflare.pem C:\laragon\etc\ssl\
   copy cloudflare.key C:\laragon\etc\ssl\
   ```

3. **Linux**
   ```bash
   # Create SSL directory
   sudo mkdir -p /etc/ssl/cloudflare
   
   # Copy certificates
   sudo cp cloudflare.pem /etc/ssl/cloudflare/cert.pem
   sudo cp cloudflare.key /etc/ssl/cloudflare/key.pem
   sudo chmod 600 /etc/ssl/cloudflare/*
   ```

4. **Update Nginx Config (Linux)**
   ```nginx
   server {
       listen 443 ssl http2;
       server_name yourdomain.com;
       
       ssl_certificate /etc/ssl/cloudflare/cert.pem;
       ssl_certificate_key /etc/ssl/cloudflare/key.pem;
       
       # Cloudflare SSL settings
       ssl_protocols TLSv1.2 TLSv1.3;
       ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
       ssl_prefer_server_ciphers off;
       
       # ... rest of config
   }
   ```

5. **Set Cloudflare SSL Mode**
   - In Cloudflare dashboard: SSL/TLS → Overview
   - Set to "Full (strict)"

### Option 2: Let's Encrypt (Linux only)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### Option 3: Self-Signed (Development)

**Windows:**
```cmd
# In Laragon, SSL is auto-configured for .test domains
```

**Linux:**
```bash
# Generate self-signed certificate
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/selfsigned.key \
  -out /etc/ssl/certs/selfsigned.crt
```

## 📅 Cron Jobs / Scheduled Tasks

### Windows (Task Scheduler)

Run `setup\setup-cron.bat` as Administrator, or manually:

```cmd
# Server monitoring (every minute)
schtasks /create /tn "HyperAbyss_Monitor" /tr "php.exe C:\laragon\www\hyperabyss\scripts\monitoring.php" /sc minute /mo 1

# Analytics update (every 5 minutes)
schtasks /create /tn "HyperAbyss_Analytics" /tr "php.exe C:\laragon\www\hyperabyss\scripts\analytics-tracker.php" /sc minute /mo 5

# Discord sync (every hour)
schtasks /create /tn "HyperAbyss_Discord" /tr "php.exe C:\laragon\www\hyperabyss\scripts\discord-sync.php" /sc hourly

# Cleanup (daily at 2 AM)
schtasks /create /tn "HyperAbyss_Cleanup" /tr "php.exe C:\laragon\www\hyperabyss\scripts\cleanup.php" /sc daily /st 02:00
```

### Linux (Crontab)

Add to `/etc/cron.d/hyperabyss`:
```bash
# Server monitoring
*/1 * * * * www-data /usr/bin/php /var/www/hyperabyss/scripts/monitoring.php

# Analytics tracking
*/5 * * * * www-data /usr/bin/php /var/www/hyperabyss/scripts/analytics-tracker.php

# Discord sync
0 */6 * * * www-data /usr/bin/php /var/www/hyperabyss/scripts/discord-sync.php

# Daily cleanup
0 2 * * * www-data /usr/bin/php /var/www/hyperabyss/scripts/cleanup.php
```

## 🌐 Web Server Configuration

### Nginx (Production)

```nginx
server {
    listen 80;
    listen [::]:80;
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/hyperabyss;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/ssl/cloudflare/cert.pem;
    ssl_certificate_key /etc/ssl/cloudflare/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com cdnjs.cloudflare.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';" always;
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    
    # Hide server version
    server_tokens off;
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=general:10m rate=30r/s;
    
    # API routing with rate limiting
    location ~* ^/api/(.*)$ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri /api/enhanced-api.php?endpoint=$1&$query_string;
    }
    
    # PHP handling
    location ~ \.php$ {
        limit_req zone=general burst=50 nodelay;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm-hyperabyss.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS on;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
    }
    
    # Static files with aggressive caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        access_log off;
        
        # Compression
        gzip_static on;
    }
    
    # Deny access to sensitive files
    location ~* \.(env|sql|log|dat|md)$ {
        deny all;
        return 404;
    }
    
    # Pretty URLs
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Deny access to hidden files
    location ~ /\. {
        deny all;
        return 404;
    }
    
    # Block common attack patterns
    location ~* (wp-admin|wp-login|xmlrpc|wp-config) {
        deny all;
        return 404;
    }
}
```

### Apache (.htaccess)

```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security Headers
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"

# API routing
RewriteRule ^api/(.*)$ /api/enhanced-api.php?endpoint=$1 [QSA,L]

# Static file caching
<FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$">
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
    Header append Cache-Control "public, immutable"
    Header append Vary "Accept-Encoding"
</FilesMatch>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Deny access to sensitive files
<FilesMatch "\.(env|sql|log|dat|md)$">
    Require all denied
</FilesMatch>

# Pretty URLs
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php?$1 [QSA,L]
```

## 🔍 Monitoring & Maintenance

### Health Check URLs

- **System Health**: `https://yourdomain.com/api/health-check.php`
- **API Status**: `https://yourdomain.com/api/enhanced-api.php?endpoint=health`
- **Server Status**: `https://yourdomain.com/api/enhanced-api.php?endpoint=servers`

### Log Files

```bash
# Application logs
tail -f logs/api.log
tail -f logs/errors.log

# Web server logs (Linux)
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# PHP logs
tail -f /var/log/php8.2-fpm.log
```

### Performance Monitoring

```bash
# Check system resources
htop
df -h
free -h

# Monitor database
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW STATUS LIKE 'Connections';"

# Check web server status
systemctl status nginx
systemctl status php8.2-fpm
```

## 🐛 Troubleshooting

### Common Issues

**1. API Not Working**
```bash
# Check file permissions
ls -la api/enhanced-api.php

# Check PHP errors
tail -f logs/errors.log

# Test API directly
curl https://yourdomain.com/api/enhanced-api.php?endpoint=health
```

**2. Database Connection Failed**
```bash
# Test MySQL connection
mysql -u username -p -h localhost

# Check database exists
mysql -e "SHOW DATABASES;"

# Verify user permissions
mysql -e "SHOW GRANTS FOR 'username'@'localhost';"
```

**3. RCON Connection Issues**
```bash
# Test RCON connectivity
telnet server_ip rcon_port

# Check server logs
tail -f logs/api.log | grep RCON

# Verify RCON passwords in .env
```

**4. SSL Certificate Issues**
```bash
# Test SSL certificate
openssl s_client -connect yourdomain.com:443

# Check certificate expiry
openssl x509 -in /etc/ssl/cloudflare/cert.pem -text -noout

# Verify Nginx config
nginx -t
```

### Debug Mode

Enable debug mode in `.env`:
```env
DEBUG_MODE=true
LOG_LEVEL=DEBUG
```

## 🔒 Security Best Practices

### File Permissions
```bash
# Application files
chmod 644 *.php
chmod 755 directories/

# Sensitive files
chmod 600 .env
chmod 644 servers.json

# Log directory
chmod 777 logs/
```

### Database Security
```sql
-- Remove test databases
DROP DATABASE IF EXISTS test;

-- Secure MySQL user
CREATE USER 'hyperabyss_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT,INSERT,UPDATE,DELETE ON hyperabyss_cluster.* TO 'hyperabyss_user'@'localhost';
FLUSH PRIVILEGES;
```

### Firewall Rules (Linux)
```bash
# Allow only necessary ports
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

## 📊 Performance Optimization

### PHP Optimization
```ini
; php.ini optimizations
memory_limit = 256M
max_execution_time = 60
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
```

### MySQL Optimization
```sql
-- my.cnf optimizations
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 64M
```

### Nginx Optimization
```nginx
# nginx.conf
worker_processes auto;
worker_connections 1024;
keepalive_timeout 65;
client_max_body_size 64M;

# Enable gzip
gzip on;
gzip_vary on;
gzip_types text/plain text/css application/json application/javascript;
```

## 🆕 Updates & Upgrades

### Update Process
```bash
# Backup current installation
cp -r /var/www/hyperabyss /var/www/hyperabyss-backup-$(date +%Y%m%d)

# Download latest version
git pull origin main

# Update database schema
mysql hyperabyss_cluster < db/schema.sql

# Clear caches
rm -rf logs/cache/*
systemctl reload php8.2-fpm nginx
```

### Version Information
- **Current Version**: 2.0.0
- **PHP Requirement**: 8.2+
- **MySQL Requirement**: 8.0+
- **Last Updated**: December 2024

## 📞 Support

### Getting Help
- **Documentation**: This README.md
- **Issues**: Create GitHub issue
- **Discord**: [Your Discord Server]
- **Email**: support@yourdomain.com

### System Requirements Check
```bash
# Check PHP version
php -v

# Check MySQL version
mysql --version

# Check web server
nginx -v
# or
apache2 -v

# Check SSL
openssl version
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **ARK: Survival Ascended** by Studio Wildcard
- **PHP Community** for modern language features
- **Nginx** for high-performance web serving
- **Cloudflare** for SSL and CDN services

---

**Made with ❤️ for the ARK community**