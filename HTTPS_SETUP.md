# HTTPS Setup untuk Railway

## Apa yang telah dikonfigurasi:

1. **Middleware ForceHttps** (`app/Http/Middleware/ForceHttps.php`)
   - Redirect HTTP ke HTTPS di production
   - Add HSTS header (Strict-Transport-Security) untuk security
   - Hanya enforce di `APP_ENV=production`

2. **TrustProxies Middleware** (updated)
   - Set `$proxies = '*'` agar trust Railway reverse proxy
   - Ini memungkinkan Laravel detect HTTPS dari X-Forwarded-Proto header

3. **Kernel.php** (updated)
   - Tambah `ForceHttps` middleware ke global middleware stack

## Konfigurasi di Railway:

Set environment variables di Railway dashboard:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.railway.app
```

Gantikan `your-app-name` dengan actual nama aplikasi di Railway.

## Cara Kerja:

1. User mengakses http://your-app.railway.app
2. Railway automatic redirect ke HTTPS (Railway provides SSL/TLS)
3. Request sampai ke Laravel dengan X-Forwarded-Proto: https
4. TrustProxies detect HTTPS dari header tersebut
5. ForceHttps middleware confirm HTTPS dan add security headers
6. User receive response over HTTPS

## Testing Lokal:

```bash
# Set di .env lokal
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# ForceHttps tidak akan enforce di lokal (hanya production)
# Ini memungkinkan testing tanpa SSL certificate
```

## Catatan:

- Railway otomatis provides SSL/TLS certificate (let's encrypt)
- Anda tidak perlu setup certificate secara manual
- HSTS header akan tell browsers untuk always use HTTPS untuk domain ini
- Jika ada issue, check Railway logs: `railway logs`
