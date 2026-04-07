#!/usr/bin/env bash
# Uso: ./scripts/reset-vendedores-password.sh
#      ./scripts/reset-vendedores-password.sh --password=otra
set -euo pipefail
cd "$(dirname "$0")/.."
php artisan users:reset-vendedores-password "$@"
