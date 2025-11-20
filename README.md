# AmoCRM Integration Gateway v2.0

**AmoCRM bilan integratsiya uchun REST API Gateway** (AmoCRM rasmiy kutubxonasi bilan)

![License](https://img.shields.io/badge/license-Apache%202.0-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4.svg)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED.svg)

## 🎉 Yangiliklar v2.0

- ✅ **AmoCRM rasmiy PHP kutubxonasi** integratsiyalandi
- ✅ **Avtomatik token refresh** - hech qachon expire bo'lmaydi
- ✅ **24/7 ishlaydi** - uzoq vaqt ishlatilmasa ham muammo yo'q
- ✅ **Professional error handling** - barcha xatolar to'g'ri boshqariladi
- ✅ **OAuth2 callback** - oson avtorizatsiya
- ✅ **Backward compatible** - barcha API endpointlar oldingiday

## 📋 Mundarija

- [Tezkor boshlash](#tezkor-boshlash)
- [Asosiy farqlar (v1 vs v2)](#asosiy-farqlar-v1-vs-v2)
- [API Endpointlar](#api-endpointlar)
- [Docker bilan ishlatish](#docker-bilan-ishlatish)
- [Production Deploy](#production-deploy)
- [Migration (v1.x → v2.0)](#migration-v1x--v20)
- [Texnologiyalar](#texnologiyalar)
- [Yordam](#yordam)
- [Hissa qo'shish](#hissa-qoshish)
- [Litsenziya](#litsenziya)

---

## Tezkor boshlash

### 1. O'rnatish

```bash
git clone https://github.com/blogchik/amocrm-integration-gateway.git
cd amocrm-integration-gateway
composer install
cp .env.example .env
```

### 2. Konfiguratsiya

`.env` faylni tahrirlang:

```env
API_KEY=your-secret-gateway-key

AMO_DOMAIN=yoursubdomain.amocrm.ru
AMO_CLIENT_ID=your-integration-id
AMO_CLIENT_SECRET=your-integration-secret
AMO_REDIRECT_URI=https://your-domain.com/oauth/callback

TOKEN_STORAGE_PATH=./storage/tokens.json
```

### 3. AmoCRM avtorizatsiyasi

Brauzerda oching:
```
https://your-domain.com/oauth/authorize
```

AmoCRM'ga login qiling va ruxsat bering. Token avtomatik saqlanadi.

### 4. Ishlatish

Lead yaratish:

```bash
curl -X POST https://your-domain.com/api/v1/leads/unsorted \
  -H "X-API-KEY: your-secret-gateway-key" \
  -H "Content-Type: application/json" \
  -d '{
    "source": "website",
    "form_name": "Contact Form",
    "lead": {
      "name": "Yangi mijoz",
      "price": 10000
    },
    "contact": {
      "name": "Alisher Valiev",
      "phone": "+998901234567",
      "email": "alisher@example.com"
    }
  }'
```

## Asosiy farqlar (v1 vs v2)

| Xususiyat | v1.x | v2.0 |
|-----------|------|------|
| Token refresh | Manual | Avtomatik ✅ |
| 24 soat ishlatilmasa | Xato ❌ | Ishlaydi ✅ |
| AmoCRM kutubxonasi | Custom CURL | Rasmiy ✅ |
| Error handling | Basic | Professional ✅ |
| Token storage | JSON file | JSON file + OAuthService |
| API endpoints | Same | Same (100% compatible) |

## API Endpointlar

### OAuth Endpoints

| Method | Endpoint | Tavsif |
|--------|----------|--------|
| GET | `/oauth/authorize` | AmoCRM'ga yo'naltirish |
| GET | `/oauth/callback` | OAuth callback (avtomatik) |
| GET | `/oauth/status` | Token status tekshirish |

### Lead Endpoints

| Method | Endpoint | Tavsif |
|--------|----------|--------|
| POST | `/api/v1/leads/unsorted` | Unsorted lead yaratish |

### Info Endpoints

| Method | Endpoint | Tavsif |
|--------|----------|--------|
| GET | `/api/v1/info/pipelines` | Barcha voronkalar |
| GET | `/api/v1/info/pipelines/{id}` | Bitta voronka |
| GET | `/api/v1/info/lead-fields` | Lead custom fields |
| GET | `/api/v1/info/contact-fields` | Contact custom fields |
| GET | `/api/v1/info/account` | Account ma'lumotlari |

### System Endpoints

| Method | Endpoint | Tavsif |
|--------|----------|--------|
| GET | `/health` | Health check |

---

## Docker bilan ishlatish

```bash
# Build va ishga tushirish
docker-compose up -d

# Composer install
docker-compose exec app composer install

# Logs ko'rish
docker-compose logs -f

# To'xtatish
docker-compose down
```

---

## Production Deploy

### Nginx + PHP-FPM

```bash
# Dependencies
composer install --no-dev --optimize-autoloader

# Permissions
chmod 600 storage/tokens.json
chmod 600 .env

# OAuth avtorizatsiya
curl https://your-domain.com/oauth/authorize
```

### Docker Production

```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  app:
    build: 
      context: .
      dockerfile: docker/Dockerfile
    environment:
      - APP_ENV=production
    volumes:
      - ./storage:/var/www/html/storage
    restart: unless-stopped
```

```bash
docker-compose -f docker-compose.prod.yml up -d
```

---

## Migration (v1.x → v2.0)

Eski versiyadan o'tish uchun: [MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md)

Qisqacha:

1. `composer install`
2. Eski `storage/tokens.json` ni o'chirish
3. Qayta avtorizatsiya: `/oauth/authorize`
4. Tayyor! ✅

---

## Texnologiyalar

- PHP 8.2+
- [AmoCRM API PHP Library](https://github.com/amocrm/amocrm-api-php) v1.14+
- Composer
- Docker & Docker Compose
- Nginx
- OAuth2

---

## Misol

Lead yaratish (to'liq):

```json
{
  "source": "website",
  "form_name": "Main Contact Form",
  "form_page": "https://example.com/contact",
  "lead": {
    "name": "Yangi lead - Alisher",
    "price": 25000,
    "custom_fields": [
      {
        "field_id": 123456,
        "value": "Qo'shimcha ma'lumot"
      }
    ],
    "tags": ["website", "urgent"]
  },
  "contact": {
    "name": "Alisher Valiev",
    "phone": "+998901234567",
    "email": "alisher@example.com"
  },
  "utm": {
    "utm_source": "google",
    "utm_medium": "cpc",
    "utm_campaign": "spring2024"
  },
  "comment": "Tez aloqa qiling!",
  "ip": "192.168.1.1"
}
```

---

## Yordam

### Token muammolari

```bash
# Token statusini tekshirish
curl https://your-domain.com/oauth/status \
  -H "X-API-KEY: your-key"

# Qayta avtorizatsiya
curl https://your-domain.com/oauth/authorize
```

### Error loglar

```bash
# Docker
docker-compose logs -f app

# Direct
tail -f storage/error.log
```

---

## Hissa qo'shish

Pull request'lar xush kelibsiz!

---

## Litsenziya

Apache 2.0 License

---

## Muallif

**Jabborov Abduroziq**

---

**v2.0.0** - Token expire muammosi butunlay hal qilindi! 🎉

## Tizim talablari

### Minimal talablar:

- **PHP**: >= 8.2
- **Extensions**: 
  - `curl` - HTTP so'rovlar uchun
  - `json` - JSON ishlash uchun
  - `mbstring` - Unicode support
  - `openssl` - HTTPS uchun
- **Server**: Nginx/Apache
- **OS**: Linux (tavsiya), macOS, Windows

### Docker orqali (tavsiya):

- **Docker**: >= 20.10
- **Docker Compose**: >= 1.29
- **RAM**: >= 512MB
- **Disk**: >= 1GB

---

## O'rnatish

### Docker orqali o'rnatish (Tavsiya etiladi)

Docker yordamida o'rnatish eng oson va ishonchli usul.

#### 1. Loyihani clone qilish

```bash
git clone https://github.com/blogchik/amocrm-integration-gateway.git
cd amocrm-integration-gateway
```

#### 2. Environment faylni yaratish

```bash
cp .env.example .env
```

#### 3. .env faylni tahrirlash

`.env` faylini ochib, quyidagi qiymatlarni o'zgartiring:

```env
# Gateway API Key (o'zingizning xavfsiz kalitingiz)
API_KEY=your_super_secret_api_key_here

# AmoCRM OAuth2 Settings
AMO_DOMAIN=yoursubdomain.amocrm.ru
AMO_CLIENT_ID=your-client-id-from-amocrm
AMO_CLIENT_SECRET=your-client-secret-from-amocrm
AMO_REDIRECT_URI=http://localhost:9095/oauth/callback

# Token storage path
TOKEN_STORAGE_PATH=./storage/tokens.json

# Logging
LOG_ERRORS=true
```

#### 4. Storage papkasini yaratish

```bash
mkdir -p storage
chmod -R 775 storage
```

#### 5. Docker konteynerlarni ishga tushirish

```bash
docker-compose up -d
```

#### 6. Tekshirish

Gateway ishga tushganini tekshirish:

```bash
curl http://localhost:9095/health
```

Javob:
```json
{
  "success": true,
  "message": "Gateway is running",
  "data": {
    "status": "ok"
  }
}
```

---

### To'g'ridan-to'g'ri server o'rnatish

Agar Docker'siz o'rnatmoqchi bo'lsangiz:

#### 1. Loyihani clone qilish

```bash
git clone https://github.com/blogchik/amocrm-integration-gateway.git
cd amocrm-integration-gateway
```

#### 2. PHP o'rnatish (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-curl php8.2-mbstring php8.2-json
```

#### 3. Nginx o'rnatish

```bash
sudo apt install -y nginx
```

#### 4. Nginx konfiguratsiya

`/etc/nginx/sites-available/amocrm-gateway` fayl yaratish:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    root /path/to/amocrm-integration-gateway/public;
    index index.php;

    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

Nginx'ni qayta ishga tushirish:

```bash
sudo ln -s /etc/nginx/sites-available/amocrm-gateway /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 5. Storage papkasi

```bash
mkdir -p storage
chmod -R 775 storage
chown -R www-data:www-data storage
```

#### 6. .env fayl

```bash
cp .env.example .env
nano .env  # Konfiguratsiyani to'ldiring
```

---

## Konfiguratsiya

### .env fayl sozlash

`.env` fayl barcha muhim konfiguratsiyalarni saqlaydi:

| Variable | Ta'rif | Misol |
|----------|--------|-------|
| `API_KEY` | Gateway API kaliti (xavfsiz string) | `SUPER_SECRET_KEY_2024` |
| `AMO_DOMAIN` | AmoCRM subdomain | `mycompany.amocrm.ru` |
| `AMO_CLIENT_ID` | OAuth2 Client ID | `abc123-def456-...` |
| `AMO_CLIENT_SECRET` | OAuth2 Client Secret | `xyz789qwe123...` |
| `AMO_REDIRECT_URI` | OAuth2 Redirect URI | `http://localhost:9095/oauth/callback` |
| `TOKEN_STORAGE_PATH` | Token saqlash yo'li | `./storage/tokens.json` |
| `LOG_ERRORS` | Xatolarni log qilish | `true` |

### AmoCRM OAuth2 sozlash

Gateway AmoCRM bilan ishlash uchun OAuth2 autentifikatsiya talab qiladi.

#### 1. AmoCRM integrationni ro'yxatdan o'tkazish

1. AmoCRM kabinetiga kiring: `https://www.amocrm.ru/`
2. **Sozlamalar** → **Integratsiyalar** ga o'ting
3. **Private integratsiya yaratish** tugmasini bosing
4. Quyidagi ma'lumotlarni to'ldiring:
   - **Nomi**: Gateway Integration
   - **Redirect URI**: `http://your-domain.com/oauth/callback` (yoki local: `http://localhost:9095/oauth/callback`)
   - **Ruxsatlar**: 
     - ✅ Leads (read, write)
     - ✅ Contacts (read, write)
     - ✅ Notes (write)
     - ✅ Pipelines (read)

5. Saqlangandan keyin **Client ID** va **Client Secret** ko'chirib oling

#### 2. .env ga qo'shish

```env
AMO_CLIENT_ID=your-copied-client-id
AMO_CLIENT_SECRET=your-copied-client-secret
AMO_REDIRECT_URI=http://localhost:9095/oauth/callback
```

#### 3. Dastlabki autentifikatsiya (Token olish)

AmoCRM tokenini olish uchun qo'lda autentifikatsiya qilish kerak:

1. Brauzerda quyidagi URL'ga kiring (qiymatlarni o'zingizniki bilan almashtiring):

```
https://yoursubdomain.amocrm.ru/oauth?client_id=YOUR_CLIENT_ID&mode=post_message
```

2. Ruxsat bering va **Authorization Code**ni oling
3. Terminalda curl orqali token oling:

```bash
curl -X POST https://yoursubdomain.amocrm.ru/oauth2/access_token \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET",
    "grant_type": "authorization_code",
    "code": "AUTHORIZATION_CODE_HERE",
    "redirect_uri": "http://localhost:9095/oauth/callback"
  }'
```

4. Qaytgan `access_token`, `refresh_token`, va `expires_in` ma'lumotlarini `storage/tokens.json` ga qo'lda yozing:

```json
{
    "access_token": "your_access_token",
    "refresh_token": "your_refresh_token",
    "expires_at": 1234567890
}
```

**Eslatma**: Gateway avtomatik ravishda tokenni yangilaydi, shuning uchun bu jarayonni faqat birinchi marta bajarish kerak.

---

## API Endpointlar

### Autentifikatsiya

Barcha API so'rovlari (health check bundan mustasno) **X-API-KEY** header talab qiladi:

```bash
curl -H "X-API-KEY: your_api_key_here" http://localhost:9095/api/v1/...
```

#### Health Check

**Endpoint**: `GET /health`  
**Auth**: Kerak emas  
**Ta'rif**: Gateway holatini tekshirish

**Misol:**

```bash
curl http://localhost:9095/health
```

**Javob:**

```json
{
  "success": true,
  "message": "Gateway is running",
  "data": {
    "status": "ok"
  }
}
```

---

### Lead yaratish

#### Unsorted Lead yaratish

**Endpoint**: `POST /api/v1/leads/unsorted`  
**Auth**: X-API-KEY  
**Ta'rif**: AmoCRM'ga yangi unsorted lead qo'shish

**Request Body:**

```json
{
  "source": "website",
  "form_name": "Contact Form",
  "form_page": "https://mywebsite.com/contact",
  "referer": "https://google.com",
  "ip": "127.0.0.1",
  "pipeline_id": 123456,
  "lead": {
    "name": "Yangi mijoz",
    "price": 50000,
    "custom_fields": [
      {
        "field_id": 789,
        "value": "Qo'shimcha ma'lumot"
      }
    ],
    "tags": [
      "website",
      "hot-lead"
    ]
  },
  "contact": {
    "name": "Alisher Navoi",
    "phone": "+998901234567",
    "email": "alisher@example.com"
  },
  "utm": {
    "utm_source": "google",
    "utm_medium": "cpc",
    "utm_campaign": "spring_sale"
  },
  "comment": "Mijoz maxsulot haqida so'radi"
}
```

**Javob (Success):**

```json
{
  "success": true,
  "message": "Unsorted lead created successfully",
  "data": {
    "_embedded": {
      "unsorted": [
        {
          "id": 12345,
          "uid": "gw_abc123...",
          "created_at": 1234567890
        }
      ]
    }
  }
}
```

**Javob (Error):**

```json
{
  "success": false,
  "error": "Validation failed",
  "details": {
    "lead.name": "Lead name is required",
    "contact": "Contact object is required"
  }
}
```

**Misol (cURL):**

```bash
curl -X POST http://localhost:9095/api/v1/leads/unsorted \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: your_api_key" \
  -d '{
    "source": "website",
    "form_name": "Konsultatsiya form",
    "lead": {
      "name": "Yangi mijoz so'rovi",
      "price": 100000,
      "tags": ["website", "urgent"]
    },
    "contact": {
      "name": "Jasur Karimov",
      "phone": "+998909876543",
      "email": "jasur@example.uz"
    },
    "comment": "Mijoz tez orada javob kutmoqda"
  }'
```

---

### Ma'lumot olish

#### Barcha Pipelinelarni olish

**Endpoint**: `GET /api/v1/info/pipelines`  
**Auth**: X-API-KEY  
**Ta'rif**: AmoCRM'dagi barcha pipelinelar va ularning statuslarini olish

**Misol:**

```bash
curl -H "X-API-KEY: your_api_key" http://localhost:9095/api/v1/info/pipelines
```

**Javob:**

```json
{
  "success": true,
  "message": "Pipelines retrieved successfully",
  "data": {
    "_embedded": {
      "pipelines": [
        {
          "id": 123456,
          "name": "Asosiy pipeline",
          "is_main": true,
          "_embedded": {
            "statuses": [
              {
                "id": 111,
                "name": "Yangi"
              },
              {
                "id": 222,
                "name": "Aloqada"
              }
            ]
          }
        }
      ]
    }
  }
}
```

---

#### Bitta Pipeline olish

**Endpoint**: `GET /api/v1/info/pipelines/{id}`  
**Auth**: X-API-KEY  
**Ta'rif**: Ma'lum bir pipeline ma'lumotlarini olish

**Misol:**

```bash
curl -H "X-API-KEY: your_api_key" http://localhost:9095/api/v1/info/pipelines/123456
```

---

#### Lead Custom Fields

**Endpoint**: `GET /api/v1/info/lead-fields`  
**Auth**: X-API-KEY  
**Ta'rif**: Lead obyektidagi barcha custom fieldlarni olish (field_id va nomlar)

**Misol:**

```bash
curl -H "X-API-KEY: your_api_key" http://localhost:9095/api/v1/info/lead-fields
```

**Javob:**

```json
{
  "success": true,
  "message": "Lead fields retrieved successfully",
  "data": {
    "_embedded": {
      "custom_fields": [
        {
          "id": 789,
          "name": "Mahsulot turi",
          "type": "select",
          "enums": [
            {
              "id": 1,
              "value": "Premium"
            },
            {
              "id": 2,
              "value": "Standart"
            }
          ]
        }
      ]
    }
  }
}
```

---

#### Contact Custom Fields

**Endpoint**: `GET /api/v1/info/contact-fields`  
**Auth**: X-API-KEY  
**Ta'rif**: Contact obyektidagi custom fieldlarni olish

**Misol:**

```bash
curl -H "X-API-KEY: your_api_key" http://localhost:9095/api/v1/info/contact-fields
```

---

#### Account Ma'lumotlari

**Endpoint**: `GET /api/v1/info/account`  
**Auth**: X-API-KEY  
**Ta'rif**: AmoCRM account umumiy ma'lumotlari

**Misol:**

```bash
curl -H "X-API-KEY: your_api_key" http://localhost:9095/api/v1/info/account
```

**Javob:**

```json
{
  "success": true,
  "message": "Account info retrieved successfully",
  "data": {
    "id": 12345678,
    "name": "My Company",
    "subdomain": "mycompany",
    "currency": "UZS",
    "timezone": "Asia/Tashkent"
  }
}
```

---

## Deployment (Serverga ko'tarish)

### Production muhitiga joylashtirish

Production serverga deploy qilishdan oldin quyidagilarga e'tibor bering:

#### 1. Xavfsizlik

- ✅ Kuchli API_KEY ishlatish (32+ belgili random string)
- ✅ HTTPS ishlatish (SSL sertifikat o'rnatish)
- ✅ .env faylni .gitignore ga qo'shish
- ✅ storage/ papkasini gitdan chiqarish
- ✅ Firewall sozlash (faqat 80, 443 portlar ochiq)
- ✅ Rate limiting qo'shish

#### 2. Performance

- ✅ PHP OPcache yoqish
- ✅ Nginx caching sozlash
- ✅ Gzip compression yoqish
- ✅ Log rotation sozlash

#### 3. Monitoring

- ✅ Error log monitoring (storage/error.log)
- ✅ Uptime monitoring (health check endpoint)
- ✅ Disk space monitoring (token storage)

---

### Docker Compose bilan deploy

Production serverda Docker bilan deploy qilish:

#### 1. Serverni tayyorlash

```bash
# Docker o'rnatish (Ubuntu)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Docker Compose o'rnatish
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

#### 2. Loyihani serverga ko'chirish

```bash
cd /var/www
git clone https://github.com/blogchik/amocrm-integration-gateway.git
cd amocrm-integration-gateway
```

#### 3. Production .env

```bash
cp .env.example .env
nano .env
```

Production .env da:

```env
API_KEY=super_strong_random_api_key_32_chars_minimum
AMO_DOMAIN=yourcompany.amocrm.ru
AMO_CLIENT_ID=production-client-id
AMO_CLIENT_SECRET=production-client-secret
AMO_REDIRECT_URI=https://yourdomain.com/oauth/callback
TOKEN_STORAGE_PATH=./storage/tokens.json
LOG_ERRORS=true
```

#### 4. SSL Sertifikat (Let's Encrypt)

Nginx konfiguratsiyasiga SSL qo'shish:

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    
    # ... qolgan konfiguratsiya
}

server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

#### 5. Ishga tushirish

```bash
docker-compose up -d
docker-compose logs -f  # Loglarni kuzatish
```

#### 6. Auto-restart (systemd)

`/etc/systemd/system/amocrm-gateway.service`:

```ini
[Unit]
Description=AmoCRM Gateway
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/var/www/amocrm-integration-gateway
ExecStart=/usr/local/bin/docker-compose up -d
ExecStop=/usr/local/bin/docker-compose down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
```

Yoqish:

```bash
sudo systemctl enable amocrm-gateway
sudo systemctl start amocrm-gateway
```

---

### Nginx + PHP-FPM bilan deploy

Docker'siz traditional deployment:

#### 1. PHP va Nginx o'rnatish

```bash
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-curl php8.2-mbstring php8.2-json
```

#### 2. Nginx konfiguratsiya

`/etc/nginx/sites-available/amocrm-gateway`:

```nginx
server {
    listen 443 ssl http2;
    server_name gateway.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/gateway.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/gateway.yourdomain.com/privkey.pem;

    root /var/www/amocrm-integration-gateway/public;
    index index.php;

    access_log /var/log/nginx/amocrm-gateway-access.log;
    error_log /var/log/nginx/amocrm-gateway-error.log;

    client_max_body_size 10M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\. {
        deny all;
    }

    location ~* (\.env|tokens\.json)$ {
        deny all;
    }
}

server {
    listen 80;
    server_name gateway.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

#### 3. Fayl ruxsatlari

```bash
sudo chown -R www-data:www-data /var/www/amocrm-integration-gateway
sudo chmod -R 755 /var/www/amocrm-integration-gateway
sudo chmod -R 775 /var/www/amocrm-integration-gateway/storage
```

#### 4. Ishga tushirish

```bash
sudo ln -s /etc/nginx/sites-available/amocrm-gateway /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

---

## Monitoring va Logging

### Error Logs

Barcha xatolar `storage/error.log` faylga yoziladi:

```bash
tail -f storage/error.log
```

### Nginx Access Logs

```bash
tail -f /var/log/nginx/access.log
```

### Docker Logs

```bash
docker-compose logs -f
docker-compose logs -f php  # Faqat PHP logs
docker-compose logs -f nginx  # Faqat Nginx logs
```

### Health Monitoring

Cron job orqali health check:

```bash
# /etc/cron.d/amocrm-gateway-monitor
*/5 * * * * curl -sf http://localhost:9095/health > /dev/null || echo "Gateway down!" | mail -s "Alert" admin@example.com
```

### Log Rotation

`/etc/logrotate.d/amocrm-gateway`:

```
/var/www/amocrm-integration-gateway/storage/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    create 0644 www-data www-data
}
```

---

## Xatolarni tuzatish (Troubleshooting)

### 1. "Gateway is not running" - Health check ishlamayapti

**Sabab**: Docker container ishlamayotgan yoki Nginx/PHP ishlamagan

**Yechim**:

```bash
# Docker
docker-compose ps
docker-compose up -d

# Traditiona
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl restart nginx php8.2-fpm
```

---

### 2. "Invalid or missing API key" - 401 Unauthorized

**Sabab**: X-API-KEY header noto'g'ri yoki yuborilmagan

**Yechim**:

1. .env fayldagi API_KEY ni tekshiring
2. Request headerga to'g'ri qo'shilganini tasdiqlang:

```bash
curl -H "X-API-KEY: correct_api_key" http://localhost:9095/api/v1/...
```

---

### 3. "Failed to refresh token" - AmoCRM autentifikatsiya xatosi

**Sabab**: Token muddati tugagan va yangilanmayapti

**Yechim**:

1. `storage/tokens.json` faylni tekshiring
2. Token mavjud ekanligini tasdiqlang
3. Refresh token to'g'riligini tekshiring
4. AmoCRM'da integratsiya faol ekanligini ko'ring
5. Kerak bo'lsa, tokenni qayta oling (OAuth2 sozlash qismiga qarang)

```bash
cat storage/tokens.json
```

---

### 4. "CURL error" - AmoCRM'ga ulanib bo'lmayapti

**Sabab**: Network muammosi yoki AmoCRM server ishlamayapti

**Yechim**:

```bash
# AmoCRM'ga ulanishni tekshiring
curl https://yoursubdomain.amocrm.ru/api/v4/account

# DNS tekshirish
nslookup yoursubdomain.amocrm.ru

# Firewall tekshirish
sudo ufw status
```

---

### 5. "Permission denied" - Fayl yozish ruxsati yo'q

**Sabab**: storage/ papkasiga yozish huquqi yo'q

**Yechim**:

```bash
# Docker
docker-compose exec php chown -R www-data:www-data /var/www/html/storage
docker-compose exec php chmod -R 775 /var/www/html/storage

# Traditional
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

---

### 6. "Endpoint not found" - 404 xatosi

**Sabab**: URL noto'g'ri yoki Nginx rewrite sozlanmagan

**Yechim**:

1. URL to'g'riligini tekshiring
2. Nginx konfiguratsiyasida `try_files` sozlamasi borligini ko'ring
3. Nginx'ni qayta yuklang

```bash
sudo nginx -t
sudo systemctl reload nginx
```

---

### 7. Docker container ishga tushmayapti

**Sabab**: Port band, konfiguratsiya xatosi

**Yechim**:

```bash
# Portni tekshirish
sudo lsof -i :9095

# Loglarni ko'rish
docker-compose logs

# Container'ni qayta yaratish
docker-compose down
docker-compose up -d --force-recreate
```

---

## Debugging

### PHP Debugging

#### 1. Error reporting yoqish

`public/index.php` faylda:

```php
error_reporting(E_ALL);
ini_set('display_errors', '1');  // Development uchun
```

**MUHIM**: Production'da `display_errors` ni `'0'` qiling!

#### 2. Var_dump debugging

```php
// LeadController.php
public function createUnsorted(): void
{
    $rawBody = file_get_contents('php://input');
    var_dump($rawBody);  // Debug
    die();
}
```

#### 3. Error log tekshirish

```bash
tail -f storage/error.log
```

---

### Network Debugging

#### 1. AmoCRM so'rovlarini debug qilish

`src/AmoClient.php` da:

```php
error_log("AmoCRM Request: $method $endpoint");
error_log("AmoCRM Request Data: " . json_encode($data));
error_log("AmoCRM Response: $response");
```

#### 2. cURL verbose mode

```php
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);
```

---

### Request/Response Debugging

#### Postman/Insomnia ishlatish

1. **Postman** yoki **Insomnia** dasturini yuklab oling
2. Yangi request yarating:
   - **Method**: POST
   - **URL**: http://localhost:9095/api/v1/leads/unsorted
   - **Headers**: 
     - `Content-Type: application/json`
     - `X-API-KEY: your_api_key`
   - **Body**: JSON formatda

3. Response va error ma'lumotlarini tekshiring

---

### Docker Debugging

```bash
# Container ichiga kirish
docker-compose exec php sh
cd /var/www/html
ls -la storage/

# PHP versiyasi
docker-compose exec php php -v

# PHP extensions
docker-compose exec php php -m

# Logs
docker-compose logs --tail=100 php
```

---

## Xavfsizlik

### API Key Best Practices

1. **Kuchli API key**:
```bash
# Random API key generatsiya qilish
openssl rand -base64 32
```

2. **API keyni environment variablega qo'yish** - Hech qachon code'ga yozmang

3. **API key rotation** - Davriy ravishda yangilash

### HTTPS

Production'da **faqat HTTPS** ishlatish:

```nginx
# HTTP'ni HTTPS'ga yo'naltirish
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

### Rate Limiting

Nginx rate limiting qo'shish:

```nginx
# nginx.conf
http {
    limit_req_zone $binary_remote_addr zone=api_limit:10m rate=10r/s;
    
    server {
        location /api/ {
            limit_req zone=api_limit burst=20 nodelay;
        }
    }
}
```

### CORS Sozlash

Faqat ma'lum domenlardan ruxsat berish:

```php
// public/index.php
$allowedOrigins = ['https://mywebsite.com', 'https://admin.mywebsite.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
```

### Secrets Protection

1. **.env ni gitdan chiqaring**:
```bash
echo ".env" >> .gitignore
git rm --cached .env
```

2. **storage/ papkasini himoyalang**:
```nginx
location ~* tokens\.json$ {
    deny all;
}
```

3. **File permissions**:
```bash
chmod 600 .env
chmod 600 storage/tokens.json
```

---

## Hissa qo'shish

Loyihaga hissa qo'shmoqchimisiz? Ajoyib! Quyidagi qoidalarga rioya qiling:

### Pull Request jarayoni

1. **Fork qiling**:
```bash
# GitHub'da Fork tugmasini bosing
git clone https://github.com/YOUR_USERNAME/amocrm-integration-gateway.git
```

2. **Yangi branch yarating**:
```bash
git checkout -b feature/my-awesome-feature
```

3. **O'zgarishlar kiriting**:
```bash
git add .
git commit -m "feat: mening ajoyib feature"
```

4. **Push qiling**:
```bash
git push origin feature/my-awesome-feature
```

5. **Pull Request oching** GitHub'da

### Commit message formati

```
<type>: <subject>

<body>

<footer>
```

**Type'lar**:
- `feat`: Yangi feature
- `fix`: Bug fix
- `docs`: Dokumentatsiya
- `style`: Kod formatlash
- `refactor`: Refactoring
- `test`: Test qo'shish
- `chore`: Build yoki tool o'zgartirishlari

**Misol**:
```
feat: add pipeline filtering endpoint

- GET /api/v1/pipelines?status=active endpoint qo'shildi
- Status bo'yicha filtr qo'shildi
- Test case'lar yozildi

Closes #123
```

### Code Style

- PHP PSR-12 standardiga rioya qiling
- 4 space indentation ishlatil
- Commentlar Uzbek yoki English tilida

### Issues

Muammo topsangiz yoki feature so'ramoqchi bo'lsangiz, [GitHub Issues](https://github.com/blogchik/amocrm-integration-gateway/issues) da yangi issue oching.

---

## Litsenziya

Bu loyiha **Apache License 2.0** ostida litsenziyalangan. Batafsil ma'lumot uchun [LICENSE](LICENSE) faylga qarang.

Siz quyidagilarni qilishingiz mumkin:
- ✅ Komertsial maqsadlarda ishlatish
- ✅ O'zgartirish va tarqatish
- ✅ Patent huquqlaridan foydalanish
- ✅ Private loyihalarda ishlatish

Shartlar:
- 📄 Litsenziya va copyright eslatmasini saqlab qolish
- 📝 O'zgarishlarni belgilash
- ⚠️ Warranty va liability yo'qligi haqida ogohlantirish

---

## Muallif

**Jabborov Abduroziq**

- 📧 Email: [blogchikuz@gmail.com](mailto:blogchikuz@gmail.com)
- 🐙 GitHub: [@blogchik](https://github.com/blogchik)
- 📅 Yil: 2025

---

## FAQ (Tez-tez so'raladigan savollar)

### 1. AmoCRM OAuth2 tokenni qayerdan olaman?

[AmoCRM OAuth2 sozlash](#amocrm-oauth2-sozlash) bo'limiga qarang. U yerda batafsil qo'llanma bor.

### 2. API Key nima va uni qayerda olaman?

API Key siz o'zingiz yaratadigan xavfsiz string. `.env` faylda o'rnatiladi:
```env
API_KEY=my_super_secure_random_key_here
```

### 3. Docker ishlatish majburiyatmi?

Yo'q, Docker majburiy emas. Oddiy Nginx + PHP-FPM bilan ham ishlatish mumkin. Lekin Docker tavsiya etiladi.

### 4. Bir nechta AmoCRM accountlari bilan ishlash mumkinmi?

Hozircha bitta account uchun mo'ljallangan. Lekin kodni o'zgartirish orqali multi-account qilish mumkin.

### 5. Bu gateway bepulmi?

Ha, gateway to'liq bepul va open-source. AmoCRM'ning o'zi pullik CRM ekanligi esdan chiqmasin.

### 6. Qanday qilib yangilanishlarni kuzataman?

GitHub'da repository'ni **Watch** qiling yoki **Star** bosing:
```bash
git remote add upstream https://github.com/blogchik/amocrm-integration-gateway.git
git fetch upstream
git merge upstream/main
```

### 7. Commercial loyihalarda ishlatish mumkinmi?

Ha, Apache 2.0 litsenziyasi commercial foydalanishga ruxsat beradi.

---

## Support

Yordam kerakmi? Quyidagi usullardan foydalaning:

- 🐛 **Bug report**: [GitHub Issues](https://github.com/blogchik/amocrm-integration-gateway/issues)
- 💡 **Feature request**: [GitHub Issues](https://github.com/blogchik/amocrm-integration-gateway/issues)
- 📧 **Email**: [blogchikuz@gmail.com](mailto:blogchikuz@gmail.com)
- 💬 **Telegram**: [@JabborovAbduroziq](https://t.me/JabborovAbduroziq)

---

## Tashakkur

Ushbu loyihada foydalanilgan texnologiyalar:

- [PHP](https://www.php.net/)
- [Docker](https://www.docker.com/)
- [Nginx](https://nginx.org/)
- [AmoCRM API](https://www.amocrm.ru/developers/)

Hamda barcha contributor'larga rahmat! 🙏

---

## Changelog

### v1.0.0 (2025)

- ✨ Initial release
- 🚀 Unsorted leads yaratish
- 📊 Info endpoints (pipelines, fields, account)
- 🔐 OAuth2 token management
- 🐳 Docker containerization
- 📝 To'liq dokumentatsiya

---

**Loyihani yoqtirdingizmi? ⭐ Star bering!**

