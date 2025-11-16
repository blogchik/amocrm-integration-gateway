# AmoCRM Integration Gateway - Token Refresh Muammosi va Yechimi

## 🔍 Aniqlangan Muammolar

### 1. **Race Condition (Parallel Requestlar)**
Bir vaqtning o'zida bir nechta request kelganda, ikkala request ham token refresh qilishga harakat qilardi. AmoCRM'da refresh token faqat **bir marta** ishlatiladi va har safar yangi refresh token beriladi.

**Sabab:**
- Request A token refresh qiladi va yangi tokenlar oladi
- Request B eski refresh token bilan refresh qilishga harakat qiladi
- Request B xato beradi: "Token has been revoked"

### 2. **File Lock Yo'qligi**
`tokens.json` fayliga bir vaqtda bir nechta process yozishi mumkin edi, natijada:
- JSON corruption
- Token ma'lumotlarining yo'qolishi
- Noto'g'ri tokenlar saqlanishi

### 3. **Token Muddati**
AmoCRM tokenlarining muddati:
- Access Token: 86400 soniya (24 soat)
- Token 60 soniya oldin avtomatik yangilanadi
- Lekin agar loyiha uzoq vaqt ishlatilmasa, token muddati o'tadi

## ✅ Amalga Oshirilgan Yechimlar

### 1. File Locking (flock)
**`AmoTokenStorage.php`** fayliga qo'shildi:
- `load()` - Shared lock (LOCK_SH) - bir nechta process o'qiy oladi
- `save()` - Exclusive lock (LOCK_EX) - faqat bitta process yozadi

```php
// Shared lock bilan o'qish
flock($fp, LOCK_SH);

// Exclusive lock bilan yozish
flock($fp, LOCK_EX);
```

### 2. Mutex Mexanizmi
**`AmoClient.php`** fayliga `refreshTokenWithLock()` metodi qo'shildi:
- Alohida lock file (`storage/refresh.lock`) yaratiladi
- Faqat bitta process token refresh qiladi
- Boshqa processlar kutib turadi
- 10 soniya timeout (agar lock ololmasa)

```php
// Lock olish
$locked = flock($fp, LOCK_EX | LOCK_NB);

// Lock ichida token refresh
if (!$this->storage->isExpired()) {
    // Boshqa process allaqachon yangilagan
    return true;
}
```

### 3. Yaxshilangan Logging
**`AmoAuth.php`** fayliga qo'shildi:
- Token refresh jarayonini kuzatish
- Xatoliklarni batafsil logga yozish
- Revoked token holatini aniqlash

```php
error_log('Attempting to refresh token for domain: ' . $this->domain);
error_log('Token successfully refreshed. New expires_at: ' . $expires_at);
```

### 4. Token Validation
**`AmoTokenStorage.php`** fayliga `validateTokens()` metodi qo'shildi:
- Access token JWT formatini tekshiradi
- Refresh token mavjudligini tekshiradi
- Muddati o'tganligini aniqlaydi

### 5. Diagnostics Endpoint
Yangi controller: **`DiagnosticsController.php`**

Token holatini real-time tekshirish uchun:

```bash
# Token holati
GET http://localhost:9095/api/v1/diagnostics/token-status
Headers: X-API-KEY: your-api-key

# Konfiguratsiya
GET http://localhost:9095/api/v1/diagnostics/config
Headers: X-API-KEY: your-api-key
```

## 🚀 Ishga Tushirish

### 1. Docker Container Qayta Ishga Tushirish

```bash
# Stop containers
docker-compose down

# Rebuild and start
docker-compose up -d --build

# Loglarni ko'rish
docker-compose logs -f php
```

### 2. Token Holatini Tekshirish

PowerShell:
```powershell
$headers = @{
    "X-API-KEY" = "SUPER_SECRET_KEY"
}
Invoke-RestMethod -Uri "http://localhost:9095/api/v1/diagnostics/token-status" -Headers $headers | ConvertTo-Json -Depth 5
```

cURL:
```bash
curl -H "X-API-KEY: SUPER_SECRET_KEY" http://localhost:9095/api/v1/diagnostics/token-status
```

### 3. Agar Token Muddati O'tgan Bo'lsa

Token muddati o'tgan bo'lsa va refresh qilib bo'lmasa, **qayta avtorizatsiya** qilish kerak:

1. AmoCRM'ga kiring va yangi authorization code oling
2. Code'ni ishlatib yangi tokenlar oling:

```php
$auth = new \App\AmoAuth();
$code = 'YOUR_NEW_AUTH_CODE';
$auth->getTokenByCode($code);
```

## 📊 Monitoring va Debugging

### Error Log Tekshirish

```bash
# Docker ichida
docker exec -it amocrm_gateway_php tail -f /var/www/html/storage/error.log

# Local fayldan
tail -f storage/error.log
```

### Tokenlar Faylini Ko'rish

```bash
# Pretty print
cat storage/tokens.json | jq

# PowerShell
Get-Content storage\tokens.json | ConvertFrom-Json | ConvertTo-Json -Depth 5
```

## 🔧 Test Qilish

### 1. Race Condition Testi

Bir vaqtda bir nechta request yuboring:

```powershell
# PowerShell parallel requests
1..5 | ForEach-Object -Parallel {
    Invoke-RestMethod -Uri "http://localhost:9095/api/v1/info/account" `
        -Headers @{"X-API-KEY"="SUPER_SECRET_KEY"}
} -ThrottleLimit 5
```

### 2. Token Expiry Simulation

`tokens.json` faylidagi `expires_at` qiymatini o'zgartiring:

```json
{
    "expires_at": 1600000000  // O'tgan sana
}
```

Keyin biror endpoint'ga request yuboring va logni kuzating.

## 📝 Muhim Eslatmalar

### AmoCRM OAuth2 Xususiyatlari

1. **Refresh Token bir marta ishlatiladi** - har safar yangi refresh token beriladi
2. **Access Token 24 soat amal qiladi** - keyin refresh kerak
3. **Revoked Token** - agar token bekor qilinsa, qayta avtorizatsiya kerak

### Best Practices

1. **Loglarni kuzatib boring** - `storage/error.log`
2. **Token holatini monitoring qiling** - diagnostics endpoint
3. **Parallel requestlarni testing qiling** - production'da shunday holatlar bo'lishi mumkin
4. **Backup qiling** - `tokens.json` faylini backup olib turing

## 🆘 Tez-tez Uchraydigan Xatolar

### "Failed to refresh token"
**Sabablari:**
- Refresh token muddati o'tgan (6 oy)
- Refresh token revoked qilingan
- AmoCRM OAuth settings o'zgargan

**Yechim:** Qayta avtorizatsiya qiling

### "Token has been revoked"
**Sabablari:**
- Refresh token allaqachon ishlatilgan
- AmoCRM admindan revoke qilingan

**Yechim:** Yangi authorization code bilan token oling

### "Access token is empty"
**Sabablari:**
- `tokens.json` fayli buzilgan
- Token saqlanmagan

**Yechim:** Diagnostics endpoint bilan tekshiring va qayta avtorizatsiya qiling

## 📞 Qo'shimcha Ma'lumot

Agar muammo davom etsa:

1. `docker-compose logs -f php` - container loglarini ko'ring
2. `storage/error.log` - PHP error loglarini tekshiring
3. Diagnostics endpointlarni ishlating
4. AmoCRM'da OAuth integration settings'ni tekshiring

---

**Versiya:** 2.0  
**Sana:** 2025-11-16  
**O'zgarishlar:** Token refresh race condition fix, file locking, diagnostics
