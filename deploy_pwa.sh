#!/usr/bin/env bash
# =========================================================================
# TRA Smart Revenue Hub — PWA deployment & verification script
#
# Run this after uploading the project to your HTTPS host (cPanel or SSH
# server). It verifies the site is served over HTTPS, that manifest.json
# and service-worker.js are reachable with correct MIME types, and that
# icons resolve — the requirements browsers check before allowing
# "Add to Home Screen" / native-like install on Android and iOS.
#
# Usage:
#   ./deploy_pwa.sh https://yourdomain.co.tz/tra-revenue-hub
# =========================================================================

set -euo pipefail

BASE_URL="${1:-}"

if [[ -z "$BASE_URL" ]]; then
  echo "Usage: $0 https://yourdomain.co.tz/tra-revenue-hub"
  exit 1
fi

# Strip trailing slash
BASE_URL="${BASE_URL%/}"

pass=0
fail=0

check() {
  local label="$1"
  local url="$2"
  local expect_header="${3:-}"

  echo -n "Checking ${label} ... "
  headers=$(curl -sIL -m 15 "$url" || true)
  status=$(echo "$headers" | head -n1 | tr -d '\r')

  if echo "$status" | grep -qE "200|304"; then
    if [[ -n "$expect_header" ]] && ! echo "$headers" | grep -qi "$expect_header"; then
      echo "WARN (reachable, but missing header: $expect_header)"
    else
      echo "OK ($status)"
      pass=$((pass+1))
      return
    fi
  else
    echo "FAIL ($status)"
  fi
  fail=$((fail+1))
}

echo "=== TRA Smart Hub PWA deployment check ==="
echo "Target: $BASE_URL"
echo

# 1. HTTPS enforcement
echo -n "Checking HTTPS is enforced ... "
if [[ "$BASE_URL" == https://* ]]; then
  echo "OK (using https://)"
  pass=$((pass+1))
else
  echo "FAIL — PWAs require HTTPS. Update your domain/URL to https://"
  fail=$((fail+1))
fi

# 2. Core PWA files
check "manifest.json"          "$BASE_URL/manifest.json"        "content-type: application/manifest+json"
check "service-worker.js"      "$BASE_URL/service-worker.js"     "content-type: application/javascript"
check "offline.html fallback"  "$BASE_URL/offline.html"
check "index.html (app shell)" "$BASE_URL/index.html"

# 3. Icons referenced in manifest.json
for icon in icon-192.png icon-512.png icon-maskable-192.png icon-maskable-512.png apple-touch-icon.png favicon-32.png favicon-16.png; do
  check "icon: $icon" "$BASE_URL/assets/icons/$icon"
done

# 4. Stylesheet
check "stylesheet" "$BASE_URL/assets/css/style.css"

echo
echo "=== Result: $pass passed, $fail failed ==="

if [[ $fail -eq 0 ]]; then
  echo "All checks passed. The app should be installable on Android (Chrome menu > Install app)"
  echo "and iOS (Safari Share > Add to Home Screen)."
else
  echo "Some checks failed. Fix the issues above, then re-run this script."
  echo "Common causes: HTTP (not HTTPS) hosting, wrong upload path, or missing MIME headers."
  echo "See .htaccess (cPanel/Apache) or nginx-tra-smart-hub.conf (Nginx/SSH) for header config."
  exit 1
fi
