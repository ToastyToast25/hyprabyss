#!/bin/bash

# HyperAbyss ARK Cluster - Linux/Ubuntu Server Setup Script
# Run with: sudo bash install-linux.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="hyperabyss"
WEB_ROOT="/var/www"
PROJECT_PATH="$WEB_ROOT/$PROJECT_NAME"
PHP_VERSION="8.2"
MYSQL_VERSION="8.0"

echo -e "${BLUE}========================================"
echo -e " HyperAbyss ARK Cluster Setup"
echo -e " Linux/Ubuntu Server Installation"
echo -e "========================================${NC}"
echo

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}Error: This script must be run as root (use sudo)${NC}"
   exit 1
fi

# Get the real user (in case of sudo)
REAL_USER=${SUDO_USER:-$USER}
REAL_HOME=$(eval echo ~$REAL_USER)

echo -e "${BLUE}Step 1: Updating system packages...${NC}"
apt update && apt upgrade -y
echo -e "${GREEN}✓ System updated${NC}"

echo -e "${BLUE}Step 2: Installing required packages...${NC}"
apt install -y software-properties-common curl wget git unzip

# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.2+
apt install -y \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-json \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-opcache

echo -e "${GREEN}✓ PHP ${PHP_VERSION} installed${NC}"

echo -e "${BLUE}Step 3: Installing MySQL...${NC}"
apt install -y mysql-server mysql-client

# Secure MySQL installation
mysql_secure_installation

echo -e "${GREEN}✓ MySQL installed${NC}"

echo -e "${BLUE}Step 4: Installing Nginx...${NC}"
apt install -y nginx

# Remove default site
rm -f /etc/nginx/sites-enabled/default

echo -e "${GREEN}✓ Nginx installed${NC}"

echo -e "${BLUE}Step 5: Installing Certbot for SSL...${NC}"
apt install -y certbot python3-certbot-nginx
echo -e "${GREEN}✓ Certbot installed${NC}"

echo -e "${BLUE}Step 6: Creating project directory...${NC}"
if [ -d "$PROJECT_PATH" ]; then
    echo -e "${YELLOW}Project directory exists. Remove? (y/n)${NC}"
    read -r overwrite
    if [[ $overwrite == "y" ]]; then
        rm -rf "$PROJECT_PATH"
    else
        echo "Setup cancelled"
        exit 0
    fi
fi

mkdir -p "$PROJECT_PATH"
echo -e "${GREEN}✓ Project directory created${NC}"

echo -e "${BLUE}Step 7: Copying files...${NC}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cp -r "$SCRIPT_DIR/../"* "$PROJECT_PATH/"
rm -f "$PROJECT_PATH/setup/install-"*
echo -e "${GREEN}✓ Files copied${NC}"

echo -e "${BLUE}Step 8: Setting up environment file...${NC}"
if [ ! -f "$PROJECT_PATH/.env" ]; then
    if [ -f "$PROJECT_PATH/.env.example" ]; then
        cp "$PROJECT_PATH/.env.example" "$PROJECT_PATH/.env"
    else
        cat > "$PROJECT_PATH/.env" << EOF
# Database Configuration
DB_HOST=localhost
DB_NAME=hyperabyss_cluster
DB_USER=hyperabyss_user
DB_PASS=$(openssl rand -base64 32)
DB_PORT=3306

# RCON Passwords
RAGNAROK_RCON_PASSWORD=your_password_here
THEISLAND_RCON_PASSWORD=your_password_here
THECENTER_RCON_PASSWORD=your_password_here
FORGLAR_RCON_PASSWORD=your_password_here
SVARTALFHEIM_RCON_PASSWORD=your_password_here

# Security
CORS_ENABLED=true
API_KEY_REQUIRED=false
HTTPS_ONLY=false
EOF
    fi
fi
echo -e "${GREEN}✓ Environment file ready${NC}"

echo -e "${BLUE}Step 9: Creating required directories...${NC}"
mkdir -p "$PROJECT_PATH/logs"
mkdir -p "$PROJECT_PATH/assets/images"
touch "$PROJECT_PATH/logs/api.log"
touch "$PROJECT_PATH/logs/errors.log"
echo -e "${GREEN}✓ Directories created${NC}"

echo -e "${BLUE}Step 10: Setting file permissions...${NC}"
chown -R www-data:www-data "$PROJECT_PATH"
chmod -R 755 "$PROJECT_PATH"
chmod -R 777 "$PROJECT_PATH/logs"
chmod 600 "$PROJECT_PATH/.env"
echo -e "${GREEN}✓ Permissions set${NC}"

echo -e "${BLUE}Step 11: Setting up database...${NC}"
read -p "Enter domain name (e.g., hyperabyss.com): " DOMAIN_NAME
DB_USER="hyperabyss_user"
DB_PASS=$(grep DB_PASS "$PROJECT_PATH/.env" | cut -d'=' -f2)

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS hyperabyss_cluster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON hyperabyss_cluster.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Import schema
mysql hyperabyss_cluster < "$PROJECT_PATH/db/schema.sql"
echo -e "${GREEN}✓ Database setup complete${NC}"

