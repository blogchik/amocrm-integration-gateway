# Server Setup Instructions

## Problem
```
Interface "AmoCRM\OAuth\OAuthServiceInterface" not found
```

Bu xato composer dependencies o'rnatilmaganligini bildiradi.

## Solution

Serverda quyidagi buyruqlarni bajaring:

### 1. Project papkasiga o'ting
```bash
cd /home/abuG24172911-kc/amocrm-integration-gateway
```

### 2. Composer dependencies o'rnating
```bash
composer install --no-dev --optimize-autoloader
```

Yoki agar composer global o'rnatilgan bo'lsa:
```bash
/usr/local/bin/composer install --no-dev --optimize-autoloader
```

### 3. Permissions tekshiring
```bash
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

### 4. .env faylini tekshiring
```bash
cat .env
```

Quyidagi parametrlar to'g'ri ekanligiga ishonch hosil qiling:
```env
AMO_DOMAIN=nuqta.amocrm.ru
AMO_CLIENT_ID=6e670980-83d0-4ebc-9f7d-67e26a358f00
AMO_CLIENT_SECRET=...
AMO_REDIRECT_URI=https://amoapi.nuqtauz.com/oauth/callback
API_KEY=Nuqta2024
```

### 5. Nginx/Apache restart
```bash
sudo systemctl restart nginx
# yoki
sudo systemctl restart apache2
# yoki
sudo systemctl restart php-fpm
```

### 6. Test qiling
```bash
curl -X GET http://localhost/health -H "X-API-Key: Nuqta2024"
```

Yoki tashqaridan:
```bash
curl -X GET https://amoapi.nuqtauz.com/health -H "X-API-Key: Nuqta2024"
```

## Kerakli packages

Agar composer install xato bersa, quyidagilarni tekshiring:

### PHP versiyasi (8.2+ kerak)
```bash
php -v
```

### PHP extensions
```bash
php -m | grep -E "intl|pdo|json|mbstring|curl"
```

Agar kerakli extension yo'q bo'lsa:
```bash
# Ubuntu/Debian
sudo apt install php8.2-intl php8.2-pdo php8.2-mbstring php8.2-curl

# CentOS/RHEL
sudo yum install php82-intl php82-pdo php82-mbstring php82-curl
```

## OAuth Setup

Agar token yo'q bo'lsa, browserda:
```
https://amoapi.nuqtauz.com/oauth/authorize
```

Bo'lmasa, qo'lda token qo'ying `storage/tokens.json`:
```json
{
    "access_token": "...",
    "refresh_token": "...",
    "expires_at": 1763720958,
    "base_domain": "nuqta.amocrm.ru"
}
```

## Troubleshooting

### Vendor papka yo'q
```bash
ls -la vendor/
```

Agar yo'q bo'lsa:
```bash
composer install --no-dev --optimize-autoloader
```

### Autoload xatosi
```bash
composer dump-autoload --optimize
```

### Token xatosi
```bash
cat storage/tokens.json
```

Token mavjud va `base_domain: nuqta.amocrm.ru` ekanligiga ishonch hosil qiling.

### Logs tekshirish
```bash
tail -f storage/error.log
```

## Production Deployment Checklist

- [ ] Composer dependencies o'rnatildi
- [ ] .env fayl to'g'ri sozlandi  
- [ ] storage/ permissions to'g'ri
- [ ] OAuth token olindi
- [ ] nginx/apache configured
- [ ] PHP-FPM running
- [ ] Health check ishlayapti
- [ ] Lead creation test o'tdi
