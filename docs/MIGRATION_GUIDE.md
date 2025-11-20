# AmoCRM Integration Gateway - Migration Guide

## 🔄 Yangi versiyaga o'tish

### Nima o'zgardi?

Loyiha AmoCRM rasmiy PHP kutubxonasiga o'tkazildi. Bu quyidagi muammolarni hal qiladi:

1. ✅ **Avtomatik token refresh** - Kutubxona o'zi tokenlarni yangilaydi
2. ✅ **Token expire muammosi yo'q** - 24 soat foydalanilmasa ham muammo bo'lmaydi
3. ✅ **To'liq AmoCRM API yordami** - Barcha metodlar va xususiyatlar
4. ✅ **Error handling** - Professional xatolar bilan ishlash
5. ✅ **Maintenance oson** - Rasmiy kutubxona doimiy yangilanadi

### O'rnatish

1. **Composer orqali dependency'larni o'rnatish:**

```bash
cd /path/to/project
composer install
```

2. **`.env` faylni yangilash:**

`.env` fayl o'zgarishsiz qoladi, faqat `TOKEN_STORAGE_PATH` tekshiring:

```env
# AmoCRM OAuth2 Settings
AMO_DOMAIN=yoursubdomain.amocrm.ru
AMO_CLIENT_ID=your-client-id-here
AMO_CLIENT_SECRET=your-client-secret-here
AMO_REDIRECT_URI=https://your-domain.com/oauth/callback

# Token storage
TOKEN_STORAGE_PATH=./storage/tokens.json
```

3. **Eski tokenlarni o'chirish:**

```bash
rm storage/tokens.json
```

Yangi token olish uchun qayta avtorizatsiya qilish kerak.

### Avtorizatsiya jarayoni

1. Brauzerda ochish: `https://your-domain.com/oauth/authorize`
2. AmoCRM'ga login qiling va ruxsat bering
3. Avtomatik `https://your-domain.com/oauth/callback` ga yo'naltiriladi
4. Token saqlanadi va ishlatishga tayyor

Token statusini tekshirish:
```bash
curl https://your-domain.com/oauth/status \
  -H "X-API-KEY: your-api-key"
```

### API Endpointlar

**Hech narsa o'zgarmadi!** Barcha endpointlar oldingiday ishlaydi:

#### Lead yaratish
```bash
POST /api/v1/leads/unsorted
X-API-KEY: your-api-key
Content-Type: application/json

{
  "source": "website",
  "form_name": "Contact Form",
  "lead": {
    "name": "Test Lead",
    "price": 5000
  },
  "contact": {
    "name": "John Doe",
    "phone": "+998901234567",
    "email": "john@example.com"
  }
}
```

#### Ma'lumot olish
```bash
GET /api/v1/info/pipelines
GET /api/v1/info/pipelines/{id}
GET /api/v1/info/lead-fields
GET /api/v1/info/contact-fields
GET /api/v1/info/account
```

### Docker bilan ishlatish

```bash
# Build
docker-compose build

# Run
docker-compose up -d

# Composer install (container ichida)
docker-compose exec app composer install
```

### Asosiy farqlar

| Eski implementatsiya | Yangi implementatsiya |
|---------------------|----------------------|
| Manual CURL so'rovlar | Rasmiy AmoCRM kutubxonasi |
| Manual token refresh | Avtomatik token refresh |
| Token expire xatosi | Hech qachon expire bo'lmaydi |
| Limited error handling | To'liq error handling |
| Faqat basic API | To'liq AmoCRM API |

### Arxitektura

```
src/
├── OAuth/
│   ├── AmoOAuthConfig.php      # OAuth konfiguratsiya
│   └── AmoOAuthService.php     # Token saqlash mexanizmi
├── AmoClientV2.php              # AmoCRM API wrapper
└── Controllers/
    ├── LeadControllerV2.php     # Lead yaratish (yangi)
    ├── InfoControllerV2.php     # Ma'lumot olish (yangi)
    └── OAuthController.php      # Avtorizatsiya endpoints
```

### Yangi imkoniyatlar

Rasmiy kutubxona orqali qo'shimcha qulayliklar:

```php
$client = AmoClientV2::getInstance();

// Leads
$client->leads()->get();
$client->leads()->add($leadsCollection);

// Contacts  
$client->contacts()->get();

// Companies
$client->companies()->get();

// Custom fields
$client->customFields()->get(EntityTypesInterface::LEADS);

// Va boshqalar...
```

### Migration Checklist

- [x] Composer install qilish
- [x] Eski tokenlarni o'chirish
- [x] Qayta avtorizatsiya qilish (`/oauth/authorize`)
- [x] Token statusini tekshirish (`/oauth/status`)
- [x] Test lead yuborish
- [x] Production'ga deploy qilish

### Troubleshooting

**Token yo'q xatosi:**
```bash
curl https://your-domain.com/oauth/authorize
```

**Composer dependency xatosi:**
```bash
composer install --no-dev --optimize-autoloader
```

**Docker bilan muammo:**
```bash
docker-compose down
docker-compose up --build -d
docker-compose exec app composer install
```

### Qo'shimcha yordam

Agar muammolar bo'lsa:
1. `storage/error.log` faylini tekshiring
2. AmoCRM API dokumentatsiyasi: https://www.amocrm.ru/developers/
3. Kutubxona: https://github.com/amocrm/amocrm-api-php

---

**Muhim:** Yangi versiyada token hech qachon expire bo'lmaydi va qo'lda yangilash kerak bo'lmaydi! 🎉
