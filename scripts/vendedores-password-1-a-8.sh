#!/usr/bin/env bash
# Asigna a cada vendedor (por id) la clave 1, 2, … 8 y vuelve a empezar (9º → 1, etc.).
set -euo pipefail
cd "$(dirname "$0")/.."
php artisan users:assign-vendedores-passwords-1-8
