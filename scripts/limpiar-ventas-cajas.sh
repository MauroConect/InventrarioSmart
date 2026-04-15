#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

if [[ ! -f "${PROJECT_ROOT}/artisan" ]]; then
  echo "No se encontro artisan en: ${PROJECT_ROOT}"
  echo "Verifica que el script este dentro de la carpeta scripts del proyecto Laravel."
  exit 1
fi

echo "Este script elimina datos de ventas y cajas."
echo "Tablas objetivo: items_venta, venta_adjuntos, movimientos_caja, ventas, cajas."
echo "No modifica usuarios ni productos."
read -r -p "Escribi LIMPIAR para confirmar: " CONFIRMACION

if [[ "${CONFIRMACION}" != "LIMPIAR" ]]; then
  echo "Operacion cancelada."
  exit 1
fi

php \"${PROJECT_ROOT}/artisan\" tinker --execute="
use Illuminate\Support\Facades\DB;

\$driver = DB::getDriverName();
if (\$driver === 'mysql') {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
} elseif (\$driver === 'sqlite') {
    DB::statement('PRAGMA foreign_keys = OFF');
}

DB::table('items_venta')->delete();
DB::table('venta_adjuntos')->delete();
DB::table('movimientos_caja')->delete();
DB::table('ventas')->delete();
DB::table('cajas')->delete();

if (\$driver === 'mysql') {
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
} elseif (\$driver === 'sqlite') {
    DB::statement('PRAGMA foreign_keys = ON');
}

echo 'Limpieza finalizada: ventas y cajas vaciadas.';
"

echo "Listo."