echo -e "${BLUE}Step 12: Configuring PHP...${NC}"
# Configure PHP-FPM
cat > "/etc/php/${PHP_VERSION}/fpm/pool.d/hyperabyss.conf" << EOF
[hyperabyss]
user = www-data
group = www-data
listen = /run/php/php${PHP_VERSION}-fpm-hyperabyss.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
EOF

# Configure PHP settings
cat > "/etc/php/${PHP_VERSION}/fpm/conf.d/99-hyperabyss.ini" << EOF
; HyperAbyss optimizations
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 32M
post_max_size = 32M
date.timezone = America/New_York

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Performance
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
EOF

systemctl restart php${PHP_VERSION}-fpm
echo -e "${GREEN}✓ PHP configured${NC}"

echo -e "${BLUE}Step 13: Configuring Nginx...${NC}"
cat > "/etc/nginx/sites-available/$PROJECT_NAME" << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN_NAME www.$DOMAIN_NAME;
    root $PROJECT_PATH;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Hide server version
    server_tokens off;

    # API routing
    location ~* ^/api/(.*)$ {
        try_files \$uri /api/enhanced-api.php?endpoint=\$1&\$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm-hyperabyss.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static files caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~* \.(env|sql|log|dat)$ {
        deny all;
    }

    # Pretty URLs
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
EOF

ln -sf "/etc/nginx/sites-available/$PROJECT_NAME" "/etc/nginx/sites-enabled/"
nginx -t && systemctl reload nginx
echo -e "${GREEN}✓ Nginx configured${NC}"

echo -e "${BLUE}Step 14: Installing cron jobs...${NC}"
cat > "/etc/cron.d/hyperabyss" << EOF
# HyperAbyss maintenance tasks
*/1 * * * * www-data /usr/bin/php $PROJECT_PATH/scripts/monitoring.php >/dev/null 2>&1
*/5 * * * * www-data /usr/bin/php $PROJECT_PATH/scripts/analytics-tracker.php >/dev/null 2>&1
0 */6 * * * www-data /usr/bin/php $PROJECT_PATH/scripts/discord-sync.php >/dev/null 2>&1
0 2 * * * www-data /usr/bin/php $PROJECT_PATH/scripts/cleanup.php >/dev/null 2>&1
EOF

echo -e "${GREEN}✓ Cron jobs installed${NC}"

echo -e "${BLUE}Step 15: Setting up SSL certificate...${NC}"
echo -e "${YELLOW}Choose SSL option:${NC}"
echo "1) Let's Encrypt (recommended)"
echo "2) Cloudflare Origin Certificate"
echo "3) Skip SSL setup"
read -p "Enter choice (1-3): " ssl_choice

case $ssl_choice in
    1)
        echo -e "${BLUE}Setting up Let's Encrypt...${NC}"
        certbot --nginx -d "$DOMAIN_NAME" -d "www.$DOMAIN_NAME" --non-interactive --agree-tos --email "admin@$DOMAIN_NAME"
        ;;
    2)
        echo -e "${BLUE}Setting up Cloudflare SSL...${NC}"
        mkdir -p "/etc/ssl/cloudflare"
        echo -e "${YELLOW}Please upload your Cloudflare origin certificate to:${NC}"
        echo "/etc/ssl/cloudflare/cert.pem"
        echo "/etc/ssl/cloudflare/key.pem"
        echo -e "${YELLOW}Then run: nginx -t && systemctl reload nginx${NC}"
        ;;
    3)
        echo -e "${YELLOW}SSL setup skipped${NC}"
        ;;
esac

echo -e "${BLUE}Step 16: Starting services...${NC}"
systemctl enable nginx php${PHP_VERSION}-fpm mysql
systemctl start nginx php${PHP_VERSION}-fpm mysql
echo -e "${GREEN}✓ Services started${NC}"

echo -e "${BLUE}Step 17: Setting up firewall...${NC}"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
echo -e "${GREEN}✓ Firewall configured${NC}"

echo
echo -e "${GREEN}========================================"
echo -e " Setup Complete!"
echo -e "========================================${NC}"
echo
echo -e "${GREEN}Your HyperAbyss website is ready at:${NC}"
echo -e "HTTP:  http://$DOMAIN_NAME"
echo -e "HTTPS: https://$DOMAIN_NAME"
echo
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Edit $PROJECT_PATH/.env with your RCON passwords"
echo "2. Update $PROJECT_PATH/servers.json with your servers"
echo "3. Visit your domain to test"
echo "4. Check logs: tail -f $PROJECT_PATH/logs/api.log"
echo
echo -e "${YELLOW}Important files:${NC}"
echo "Config: $PROJECT_PATH/.env"
echo "Servers: $PROJECT_PATH/servers.json"
echo "Logs: $PROJECT_PATH/logs/"
echo "Nginx: /etc/nginx/sites-available/$PROJECT_NAME"
echo
echo -e "${GREEN}Setup completed successfully!${NC}"