#!/bin/bash

# PHP 8.1 to 8.2 Upgrade Script for Ubuntu
# Run with: sudo bash upgrade-php.sh

set -e

echo "🔄 Starting PHP upgrade from 8.1 to 8.2..."

# Update package lists
echo "📦 Updating package lists..."
apt update

# Add Ondřej Surý PPA (if not already added)
echo "➕ Adding PHP PPA repository..."
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.2 and required extensions
echo "📥 Installing PHP 8.2 and extensions..."
apt install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-curl \
    php8.2-mbstring \
    php8.2-intl \
    php8.2-xml \
    php8.2-zip \
    php8.2-json \
    php8.2-gd

# Stop PHP 8.1 FPM
echo "⏸️  Stopping PHP 8.1 FPM..."
systemctl stop php8.1-fpm

# Disable PHP 8.1 FPM
echo "🔴 Disabling PHP 8.1 FPM..."
systemctl disable php8.1-fpm

# Enable PHP 8.2 FPM
echo "🟢 Enabling PHP 8.2 FPM..."
systemctl enable php8.2-fpm
systemctl start php8.2-fpm

# Switch CLI version
echo "🔄 Switching CLI version..."
update-alternatives --set php /usr/bin/php8.2

# Update Nginx configuration (if using Nginx)
if [ -f "/etc/nginx/sites-available/default" ]; then
    echo "🔧 Updating Nginx configuration..."
    sed -i 's/php8.1-fpm/php8.2-fpm/g' /etc/nginx/sites-available/default
    sed -i 's/php8.1-fpm/php8.2-fpm/g' /etc/nginx/sites-enabled/* 2>/dev/null || true
    
    # Test Nginx configuration
    nginx -t
    
    # Reload Nginx
    systemctl reload nginx
fi

# Update Apache configuration (if using Apache)
if command -v apache2 &> /dev/null; then
    echo "🔧 Updating Apache configuration..."
    a2dismod php8.1
    a2enmod php8.2
    systemctl restart apache2
fi

# Verify PHP version
echo ""
echo "✅ PHP upgrade completed!"
echo ""
echo "📊 Current PHP versions:"
php -v
php-fpm8.2 -v

echo ""
echo "🧪 Checking PHP modules:"
php -m | grep -E "(curl|mbstring|intl|xml|json)"

echo ""
echo "🎯 PHP-FPM 8.2 status:"
systemctl status php8.2-fpm --no-pager

echo ""
echo "✨ Upgrade successful!"
echo ""
echo "⚠️  Optional: Remove PHP 8.1 (run manually if needed):"
echo "   sudo apt remove php8.1 php8.1-* -y"
echo "   sudo apt autoremove -y"
