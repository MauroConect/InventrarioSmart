<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Obtener estadísticas generales del dashboard
     */
    public function estadisticas(Request $request)
    {
        $usuarioId = $request->user()->id;
        $hoy = Carbon::today();
        
        // Caja abierta del usuario actual
        $cajaAbierta = Caja::where('usuario_id', $usuarioId)
            ->where('estado', 'abierta')
            ->first();
        
        $montoCajaAbierta = 0;
        if ($cajaAbierta) {
            $totalVentas = $cajaAbierta->ventas()->sum('total_final');
            $totalIngresos = $cajaAbierta->movimientos()->where('tipo', 'ingreso')->sum('monto');
            $totalEgresos = $cajaAbierta->movimientos()->where('tipo', 'egreso')->sum('monto');
            $montoCajaAbierta = $cajaAbierta->monto_apertura + $totalVentas + $totalIngresos - $totalEgresos;
        }
        
        // Total de productos activos
        $totalProductos = Producto::where('activo', true)->count();
        
        // Total de clientes activos
        $totalClientes = Cliente::where('activo', true)->count();
        
        // Ventas del día de hoy
        $ventasHoy = Venta::whereDate('fecha', $hoy)
            ->where('estado', 'completada')
            ->count();
        
        // Monto total de ventas de hoy
        $montoVentasHoy = Venta::whereDate('fecha', $hoy)
            ->where('estado', 'completada')
            ->sum('total_final');
        
        // Productos con stock bajo
        $productosStockBajo = Producto::where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->count();
        
        // Ventas del mes actual
        $ventasMes = Venta::whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->where('estado', 'completada')
            ->count();
        
        $montoVentasMes = Venta::whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->where('estado', 'completada')
            ->sum('total_final');
        
        // Deudas pendientes
        $deudasPendientes = DB::table('deudas_clientes')
            ->where('estado', '!=', 'pagada')
            ->sum('monto_pendiente');
        
        // Cheques próximos a vencer (7 días)
        $chequesProximos = DB::table('cheques')
            ->where('estado', 'pendiente')
            ->whereBetween('fecha_vencimiento', [
                $hoy->toDateString(),
                $hoy->copy()->addDays(7)->toDateString()
            ])
            ->count();
        
        return response()->json([
            'caja_abierta' => (float) round($montoCajaAbierta, 2),
            'total_productos' => (int) $totalProductos,
            'total_clientes' => (int) $totalClientes,
            'ventas_hoy' => (int) $ventasHoy,
            'monto_ventas_hoy' => (float) round($montoVentasHoy, 2),
            'productos_stock_bajo' => (int) $productosStockBajo,
            'ventas_mes' => (int) $ventasMes,
            'monto_ventas_mes' => (float) round($montoVentasMes, 2),
            'deudas_pendientes' => (float) round($deudasPendientes, 2),
            'cheques_proximos' => (int) $chequesProximos,
            'tiene_caja_abierta' => (bool) ($cajaAbierta !== null),
        ]);
    }
    
    /**
     * Obtener gráfico de ventas por día del mes actual
     */
    public function ventasPorDia(Request $request)
    {
        $mes = $request->input('mes', Carbon::now()->month);
        $ano = $request->input('ano', Carbon::now()->year);
        
        $ventas = Venta::select(
                DB::raw('DATE(fecha) as fecha'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total_final) as total')
            )
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $ano)
            ->where('estado', 'completada')
            ->groupBy(DB::raw('DATE(fecha)'))
            ->orderBy('fecha', 'asc')
            ->get();
        
        return response()->json($ventas);
    }
    
    /**
     * Obtener productos más vendidos
     */
    public function productosMasVendidos(Request $request)
    {
        $limite = $request->input('limite', 10);
        $fechaInicio = $request->input('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', Carbon::now()->toDateString());
        
        $productos = DB::table('items_venta')
            ->join('productos', 'items_venta.producto_id', '=', 'productos.id')
            ->join('ventas', 'items_venta.venta_id', '=', 'ventas.id')
            ->whereBetween('ventas.fecha', [$fechaInicio, $fechaFin])
            ->where('ventas.estado', 'completada')
            ->select(
                'productos.id',
                'productos.codigo',
                'productos.nombre',
                DB::raw('SUM(items_venta.cantidad) as cantidad_vendida'),
                DB::raw('SUM(items_venta.subtotal) as total_vendido')
            )
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre')
            ->orderBy('cantidad_vendida', 'desc')
            ->limit($limite)
            ->get();
        
        return response()->json($productos);
    }
    
    /**
     * Obtener resumen de cajas del mes
     */
    public function resumenCajas(Request $request)
    {
        $mes = $request->input('mes', Carbon::now()->month);
        $ano = $request->input('ano', Carbon::now()->year);
        
        $cajas = Caja::whereMonth('fecha_apertura', $mes)
            ->whereYear('fecha_apertura', $ano)
            ->where('estado', 'cerrada')
            ->select(
                DB::raw('COUNT(*) as total_cajas'),
                DB::raw('SUM(monto_cierre) as total_cierre'),
                DB::raw('AVG(diferencia) as diferencia_promedio')
            )
            ->first();
        
        return response()->json([
            'total_cajas' => $cajas->total_cajas ?? 0,
            'total_cierre' => round($cajas->total_cierre ?? 0, 2),
            'diferencia_promedio' => round($cajas->diferencia_promedio ?? 0, 2),
        ]);
    }
    
    /**
     * Obtener ventas agrupadas por tipo de pago
     */
    public function ventasPorTipoPago(Request $request)
    {
        $mes = $request->input('mes', Carbon::now()->month);
        $ano = $request->input('ano', Carbon::now()->year);
        
        $ventas = Venta::whereMonth('fecha', $mes)
            ->whereYear('fecha', $ano)
            ->where('estado', 'completada')
            ->select(
                'tipo_pago',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total_final) as total')
            )
            ->groupBy('tipo_pago')
            ->get();
        
        // Formatear nombres
        $nombres = [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'cuenta_corriente' => 'Cuenta Corriente',
            'mixto' => 'Mixto'
        ];
        
        $datosFormateados = $ventas->map(function($item) use ($nombres) {
            return [
                'name' => $nombres[$item->tipo_pago] ?? ucfirst($item->tipo_pago),
                'value' => round($item->total, 2),
                'cantidad' => $item->cantidad,
            ];
        });
        
        return response()->json($datosFormateados);
    }
}
