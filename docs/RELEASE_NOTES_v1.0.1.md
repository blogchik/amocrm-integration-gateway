# Release v1.0.1 - Critical Token Refresh Fix & File Locking

**Chiqarilgan sana:** 2025-11-16
**Versiya:** 1.0.1
**Turi:** Patch Release (Critical Bug Fix)

---

## 🔧 Tuzatilgan Muammolar

### Critical Bug Fix: Token Refresh Race Condition

Bir vaqtda bir nechta request kelganda **"Failed to refresh token"** xatosi yuzaga kelgan muammo to'liq hal qilindi.

**Muammoning sabablari:**

- Bir vaqtda bir nechta request kelganda, ikkala request ham token refresh qilishga harakat qilardi
- AmoCRM refresh token'i faqat **bir marta** ishlatiladi va har safar yangi refresh token beriladi
- Birinchi request muvaffaqiyatli bo'lsa, ikkinchi request eski token bilan urinib "Token has been revoked" xatosini olardi
- `storage/tokens.json` fayliga bir vaqtda bir nechta process yozib, JSON corruption yuzaga kelardi

---

## ✨ Yangi Imkoniyatlar

### 1. File Locking Mexanizmi (`AmoTokenStorage.php`)

Token faylini o'qish va yozishda `flock()` mexanizmi qo'shildi:

- **Shared Lock (LOCK_SH)** - Bir nechta process bir vaqtda o'qiy oladi
- **Exclusive Lock (LOCK_EX)** - Faqat bitta process yozish huquqiga ega
- JSON corruption oldini oladi
- Atomic write operations

```php
// Shared lock bilan o'qish
flock($fp, LOCK_SH);

// Exclusive lock bilan yozish
flock($fp, LOCK_EX);
```

### 2. Token Refresh Mutex (`AmoClient.php`)

Yangi `refreshTokenWithLock()` metodi qo'shildi:

- Alohida lock file (`storage/refresh.lock`) orqali parallel refresh oldini olish
- Faqat bitta process token refresh qiladi, boshqalar kutib turadi
- 10 soniya timeout - agar lock ololmasa, xato qaytaradi
- Lock ichida qayta tekshirish - boshqa process yangilagan bo'lsa, skip qiladi

**Texnik tafsilotlar:**

- Non-blocking lock attempt (LOCK_NB)
- 100ms interval bilan retry
- Double-check pattern - race condition oldini olish

### 3. Yaxshilangan Logging (`AmoAuth.php`)

Token refresh jarayonini batafsil kuzatish:

- Token refresh boshlanishi va tugashi to'g'risida loglar
- Revoked token holatini aniqlash va CRITICAL log yozish
- Xatoliklarni batafsil log qilish (HTTP status, hint, response)
- 30 soniya CURL timeout qo'shildi
- Refresh token formatini validatsiya qilish

**Log misollari:**

```
Attempting to refresh token for domain: yoursubdomain.amocrm.ru
Token successfully refreshed. New expires_at: 1763381921
CRITICAL: Refresh token has been revoked. Manual re-authorization required!
```

### 4. Token Validation

Yangi `validateTokens()` metodi qo'shildi (`AmoTokenStorage.php`):

- Token holatini to'liq tekshirish
- JWT formatini validatsiya qilish (access token 3 qismdan iborat bo'lishi kerak)
- Refresh token mavjudligi va uzunligini tekshirish
- Token muddatini real-time hisoblash
- Validation natijalarini batafsil qaytarish

**Qaytariladigan ma'lumotlar:**

```json
{
  "valid": true,
  "expired": false,
  "expires_at": 1763381921,
  "expires_in": 86400
}
```

### 5. Diagnostics Endpoints

Monitoring va debugging uchun yangi endpointlar:

#### `GET /api/v1/diagnostics/token-status`

Token holatini real-time tekshirish:

```bash
curl -H "X-API-KEY: your-key" http://localhost:9095/api/v1/diagnostics/token-status
```

**Response misoli:**

```json
{
  "success": true,
  "data": {
    "token_validation": {
      "valid": true,
      "expired": false,
      "expires_at": 1763381921,
      "expires_in": 86400
    },
    "storage_path": "./storage/tokens.json",
    "current_time": 1763295521,
    "current_datetime": "2025-11-16 10:25:21",
    "access_token_preview": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImU2...",
    "refresh_token_length": 856,
    "expires_datetime": "2025-11-17 10:25:21"
  }
}
```

#### `GET /api/v1/diagnostics/config`

Konfiguratsiya sozlamalarini ko'rish (xavfsiz, maxfiy ma'lumotlar yashirilgan):

```bash
curl -H "X-API-KEY: your-key" http://localhost:9095/api/v1/diagnostics/config
```

**Response misoli:**

```json
{
  "success": true,
  "data": {
    "domain": "yoursubdomain.amocrm.ru",
    "client_id": "6e67098080...",
    "redirect_uri": "http://localhost:9095/oauth/callback",
    "token_storage_path": "./storage/tokens.json"
  }
}
```

---

## 📚 Yangi Hujjatlar

### `TROUBLESHOOTING.md`

Token refresh muammosi va yechimi to'g'risida to'liq qo'llanma qo'shildi:

**Qamrab olingan mavzular:**

- Muammoning batafsil tahlili
- Race condition tushuntirish
- File locking mexanizmi
- Yechimlarning texnik tafsilotlari
- Monitoring va debugging yo'l-yo'riqlari
- Test qilish usullari
- Tez-tez uchraydigan xatolar va yechimlar
- Best practices va maslahatlar

**Foydalanish:**

```bash
cat TROUBLESHOOTING.md
# yoki
code TROUBLESHOOTING.md
```

---

