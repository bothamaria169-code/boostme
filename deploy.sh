#!/bin/bash
# Run by cPanel "Deploy HEAD Commit" (.cpanel.yml). Copies the app from the Git clone into the
# subdomain folder. Never touches .env or uploaded media, and keeps cPanel's PHP-version
# handler lines in .htaccess.
set -e
SRC="$(cd "$(dirname "$0")" && pwd)"
DEST="${DEST:-/home/swiftfusionco/boostme.swiftfusion.co.za}"

mkdir -p "$DEST/uploads"
cp -R "$SRC/app" "$SRC/views" "$SRC/assets" "$DEST/"
cp "$SRC/index.php" "$SRC/cron.php" "$SRC/BoostMe-Guide.pdf" "$SRC/.env.example" "$DEST/"
cp "$SRC/uploads/.htaccess" "$DEST/uploads/.htaccess"

# .htaccess = ours + the block cPanel's MultiPHP Manager writes (if any).
HANDLER=""
if [ -f "$DEST/.htaccess" ]; then
  HANDLER="$(sed -n '/# php -- BEGIN cPanel-generated handler/,/# php -- END cPanel-generated handler/p' "$DEST/.htaccess")"
fi
cp "$SRC/.htaccess" "$DEST/.htaccess.new"
if [ -n "$HANDLER" ]; then
  printf '\n%s\n' "$HANDLER" >> "$DEST/.htaccess.new"
fi
mv "$DEST/.htaccess.new" "$DEST/.htaccess"

echo "BoostMe deployed to $DEST"
