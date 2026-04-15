#!/usr/bin/env bash
set -euo pipefail

echo "Este script elimina datos de ventas y cajas."
echo "Tablas objetivo: items_venta, venta_adjuntos, movimientos_caja, ventas, cajas."
echo "No modifica usuarios ni productos."
read -r -p "Escribi LIMPIAR para confirmar: " CONFIRMACION

if [[ "${CONFIRMACION}" != "LIMPIAR" ]]; then
  echo "Operacion cancelada."
  exit 1
fi

php artisan tinker --execute="
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
