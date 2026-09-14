#!/data/data/com.termux/files/usr/bin/bash

set -e

cd "$(dirname "$0")"

if ! command -v php >/dev/null 2>&1; then
    echo "PHP غير مثبت. نفذ: pkg install php"
    exit 1
fi

echo "Smart Wallet running at http://127.0.0.1:8000"
php -S 127.0.0.1:8000 router.php