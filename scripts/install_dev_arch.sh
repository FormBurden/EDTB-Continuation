#!/usr/bin/env bash
set -euo pipefail

echo "==> Installing PHP toolchain and MariaDB (Manjaro/Arch)..."
sudo pacman -Syu --noconfirm php php-fpm php-gd php-intl composer mariadb

echo "==> Initializing MariaDB (idempotent)..."
sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql || true
sudo systemctl enable --now mariadb

echo "==> Creating database and user (safe if already exists)..."
mariadb -uroot -e "CREATE DATABASE IF NOT EXISTS edtb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'edtb'@'localhost' IDENTIFIED BY 'edtbpass';
GRANT ALL PRIVILEGES ON edtb.* TO 'edtb'@'localhost';
FLUSH PRIVILEGES;"

echo "==> Enabling PHP extensions..."
sudo install -d /etc/php/conf.d
sudo tee /etc/php/conf.d/edtb.ini >/dev/null <<'INI'
extension=pdo_mysql
extension=mysqli
extension=intl
extension=gd
; zip is built-in on Arch; leave commented unless using shared zip module:
; extension=zip
INI

echo "==> Verifying extensions..."
php -m | egrep -i 'pdo_mysql|mysqli|intl|gd|zip' || true

cat <<'NEXT'

All set.

Next steps:
  1) cp .env.example .env
  2) composer install
  3) php -S 0.0.0.0:8080 -t public
  4) Open http://localhost:8080/health (should show {"ok": true, ... "db": true})

If "db" is false, confirm the DB creds in .env and that mariadb is running:
  sudo systemctl status mariadb

NEXT
