# Tezkor O'rnatish Qo'llanmasi

## 1. Composer Install

```bash
cd /path/to/amocrm-integration-gateway
composer install
```

Agar Composer o'rnatilmagan bo'lsa:
```bash
# Linux/Mac
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Yoki Docker ishlatilsa
docker-compose exec app composer install
```

## 2. Konfiguratsiya

```bash
cp .env.example .env
nano .env
```

Quyidagi qiymatlarni o'zgartiring:

```env
# Gateway API key (istalgan murakkab string)
API_KEY=super-secret-key-12345

# AmoCRM OAuth credentials
AMO_DOMAIN=yourcompany.amocrm.ru
AMO_CLIENT_ID=12345678-abcd-1234-abcd-123456789abc
AMO_CLIENT_SECRET=your-client-secret-here
AMO_REDIRECT_URI=https://your-domain.com/oauth/callback
```

### AmoCRM Integration yaratish

1. https://www.amocrm.ru → Sozlamalar → Integratsiyalar
2. "Integratsiyani yaratish" tugmasini bosing
3. Ma'lumotlarni to'ldiring:
   - **Redirect URI:** `https://your-domain.com/oauth/callback`
   - **Ruxsatlar:** Leads, Contacts, Custom fields (read/write)
4. **Client ID** va **Client Secret** ni nusxalang

## 3. Permissions

```bash
chmod 600 .env
chmod 600 storage/tokens.json
chmod 755 storage
```

## 4. OAuth Avtorizatsiya

### A. Brauzerda

```
https://your-domain.com/oauth/authorize
```

AmoCRM login qiling va "Ruxsat berish" tugmasini bosing.

### B. Statusni tekshirish

```bash
curl https://your-domain.com/oauth/status
```

Response:
```json
{
  "success": true,
  "data": {
    "has_token": true,
    "status": "authorized"
  }
}
```

## 5. Test

### Lead yaratish

```bash
curl -X POST https://your-domain.com/api/v1/leads/unsorted \
  -H "X-API-KEY: super-secret-key-12345" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "website",
    "form_name": "Test Form",
    "lead": {
      "name": "Test Lead",
      "price": 5000
    },
    "contact": {
      "name": "John Doe",
      "phone": "+998901234567"
    }
  }'
```

Muvaffaqiyatli response:
```json
{
  "success": true,
  "data": {
    "id": "abc123",
    "uid": "gw_..."
  },
  "message": "Unsorted lead created successfully"
}
```

### Pipelines olish

```bash
curl https://your-domain.com/api/v1/info/pipelines \
  -H "X-API-KEY: super-secret-key-12345"
```

## 6. Production Deploy

### Nginx konfiguratsiya

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/amocrm-gateway/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # SSL (Let's Encrypt)
    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
}
```

### Docker Production

```bash
# Build
docker-compose -f docker-compose.yml up -d --build

# Composer install
docker-compose exec app composer install --no-dev --optimize-autoloader

# Check logs
docker-compose logs -f app
```

## 7. Monitoring

### Logs

```bash
# Application logs
tail -f storage/error.log

# Docker logs
docker-compose logs -f app

# Nginx logs
tail -f /var/log/nginx/error.log
```

### Health check

```bash
# Sistemani tekshirish
curl https://your-domain.com/health

# Token statusini tekshirish
curl https://your-domain.com/oauth/status \
  -H "X-API-KEY: your-api-key"
```

## Tez-tez uchraydigan muammolar

### 1. "No OAuth token found"

**Yechim:**
```bash
curl https://your-domain.com/oauth/authorize
```

### 2. "Composer not found"

**Yechim:**
```bash
# Install composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer install
```

### 3. "Permission denied" (tokens.json)

**Yechim:**
```bash
chmod 600 storage/tokens.json
chown www-data:www-data storage/tokens.json
```

### 4. Docker "vendor" not found

**Yechim:**
```bash
docker-compose exec app composer install
```

### 5. "Invalid API key"

`.env` faylda `API_KEY` to'g'ri sozlanganini tekshiring.

## Qo'shimcha resurslar

- **Migration Guide:** [docs/MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)
- **Troubleshooting:** [docs/TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- **Release Notes:** [docs/RELEASE_NOTES_v2.0.0.md](RELEASE_NOTES_v2.0.0.md)
- **AmoCRM Docs:** https://www.amocrm.ru/developers/

## Yordam kerakmi?

1. Loglarni tekshiring: `tail -f storage/error.log`
2. OAuth statusini tekshiring: `curl your-domain.com/oauth/status`
3. AmoCRM integratsiya sozlamalarini tekshiring

---

**Tayyor!** 🎉 Gateway ishga tushdi va tokenlar avtomatik yangilanadi.
