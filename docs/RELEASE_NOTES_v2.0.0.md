# Release Notes v2.0.0

## 🎉 AmoCRM Integration Gateway v2.0.0

**Sanasi:** 2025-11-20

### 🚀 Asosiy o'zgarishlar

#### 1. AmoCRM Rasmiy Kutubxonasi Integratsiyasi

Loyiha AmoCRM rasmiy PHP kutubxonasiga to'liq o'tkazildi:

- `amocrm/amocrm-api-library` v1.14+ qo'shildi
- Professional API client implementation
- To'liq type safety va error handling

#### 2. Avtomatik Token Refresh ⭐

**Eng muhim yangilik!** Endi tokenlar avtomatik yangilanadi:

- ✅ Token expire muammosi butunlay hal qilindi
- ✅ 24 soat yoki uzoqroq foydalanilmasa ham ishlaydi
- ✅ Qo'lda token yangilash kerak emas
- ✅ AmoCRM kutubxonasi refresh'ni o'zi boshqaradi
- ✅ Token lifecycle to'liq avtomatlashtirilgan

**Eski muammo:**

```
❌ 24 soat ishlatilmadi → token expire bo'ldi
❌ Qo'lda serverga kirib yangi token joylashtirish kerak edi
❌ Downtime va xizmat uzilishi
```

**Yangi yechim:**

```
✅ Istalgan vaqt ishlatilmasa ham ishlaydi
✅ Token avtomatik yangilanadi
✅ Zero downtime
✅ Zero maintenance
```

#### 3. Yangi Arxitektura

```
src/
├── OAuth/
│   ├── AmoOAuthConfig.php       # OAuth konfiguratsiya
│   └── AmoOAuthService.php      # Token lifecycle management
├── AmoClientV2.php               # Yangi API wrapper (Singleton)
└── Controllers/
    ├── LeadControllerV2.php      # Leads (yangi implementatsiya)
    ├── InfoControllerV2.php      # Info endpoints (yangi)
    └── OAuthController.php       # OAuth endpoints (yangi)
```

#### 4. OAuth2 Endpoints

Yangi endpointlar qo'shildi:

- `GET /oauth/authorize` - AmoCRM'ga avtorizatsiya
- `GET /oauth/callback` - OAuth callback handler
- `GET /oauth/status` - Token status checker

#### 5. Professional Error Handling

AmoCRM kutubxonasining exception sistemasi:

- `AmoCRMApiException` - barcha API xatolari
- `AmoCRMoAuthApiException` - OAuth xatolari
- To'liq error context va debugging info
- Structured error responses

### 🔄 O'zgarishlar

#### Qo'shilgan

- ✅ `composer.json` - dependency management
- ✅ `src/OAuth/*` - OAuth implementation
- ✅ `src/AmoClientV2.php` - Yangi API client
- ✅ `src/Controllers/*V2.php` - Yangi controllerlar
- ✅ `docs/MIGRATION_GUIDE.md` - Migration qo'llanma

#### O'zgartirilgan

- 🔄 `public/index.php` - Yangi route'lar va controllerlar
- 🔄 Token storage format - Base domain qo'shildi
- 🔄 Error logging - Yanada batafsil

#### Deprecated (lekin hali ishlaydi)

- ⚠️ `src/AmoAuth.php` - AmoClientV2 ishlatilsin
- ⚠️ `src/AmoClient.php` - AmoClientV2 ishlatilsin
- ⚠️ `src/AmoTokenStorage.php` - OAuth\AmoOAuthService ishlatilsin

### 🎯 API Compatibility

**100% Backward Compatible!** Barcha eski endpointlar ishlaydi:

- ✅ `POST /api/v1/leads/unsorted` - same
- ✅ `GET /api/v1/info/*` - same
- ✅ Request/Response format - same
- ✅ Authentication (X-API-KEY) - same

### 📦 Dependencies

```json
{
  "require": {
    "php": ">=8.2",
    "amocrm/amocrm-api-library": "^1.14",
    "ext-json": "*",
    "ext-curl": "*"
  }
}
```

### 🔧 Migration

#### Eski versiyadan o'tish:

```bash
# 1. Composer install
composer install

# 2. Eski tokenni o'chirish
rm storage/tokens.json

# 3. Qayta avtorizatsiya
curl https://your-domain.com/oauth/authorize

# 4. Test
curl https://your-domain.com/oauth/status
```

To'liq ma'lumot: [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)

### 🐛 Bug Fixes

- ✅ Token expire after 24 hours of inactivity
- ✅ Manual token refresh requirement
- ✅ Race conditions in token refresh
- ✅ Refresh token expiration handling
- ✅ Error context loss

### ⚡ Performance

- Singleton pattern - bir marta init
- File locking optimized
- Better error handling (less overhead)
- Composer autoloader optimized

### 🔐 Security

- OAuth2 protocol properly implemented
- Token storage permissions (0600)
- State validation in OAuth callback
- CSRF protection
- Secure session handling

### 📚 Documentation

- ✅ Migration guide
- ✅ Updated README
- ✅ Release notes
- ✅ Code comments
- ✅ API examples

### 🧪 Testing

Manual test checklist:

```bash
# Health check
curl https://your-domain.com/health

# OAuth flow
curl https://your-domain.com/oauth/authorize
curl https://your-domain.com/oauth/status

# Lead creation
curl -X POST https://your-domain.com/api/v1/leads/unsorted \
  -H "X-API-KEY: your-key" \
  -H "Content-Type: application/json" \
  -d @test-lead.json

# Info endpoints
curl https://your-domain.com/api/v1/info/pipelines \
  -H "X-API-KEY: your-key"
```

### 🎓 Learning Resources

- [AmoCRM API PHP Library GitHub](https://github.com/amocrm/amocrm-api-php)
- [AmoCRM API Documentation](https://www.amocrm.ru/developers/content/crm_platform/api-php-library)
- [OAuth2 Protocol](https://oauth.net/2/)

### 🙏 Thanks

AmoCRM jamoasiga rasmiy kutubxona uchun rahmat!

### 📅 Roadmap v2.1

- [ ] Webhooks support
- [ ] Multiple accounts support
- [ ] Advanced logging (Monolog)
- [ ] Unit tests
- [ ] CI/CD pipeline

---

**Full Changelog:** v1.0.1...v2.0.0

**Upgrade:** [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)
