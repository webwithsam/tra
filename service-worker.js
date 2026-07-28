// TRA Smart Revenue Hub — Service Worker
// Enables offline access to the app shell and graceful fallback for dynamic pages.

const CACHE_VERSION = "tra-smart-hub-v2";
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;

// Core "app shell" — always cached on install so the app opens instantly, even offline.
const APP_SHELL = [
  "/tra/index.html",
  "/tra/manifest.json",
  "/tra/assets/css/style.css",
  "/tra/assets/icons/icon-192.png",
  "/tra/assets/icons/icon-512.png",
  "/tra/assets/icons/icon-maskable-192.png",
  "/tra/assets/icons/icon-maskable-512.png",
  "/tra/assets/icons/apple-touch-icon.png",
  "/tra/offline.html",
];

// Install: pre-cache the app shell.
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches
      .open(STATIC_CACHE)
      .then((cache) => cache.addAll(APP_SHELL))
      .then(() => self.skipWaiting())
  );
});

// Activate: clean up old cache versions.
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((key) => key.startsWith("tra-smart-hub-") && key !== STATIC_CACHE && key !== RUNTIME_CACHE)
            .map((key) => caches.delete(key))
        )
      )
      .then(() => self.clients.claim())
  );
});

// Fetch strategy:
// - Static assets (CSS/JS/images/fonts/CDN libs): cache-first, fall back to network.
// - Navigation requests (HTML/PHP pages): network-first, fall back to cache, then offline page.
// - Everything else: network-first with runtime caching.
self.addEventListener("fetch", (event) => {
  const { request } = event;

  if (request.method !== "GET") {
    return; // Never cache POST (payments) or other mutating requests.
  }

  const url = new URL(request.url);

  // Navigation requests -> network-first so payment/dashboard data stays fresh,
  // but still work offline by serving the last cached copy or offline page.
  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(RUNTIME_CACHE).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() =>
          caches.match(request).then((cached) => cached || caches.match("/tra/offline.html"))
        )
    );
    return;
  }

  const isStaticAsset =
    url.pathname.startsWith("/tra/assets/") ||
    url.hostname === "cdn.jsdelivr.net";

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) return cached;
        return fetch(request).then((response) => {
          const clone = response.clone();
          caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
          return response;
        });
      })
    );
    return;
  }

  // Default: network-first, runtime cache fallback.
  event.respondWith(
    fetch(request)
      .then((response) => {
        const clone = response.clone();
        caches.open(RUNTIME_CACHE).then((cache) => cache.put(request, clone));
        return response;
      })
      .catch(() => caches.match(request))
  );
});
