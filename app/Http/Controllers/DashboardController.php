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
            ->whereIn('estado', ['cerrada', 'completada'])
            ->count();
        
        // Monto total de ventas de hoy
        $montoVentasHoy = Venta::whereDate('fecha', $hoy)
            ->whereIn('estado', ['cerrada', 'completada'])
            ->sum('total_final');
        
        // Productos con stock bajo
        $productosStockBajo = Producto::where('activo', true)
            ->whereRaw('stock_actual <= stock_minimo')
            ->count();
        
        // Ventas del mes actual
        $ventasMes = Venta::whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->whereIn('estado', ['cerrada', 'completada'])
            ->count();
        
        $montoVentasMes = Venta::whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->whereIn('estado', ['cerrada', 'completada'])
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
            ->whereIn('estado', ['cerrada', 'completada'])
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
            ->whereIn('ventas.estado', ['cerrada', 'completada'])
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
            ->whereIn('estado', ['cerrada', 'completada'])
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
            'transferencia' => 'Transferencia',
            'cuenta_corriente' => 'Cuenta Corriente',
            'mixto' => 'Mixto',
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

    /**
     * Informe de rankings de ventas (productos, vendedores, clientes, cajas) para administración.
     * Solo ventas cerradas o completadas en el rango de fechas.
     */
    public function rankingVentas(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'limite' => 'nullable|integer|min:5|max:100',
        ]);

        $fin = Carbon::parse($validated['fecha_fin'] ?? Carbon::now()->toDateString())->endOfDay();
        $inicio = Carbon::parse($validated['fecha_inicio'] ?? Carbon::now()->startOfMonth()->toDateString())->startOfDay();

        if ($inicio->gt($fin)) {
            return response()->json([
                'message' => 'La fecha de inicio no puede ser posterior a la fecha de fin.',
            ], 422);
        }

        $limite = (int) ($validated['limite'] ?? 25);
        $limite = max(5, min(100, $limite));

        $estados = ['cerrada', 'completada'];

        $resumenVentas = Venta::query()
            ->whereBetween('fecha', [$inicio, $fin])
            ->whereIn('estado', $estados);

        $cantidadVentas = (int) (clone $resumenVentas)->count();
        $montoTotal = (float) (clone $resumenVentas)->sum('total_final');
        $ticketPromedio = $cantidadVentas > 0 ? round($montoTotal / $cantidadVentas, 2) : 0.0;

        $unidadesVendidas = (int) DB::table('items_venta')
            ->join('ventas', 'items_venta.venta_id', '=', 'ventas.id')
            ->whereBetween('ventas.fecha', [$inicio, $fin])
            ->whereIn('ventas.estado', $estados)
            ->sum('items_venta.cantidad');

        $productos = DB::table('items_venta')
            ->join('productos', 'items_venta.producto_id', '=', 'productos.id')
            ->join('ventas', 'items_venta.venta_id', '=', 'ventas.id')
            ->whereBetween('ventas.fecha', [$inicio, $fin])
            ->whereIn('ventas.estado', $estados)
            ->select(
                'productos.id',
                'productos.codigo',
                'productos.nombre',
                DB::raw('SUM(items_venta.cantidad) as cantidad_vendida'),
                DB::raw('SUM(items_venta.subtotal) as total_vendido')
            )
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre')
            ->orderByDesc('cantidad_vendida')
            ->limit($limite)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'codigo' => $row->codigo,
                'nombre' => $row->nombre,
                'cantidad_vendida' => (int) $row->cantidad_vendida,
                'total_vendido' => round((float) $row->total_vendido, 2),
            ]);

        $vendedores = DB::table('ventas')
            ->leftJoin('users', 'ventas.usuario_id', '=', 'users.id')
            ->whereBetween('ventas.fecha', [$inicio, $fin])
            ->whereIn('ventas.estado', $estados)
            ->select(
                'ventas.usuario_id',
                DB::raw('COALESCE(users.name, \'Sin asignar\') as nombre'),
                DB::raw('COALESCE(users.email, \'\') as email'),
                DB::raw('COUNT(*) as cantidad_ventas'),
                DB::raw('SUM(ventas.total_final) as monto_total')
            )
            ->groupBy('ventas.usuario_id', 'users.id', 'users.name', 'users.email')
            ->orderByDesc('monto_total')
            ->limit($limite)
            ->get()
            ->map(fn ($row) => [
                'usuario_id' => $row->usuario_id !== null ? (int) $row->usuario_id : null,
                'nombre' => $row->nombre,
                'email' => $row->email,
                'cantidad_ventas' => (int) $row->cantidad_ventas,
                'monto_total' => round((float) $row->monto_total, 2),
            ]);

        $clientes = DB::table('ventas')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->whereBetween('ventas.fecha', [$inicio, $fin])
            ->whereIn('ventas.estado', $estados)
            ->select(
                'clientes.id',
                'clientes.nombre',
                'clientes.apellido',
                DB::raw('COUNT(*) as cantidad_compras'),
                DB::raw('SUM(ventas.total_final) as monto_total')
            )
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellido')
            ->orderByDesc('monto_total')
            ->limit($limite)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'nombre' => trim($row->nombre.' '.$row->apellido),
                'cantidad_compras' => (int) $row->cantidad_compras,
                'monto_total' => round((float) $row->monto_total, 2),
            ]);

        $cajas = DB::table('ventas')
            ->join('cajas', 'ventas.caja_id', '=', 'cajas.id')
            ->whereBetween('ventas.fecha', [$inicio, $fin])
            ->whereIn('ventas.estado', $estados)
            ->select(
                'cajas.id',
                'cajas.nombre',
                DB::raw('COUNT(*) as cantidad_ventas'),
                DB::raw('SUM(ventas.total_final) as monto_total')
            )
            ->groupBy('cajas.id', 'cajas.nombre')
            ->orderByDesc('monto_total')
            ->limit($limite)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'nombre' => $row->nombre ?: ('Caja #'.$row->id),
                'cantidad_ventas' => (int) $row->cantidad_ventas,
                'monto_total' => round((float) $row->monto_total, 2),
            ]);

        $nombresTipoPago = [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia',
            'cuenta_corriente' => 'Cuenta corriente',
            'mixto' => 'Mixto',
        ];

        $porTipoPago = DB::table('ventas')
            ->whereBetween('fecha', [$inicio, $fin])
            ->whereIn('estado', $estados)
            ->select(
                'tipo_pago',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(total_final) as monto')
            )
            ->groupBy('tipo_pago')
            ->orderByDesc('monto')
            ->get()
            ->map(fn ($row) => [
                'tipo_pago' => $row->tipo_pago,
                'label' => $nombresTipoPago[$row->tipo_pago] ?? ucfirst((string) $row->tipo_pago),
                'cantidad' => (int) $row->cantidad,
                'monto' => round((float) $row->monto, 2),
            ]);

        return response()->json([
            'periodo' => [
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $fin->toDateString(),
            ],
            'resumen' => [
                'cantidad_ventas' => $cantidadVentas,
                'monto_total' => round($montoTotal, 2),
                'ticket_promedio' => $ticketPromedio,
                'unidades_vendidas' => $unidadesVendidas,
            ],
            'productos' => $productos,
            'vendedores' => $vendedores,
            'clientes' => $clientes,
            'cajas' => $cajas,
            'por_tipo_pago' => $porTipoPago,
        ]);
    }
}
