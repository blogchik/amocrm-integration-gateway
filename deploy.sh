#!/bin/bash

# AmoCRM Integration Gateway - Production Deployment Script
# Usage: ./deploy.sh

set -e  # Exit on error

echo "🚀 Starting deployment..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Project directory
PROJECT_DIR="/home/abu/amocrm-integration-gateway"

# Check if project directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}❌ Project directory not found: $PROJECT_DIR${NC}"
    exit 1
fi

cd $PROJECT_DIR

echo -e "${YELLOW}📂 Current directory: $(pwd)${NC}"

# 1. Git pull (if using git)
if [ -d ".git" ]; then
    echo -e "${YELLOW}📥 Pulling latest changes...${NC}"
    git pull origin main
else
    echo -e "${YELLOW}⚠️  Not a git repository, skipping pull${NC}"
fi

# 2. Install composer dependencies
echo -e "${YELLOW}📦 Installing composer dependencies...${NC}"
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
elif [ -f "/usr/local/bin/composer" ]; then
    /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction
else
    echo -e "${RED}❌ Composer not found!${NC}"
    exit 1
fi

# 3. Check .env file
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    exit 1
fi

echo -e "${GREEN}✅ .env file exists${NC}"

# 4. Create storage directory if not exists
if [ ! -d "storage" ]; then
    mkdir -p storage
fi

# 5. Set permissions
echo -e "${YELLOW}🔒 Setting permissions...${NC}"
chmod -R 775 storage/
if command -v chown &> /dev/null; then
    chown -R www-data:www-data storage/ 2>/dev/null || chown -R apache:apache storage/ 2>/dev/null || echo "Could not change owner, continuing..."
fi

# 6. Check tokens.json
if [ ! -f "storage/tokens.json" ]; then
    echo -e "${YELLOW}⚠️  tokens.json not found. You need to authorize via OAuth.${NC}"
    echo -e "${YELLOW}   Visit: https://amoapi.nuqtauz.com/oauth/authorize${NC}"
fi

# 7. Check PHP version
echo -e "${YELLOW}🐘 Checking PHP version...${NC}"
PHP_VERSION=$(php -v | head -n 1)
echo -e "${GREEN}   $PHP_VERSION${NC}"

# 8. Check required PHP extensions
echo -e "${YELLOW}🔌 Checking PHP extensions...${NC}"
REQUIRED_EXTENSIONS=("intl" "pdo" "json" "mbstring" "curl")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "$ext"; then
        echo -e "${GREEN}   ✅ $ext${NC}"
    else
        echo -e "${RED}   ❌ $ext NOT INSTALLED${NC}"
    fi
done

# 9. Test autoload
echo -e "${YELLOW}🧪 Testing autoload...${NC}"
if [ -f "vendor/autoload.php" ]; then
    echo -e "${GREEN}   ✅ Autoload file exists${NC}"
else
    echo -e "${RED}   ❌ Autoload file not found!${NC}"
    exit 1
fi

# 10. Restart PHP-FPM (optional)
echo -e "${YELLOW}🔄 Attempting to restart PHP-FPM...${NC}"
if command -v systemctl &> /dev/null; then
    sudo systemctl restart php-fpm 2>/dev/null || sudo systemctl restart php8.2-fpm 2>/dev/null || echo "Could not restart PHP-FPM (might need sudo)"
fi

# 11. Health check
echo -e "${YELLOW}🏥 Running health check...${NC}"
sleep 2
HEALTH_CHECK=$(curl -s -X GET http://localhost/health -H "X-API-Key: Nuqta2024" || echo '{"success":false}')
if echo "$HEALTH_CHECK" | grep -q '"success":true'; then
    echo -e "${GREEN}   ✅ Health check passed!${NC}"
else
    echo -e "${RED}   ❌ Health check failed!${NC}"
    echo -e "${RED}   Response: $HEALTH_CHECK${NC}"
fi

echo ""
echo -e "${GREEN}✨ Deployment completed!${NC}"
echo ""
echo -e "${YELLOW}📋 Next steps:${NC}"
echo -e "   1. Check logs: tail -f storage/error.log"
echo -e "   2. Test OAuth: curl https://amoapi.nuqtauz.com/oauth/status"
echo -e "   3. Test lead creation: curl -X POST https://amoapi.nuqtauz.com/api/v1/leads/unsorted"
echo ""
