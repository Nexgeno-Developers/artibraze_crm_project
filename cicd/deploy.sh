#!/bin/bash
# Copies the checked-out repo into public_html.
# Run from the repo root. Used by both .cpanel.yml (cPanel "Deploy HEAD Commit")
# and .github/workflows/deploy.yml (automatic deploy on merge to main).
set -euo pipefail

DEPLOYPATH=/home2/arfashionnexgeno/public_html/

# Safety check: stop if the target isn't the live site
test -f "${DEPLOYPATH}index.php"

/bin/rsync -a --no-p --no-g --no-o --chmod=D755,F644 \
  --exclude '/.git*' \
  --exclude '/.github/' \
  --exclude '/.cpanel.yml' \
  --exclude '/cicd/' \
  --exclude '/application/config/app-config.php' \
  --exclude '/.htaccess*' \
  --exclude '/uploads/' \
  --exclude '/modules/*/uploads/' \
  --exclude '/media/' \
  --exclude '/temp/' \
  --exclude '/ci_sessions/' \
  --exclude '/application/cache/' \
  --exclude '/application/logs/' \
  --itemize-changes \
  ./ "$DEPLOYPATH"

find "${DEPLOYPATH}application/cache/" -type f ! -name 'index.html' ! -name '.htaccess' -delete 2>/dev/null || true

echo "Deployed $(git log -1 --oneline 2>/dev/null || echo 'commit')"
