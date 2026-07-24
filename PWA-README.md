# TRA Smart Revenue Hub — PWA Setup

This project has been converted into a **Progressive Web App (PWA)**. Once deployed over HTTPS, users can install it to their home screen on Android and iOS and it will behave like a native app — full-screen, offline-capable app shell, and its own launcher icon.

## What was added

| File | Purpose |
|---|---|
| `manifest.json` | App metadata: name, icons, theme/background color, start URL, display mode, shortcuts. |
| `service-worker.js` | Caches the app shell (HTML/CSS/icons) for offline access; network-first for dynamic PHP pages with offline fallback. |
| `offline.html` | Shown when a page can't be reached and isn't cached. |
| `assets/icons/*.png` | App icons: 192x192, 512x512, maskable variants (Android adaptive icons), Apple touch icon (iOS), favicons. |
| `.htaccess` | Apache/cPanel config: forces HTTPS, sets correct MIME types, disables caching on the service worker/manifest, adds security headers, blocks direct access to `receipts/`. |
| `nginx-tra-smart-hub.conf` | Equivalent config for a plain Nginx/SSH server (no cPanel). |
| `deploy_pwa.sh` | Post-deploy verification script — checks HTTPS, manifest, service worker, icons all resolve correctly. |
| `index.html` | Updated `<head>` with manifest link, theme-color, Apple/iOS meta tags, favicons, service worker registration script, and a custom "Install app" banner. |

## Why HTTPS is required

Browsers only allow service worker registration and "Add to Home Screen" installability on secure origins (`https://` or `localhost`). Both the `.htaccess` and `nginx-tra-smart-hub.conf` files force a redirect from HTTP to HTTPS.

## Deploying on cPanel

1. Upload the entire `tra-revenue-hub/` folder to your domain's document root (e.g. `public_html/` or a subfolder).
2. Confirm an SSL certificate is active for the domain (cPanel → SSL/TLS Status → AutoSSL, or install your own certificate).
3. The included `.htaccess` will automatically force HTTPS and set the correct headers — no extra steps needed if `mod_rewrite` and `mod_headers` are enabled (they are by default on virtually all cPanel hosts).
4. Update `APP_BASE_URL` in `config.php` to your real HTTPS domain.
5. If you deploy to a subfolder (e.g. `https://yourdomain.co.tz/tra-revenue-hub/`), keep the `start_url`, `scope`, and shortcut URLs in `manifest.json` matching that path (already set to `/tra-revenue-hub/` — update if your path differs).

## Deploying on a plain Linux/SSH server (Nginx)

1. Copy `nginx-tra-smart-hub.conf` to `/etc/nginx/sites-available/tra-revenue-hub`, symlink it into `sites-enabled`, and adjust `server_name`, `root`, and the PHP-FPM socket path.
2. Obtain a certificate with Certbot: `sudo certbot --nginx -d yourdomain.co.tz`.
3. Reload Nginx: `sudo nginx -t && sudo systemctl reload nginx`.
4. If serving from the domain root (not a subfolder), update `manifest.json`'s `start_url`/`scope` to `/` and the paths inside `service-worker.js`'s `APP_SHELL` array to drop the `/tra-revenue-hub` prefix.

## Verifying the install experience

Run the included script after deployment:

```bash
./deploy_pwa.sh https://yourdomain.co.tz/tra-revenue-hub
```

It checks that HTTPS is enforced and that `manifest.json`, `service-worker.js`, `offline.html`, and all icons return HTTP 200 with the right content types.

### Manual checks
- **Android (Chrome):** open the site, tap the browser menu → "Install app" or wait for the automatic install banner built into `index.html`.
- **iOS (Safari):** open the site, tap Share → "Add to Home Screen" (iOS does not support the automatic `beforeinstallprompt` banner — this is an Apple platform limitation, not a bug).
- **Desktop (Chrome/Edge):** an install icon appears in the address bar.
- **Lighthouse audit:** Chrome DevTools → Lighthouse → PWA category, to confirm all installability criteria pass.

## Notes on the service worker strategy

- Static assets (CSS, icons, CDN libraries) are served **cache-first** for instant loads and offline availability.
- Page navigations (`index.html`, `dashboard.php`, `receipt.php`) are **network-first** so payment status and receipts stay accurate; if offline, the last cached version is shown, or `offline.html` if nothing is cached yet.
- POST requests (payment submissions) are never cached or intercepted — payments always require a live connection.
- Cache version is controlled by `CACHE_VERSION` in `service-worker.js`. Bump this string whenever you change the app shell files so returning users get the update instead of a stale cache.
