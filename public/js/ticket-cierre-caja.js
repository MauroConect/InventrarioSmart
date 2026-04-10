/**
 * Ticket cierre de caja (Blade / sin Vite). Expone window.imprimirTicketCierreCaja y window.construirPayloadTicketCierreCaja.
 */
(function () {
    'use strict';

    function escapeHtml(text) {
        if (text == null) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function construirPayloadTicketCierre(opts) {
        var resumenCierre = opts.resumenCierre;
        var montoReal = opts.montoReal;
        var observaciones = opts.observaciones;
        var cajaCerrada = opts.cajaCerrada;
        var r = resumenCierre.resumen;
        var c = resumenCierre.caja;
        var montoRealNum = typeof montoReal === 'number' ? montoReal : parseFloat(montoReal);
        var esperado =
            cajaCerrada && cajaCerrada.monto_esperado != null
                ? parseFloat(cajaCerrada.monto_esperado)
                : parseFloat(r.monto_esperado);
        var dif =
            cajaCerrada && cajaCerrada.diferencia != null
                ? parseFloat(cajaCerrada.diferencia)
                : montoRealNum - esperado;
        var obs =
            cajaCerrada && cajaCerrada.observaciones != null && cajaCerrada.observaciones !== ''
                ? cajaCerrada.observaciones
                : observaciones || '';

        return {
            esBorrador: !(cajaCerrada && cajaCerrada.fecha_cierre),
            fechaCierre: (cajaCerrada && cajaCerrada.fecha_cierre) || null,
            cajaNombre: c.nombre,
            cajaId: c.id,
            usuarioNombre: (c.usuario && c.usuario.name) || '',
            fechaApertura: c.fecha_apertura,
            montoApertura: parseFloat(r.monto_apertura),
            totalVentas: parseFloat(r.total_ventas),
            cantidadVentas: r.cantidad_ventas,
            totalIngresos: parseFloat(r.total_ingresos),
            totalEgresos: parseFloat(r.total_egresos),
            montoEsperado: esperado,
            montoReal:
                cajaCerrada && cajaCerrada.monto_real != null
                    ? parseFloat(cajaCerrada.monto_real)
                    : montoRealNum,
            diferencia: dif,
            observaciones: obs,
            porMedioPago: r.por_medio_pago || {},
            ventasCuentaCorriente: Array.isArray(r.ventas_cuenta_corriente) ? r.ventas_cuenta_corriente : [],
        };
    }

    function buildHtml(p) {
        var pm = p.porMedioPago || {};
        var titulo = p.esBorrador ? 'RELEVAMIENTO DE CAJA' : 'CIERRE DE CAJA';
        var fechaCierreStr = p.fechaCierre
            ? new Date(p.fechaCierre).toLocaleString('es-AR')
            : new Date().toLocaleString('es-AR');
        var fechaAperturaStr = p.fechaApertura ? new Date(p.fechaApertura).toLocaleString('es-AR') : '';

        function row(label, value) {
            return (
                '<div class="row"><span class="l">' +
                escapeHtml(label) +
                '</span><span class="r">' +
                escapeHtml(value) +
                '</span></div>'
            );
        }

        function money(n) {
            return '$' + Number(n || 0).toFixed(2);
        }
        var dif = Number(p.diferencia || 0);
        var difStr = (dif >= 0 ? '+' : '-') + '$' + Math.abs(dif).toFixed(2);

        var obs = String(p.observaciones || '').trim();
        var obsBlock = obs ? '<div class="obs">' + escapeHtml(obs) + '</div>' : '';

        var vcc = p.ventasCuentaCorriente || [];
        var ccDetalle = '';
        if (vcc.length) {
            ccDetalle =
                '\n    <div class="sep"></div>\n    <div class="sub" style="font-size:7px">Cta. cte. por cliente</div>\n    ';
            for (var i = 0; i < vcc.length; i++) {
                var vc = vcc[i];
                var parts = [];
                if (vc.numero_factura) parts.push(vc.numero_factura);
                parts.push(vc.cliente_nombre || 'Sin cliente');
                ccDetalle += row(parts.join(' · '), money(vc.total_final)) + '\n    ';
            }
        }

        return (
            '<!DOCTYPE html>\n<html lang="es">\n<head>\n    <meta charset="UTF-8">\n    <title>Danielles — ' +
            escapeHtml(titulo) +
            '</title>\n    <style>\n        * { box-sizing: border-box; }\n        @media print {\n            @page { size: auto; margin: 3mm; }\n            * {\n                -webkit-print-color-adjust: exact !important;\n                print-color-adjust: exact !important;\n            }\n            html, body {\n                width: auto !important;\n                max-width: 220px !important;\n                margin: 0 auto !important;\n                padding: 0 !important;\n                background: #fff !important;\n                color: #000 !important;\n            }\n            .no-print { display: none !important; }\n        }\n        body {\n            font-family: Arial, Helvetica, sans-serif;\n            max-width: 48mm;\n            margin: 0 auto;\n            padding: 2mm;\n            font-size: 9px;\n            line-height: 1.2;\n            background: #fff;\n            color: #000;\n        }\n        h1 {\n            margin: 0 0 2mm;\n            font-size: 11px;\n            text-align: center;\n            border-bottom: 1px dashed #333;\n            padding-bottom: 2mm;\n        }\n        .sub { text-align: center; font-size: 10px; font-weight: bold; margin-bottom: 2mm; }\n        .row {\n            display: table;\n            width: 100%;\n            margin-bottom: 2px;\n            font-size: 8px;\n        }\n        .row span { display: table-cell; vertical-align: top; }\n        .row .l { color: #000; padding-right: 2px; }\n        .row .r { text-align: right; font-weight: 600; }\n        .sep { border-top: 1px dashed #333; margin: 2mm 0; padding-top: 2mm; }\n        .muted { font-size: 7px; color: #333; text-align: center; margin-top: 3mm; border-top: 1px dashed #ccc; padding-top: 2mm; }\n        .obs { font-size: 7px; margin-top: 2mm; white-space: pre-wrap; word-break: break-word; }\n        .btn { text-align: center; margin-top: 4mm; }\n        button {\n            background: #007bff;\n            color: #fff;\n            border: none;\n            padding: 6px 12px;\n            font-size: 10px;\n            cursor: pointer;\n            border-radius: 4px;\n        }\n    </style>\n</head>\n<body>\n    <h1>Danielles</h1>\n    <div class="sub">' +
            escapeHtml(titulo) +
            '</div>\n    ' +
            row('Caja', (p.cajaNombre || '—') + ' (#' + p.cajaId + ')') +
            '\n    ' +
            row('Usuario', p.usuarioNombre || '—') +
            '\n    ' +
            row('Apertura', fechaAperturaStr) +
            '\n    ' +
            row('Monto apertura', money(p.montoApertura)) +
            '\n    ' +
            (!p.esBorrador ? row('Cierre', fechaCierreStr) : row('Impreso', fechaCierreStr)) +
            '\n    <div class="sep"></div>\n    ' +
            row('Cant. ventas', String(p.cantidadVentas)) +
            '\n    ' +
            row('Total ventas', money(p.totalVentas)) +
            '\n    ' +
            row('Ingresos caja', money(p.totalIngresos)) +
            '\n    ' +
            row('Egresos caja', money(p.totalEgresos)) +
            '\n    <div class="sep"></div>\n    ' +
            row('Efectivo (ventas)', money(pm.efectivo)) +
            '\n    ' +
            row('Tarjeta', money(pm.tarjeta)) +
            '\n    ' +
            row('Transferencia', money(pm.transferencia)) +
            '\n    ' +
            row('Cta. corriente', money(pm.cuenta_corriente)) +
            ccDetalle +
            '\n    <div class="sep"></div>\n    ' +
            row('Monto esperado', money(p.montoEsperado)) +
            '\n    ' +
            row('Monto real', money(p.montoReal)) +
            '\n    ' +
            row('Diferencia', difStr) +
            '\n    ' +
            obsBlock +
            '\n    <p class="muted">Documento interno de control de caja.</p>\n    <div class="btn no-print">\n        <button type="button" onclick="window.print()">Imprimir</button>\n    </div>\n</body>\n</html>'
        );
    }

    function abrirImpresionTicketCierre(payload) {
        var html = buildHtml(payload);
        var w = window.open('', '_blank');
        if (!w) {
            return false;
        }
        w.document.open();
        w.document.write(html);
        w.document.close();
        setTimeout(function () {
            try {
                w.focus();
                w.print();
            } catch (e) {}
        }, 450);
        return true;
    }

    window.construirPayloadTicketCierreCaja = construirPayloadTicketCierre;
    window.imprimirTicketCierreCaja = abrirImpresionTicketCierre;
})();
