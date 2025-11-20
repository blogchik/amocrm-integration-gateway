# Production Deployment Quick Guide

## 🚀 Automated Deployment

### Method 1: Using Deployment Script (Recommended)

1. **Make script executable**:
```bash
chmod +x deploy.sh
```

2. **Run deployment**:
```bash
./deploy.sh
```

The script will automatically:
- Pull latest changes (if git configured)
- Install composer dependencies
- Set correct permissions
- Check PHP version and extensions
- Test autoload
- Restart PHP-FPM
- Run health check

---

## 🔧 Manual Deployment

If automated script fails, follow these steps:

### Step 1: Install Dependencies
```bash
cd /home/abuG24172911-kc/amocrm-integration-gateway
composer install --no-dev --optimize-autoloader
```

### Step 2: Set Permissions
```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/
# OR
chown -R apache:apache storage/
```

### Step 3: Verify .env Configuration
```bash
cat .env | grep AMO_DOMAIN
```

Should show: `AMO_DOMAIN=nuqta.amocrm.ru`

### Step 4: OAuth Authorization
If `storage/tokens.json` doesn't exist or needs refresh:
```bash
# Visit in browser:
https://amoapi.nuqtauz.com/oauth/authorize
```

### Step 5: Restart Services
```bash
sudo systemctl restart php-fpm
# OR
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### Step 6: Health Check
```bash
curl -X GET https://amoapi.nuqtauz.com/health \
  -H "X-API-Key: Nuqta2024"
```

Expected response:
```json
{"success":true,"environment":"production","php_version":"8.2.x"}
```

---

## 🧪 Testing

### Test OAuth Status
```bash
curl -X GET https://amoapi.nuqtauz.com/oauth/status \
  -H "X-API-Key: Nuqta2024"
```

### Test Lead Creation
```bash
curl -X POST https://amoapi.nuqtauz.com/api/v1/leads/unsorted \
  -H "X-API-Key: Nuqta2024" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Lead from Production",
    "price": 50000,
    "phone": "+998901234567"
  }'
```

---

## 📋 Troubleshooting

### Issue: "Interface not found"
**Solution**: Run composer install
```bash
composer install --no-dev --optimize-autoloader
```

### Issue: "Permission denied" for storage/
**Solution**: Fix permissions
```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

### Issue: "Token expired"
**Solution**: Re-authorize via OAuth
```bash
# Visit: https://amoapi.nuqtauz.com/oauth/authorize
```

### Issue: 405 Method Not Allowed
**Solution**: Check AMO_DOMAIN in .env
```bash
echo "AMO_DOMAIN=nuqta.amocrm.ru" >> .env
```

---

## 📊 Monitoring

### View Error Logs
```bash
tail -f storage/error.log
```

### View PHP Errors
```bash
tail -f /var/log/php-fpm/error.log
# OR
tail -f /var/log/php8.2-fpm.log
```

### View Nginx Logs
```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

---

## 🔄 Update Workflow

When pushing new code:

1. **Push to repository**:
```bash
git push origin main
```

2. **Deploy on server**:
```bash
./deploy.sh
```

---

## 📞 Support

If deployment fails:
1. Check logs: `tail -f storage/error.log`
2. Verify PHP extensions: `php -m`
3. Check composer: `composer diagnose`
4. Review SERVER_SETUP.md for detailed instructions

---

## ✅ Deployment Checklist

- [ ] Composer dependencies installed
- [ ] Storage permissions set (775)
- [ ] .env file configured with AMO_DOMAIN
- [ ] OAuth token obtained (tokens.json exists)
- [ ] PHP-FPM restarted
- [ ] Health check passes
- [ ] OAuth status shows token valid
- [ ] Test lead creation successful

---

**Last Updated**: v2.0.0
**Production URL**: https://amoapi.nuqtauz.com
**API Key**: Nuqta2024