## 🔄 O'zgargan Fayllar

### Modified Files (4 ta)

1. **`src/AmoTokenStorage.php`**

   - `load()` metodi - shared lock bilan o'qish
   - `save()` metodi - exclusive lock bilan yozish
   - `getAccessToken()` - JWT format validatsiya
   - `validateTokens()` - yangi metod
2. **`src/AmoClient.php`**

   - `__construct()` - lock file path qo'shildi
   - `request()` - lock mexanizmi bilan yangilandi
   - `refreshTokenWithLock()` - yangi metod (mutex)
3. **`src/AmoAuth.php`**

   - `refreshToken()` - yaxshilangan logging
   - `makeRequest()` - timeout va error handling
4. **`public/index.php`**

   - DiagnosticsController import
   - 2 ta yangi route qo'shildi

### New Files (1 ta)

1. **`src/Controllers/DiagnosticsController.php`** (yangi)

   - `getTokenStatus()` metodi
   - `getConfig()` metodi

---

## 🚀 Yangilash (Migration)

Yangi versiyaga o'tish uchun quyidagi qadamlarni bajaring:

### 1. Kod yangilash

```bash
# Git repository yangilash
git pull origin main

# yoki GitHub'dan ZIP yuklash
```

### 2. Docker container qayta ishga tushirish

```bash
# Container to'xtatish
docker-compose down

# Rebuild va restart
docker-compose up -d --build

# Loglarni kuzatish
docker-compose logs -f php
```

### 3. Token holatini tekshirish

PowerShell:

```powershell
$headers = @{"X-API-KEY" = "SUPER_SECRET_KEY"}
Invoke-RestMethod -Uri "http://localhost:9095/api/v1/diagnostics/token-status" -Headers $headers | ConvertTo-Json -Depth 5
```

Bash/cURL:

```bash
curl -H "X-API-KEY: SUPER_SECRET_KEY" http://localhost:9095/api/v1/diagnostics/token-status | jq
```

### 4. Loglarni tekshirish

```bash
# Docker log
docker-compose logs -f php

# yoki file log
tail -f storage/error.log
```

---

## ⚠️ Breaking Changes

**Yo'q** - Bu patch release bo'lib, barcha API endpointlar oldingi kabi ishlaydi.

Hech qanday konfiguratsiya o'zgartirish yoki migration script talab qilinmaydi.

---

## 📊 Texnik Statistika

| Metrika              | Qiymat |
| -------------------- | ------ |
| O'zgargan fayllar    | 4 ta   |
| Yangi fayllar        | 1 ta   |
| Qo'shilgan qatorlar  | ~350   |
| O'chirilgan qatorlar | ~50    |
| Yangi metodlar       | 4 ta   |
| Yangi endpointlar    | 2 ta   |

---

## 🐛 Tuzatilgan Xatolar

### Issue #1: Failed to refresh token (Race Condition)

**Sababi:** Parallel requestlar bir vaqtda eski refresh token bilan yangilanishga harakat qilardi

**Yechim:** Mutex-based locking mexanizmi

**Natija:** Token refresh 100% muvaffaqiyatli, parallel requestlarda xatolar yo'q

### Issue #2: JSON Corruption

**Sababi:** Bir vaqtda bir nechta process `tokens.json` fayliga yozardi

**Yechim:** Exclusive file lock (flock LOCK_EX)

**Natija:** Token fayli har doim to'g'ri formatda saqlanadi

### Issue #3: Poor Error Logging

**Sababi:** Token refresh xatolarini debug qilish qiyin edi

**Yechim:** Batafsil logging va diagnostics endpoints

**Natija:** Xatolarni tez aniqlash va tuzatish mumkin

---

## 🔒 Xavfsizlik

Ushbu release xavfsizlikni yaxshilaydi:

- **File Permissions:** Token fayli uchun 0600 ruxsati o'rnatiladi (faqat owner o'qiy va yoza oladi)
- **Sensitive Data Protection:** Diagnostics endpointlarda maxfiy ma'lumotlar yashiriladi
- **Timeout Protection:** CURL requestlarda 30 soniya timeout
- **Lock Timeout:** Token refresh lockda 10 soniya timeout

---

## 🧪 Test Qilish

### Race Condition Testi

Bir vaqtda 5 ta parallel request yuboring:

```powershell
# PowerShell
1..5 | ForEach-Object -Parallel {
    Invoke-RestMethod -Uri "http://localhost:9095/api/v1/info/account" `
        -Headers @{"X-API-KEY"="SUPER_SECRET_KEY"}
} -ThrottleLimit 5
```

### Token Expiry Simulation

```bash
# tokens.json faylidagi expires_at ni o'zgartiring
# Keyin biror endpoint'ga request yuboring
curl -H "X-API-KEY: your-key" http://localhost:9095/api/v1/info/account
```

### Lock Mechanism Test

```bash
# Bir vaqtda bir nechta terminal ochib, har birida:
curl -H "X-API-KEY: your-key" http://localhost:9095/api/v1/info/account
```

---

## 📖 Qo'shimcha Resurslar

- **README:** `README.md`
- **API Documentation:** README.md ichida
- **AmoCRM OAuth2 Docs:** https://www.amocrm.ru/developers/content/oauth/oauth

---

## 🙏 Credits

Bu release quyidagi texnologiyalar va dasturlash prinsiplariga asoslangan:

- PHP `flock()` - File locking
- Mutex pattern - Concurrency control
- AmoCRM OAuth2 - Token management
- Docker - Containerization

---

**Full Changelog:** https://github.com/blogchik/amocrm-integration-gateway/compare/v1.0.0...v1.0.1

**Download:** https://github.com/blogchik/amocrm-integration-gateway/releases/tag/v1.0.1
