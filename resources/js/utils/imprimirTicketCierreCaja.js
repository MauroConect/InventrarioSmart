/**
 * Ticket térmico de cierre / relevamiento de caja (misma base que ventas: drivers USB).
 */

function escapeHtml(text) {
    if (text == null) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Arma el payload para imprimir desde el modal de cierre.
 * @param {object} opts
 * @param {object} opts.resumenCierre - respuesta GET resumen-cierre
 * @param {number|string} opts.montoReal
 * @param {string} [opts.observaciones]
 * @param {object} [opts.cajaCerrada] - respuesta POST cerrar (con fecha_cierre, monto_esperado, diferencia…)
 */
export function construirPayloadTicketCierre({ resumenCierre, montoReal, observaciones, cajaCerrada }) {
    const r = resumenCierre.resumen;
    const c = resumenCierre.caja;
    const montoRealNum = typeof montoReal === 'number' ? montoReal : parseFloat(montoReal);
    const esperado =
        cajaCerrada?.monto_esperado != null ? parseFloat(cajaCerrada.monto_esperado) : parseFloat(r.monto_esperado);
    const dif =
        cajaCerrada?.diferencia != null
            ? parseFloat(cajaCerrada.diferencia)
            : montoRealNum - esperado;
    const obs = cajaCerrada?.observaciones ?? observaciones ?? '';

    return {
        esBorrador: !cajaCerrada?.fecha_cierre,
        fechaCierre: cajaCerrada?.fecha_cierre || null,
        cajaNombre: c.nombre,
        cajaId: c.id,
        usuarioNombre: c.usuario?.name || '',
        fechaApertura: c.fecha_apertura,
        montoApertura: parseFloat(r.monto_apertura),
        totalVentas: parseFloat(r.total_ventas),
        cantidadVentas: r.cantidad_ventas,
        totalIngresos: parseFloat(r.total_ingresos),
        totalEgresos: parseFloat(r.total_egresos),
        montoEsperado: esperado,
        montoReal: cajaCerrada?.monto_real != null ? parseFloat(cajaCerrada.monto_real) : montoRealNum,
        diferencia: dif,
        observaciones: obs,
        porMedioPago: r.por_medio_pago || {},
    };
}

function buildHtml(p) {
    const pm = p.porMedioPago || {};
    const titulo = p.esBorrador ? 'RELEVAMIENTO DE CAJA' : 'CIERRE DE CAJA';
    const fechaCierreStr = p.fechaCierre
        ? new Date(p.fechaCierre).toLocaleString('es-AR')
        : new Date().toLocaleString('es-AR');
    const fechaAperturaStr = p.fechaApertura ? new Date(p.fechaApertura).toLocaleString('es-AR') : '';

    const row = (label, value) =>
        `<div class="row"><span class="l">${escapeHtml(label)}</span><span class="r">${escapeHtml(value)}</span></div>`;

    const money = (n) => `$${Number(n || 0).toFixed(2)}`;
    const dif = Number(p.diferencia || 0);
    const difStr = (dif >= 0 ? '+' : '-') + '$' + Math.abs(dif).toFixed(2);

    const obs = String(p.observaciones || '').trim();
    const obsBlock = obs
        ? `<div class="obs">${escapeHtml(obs)}</div>`
        : '';

    return `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Danielles — ${escapeHtml(titulo)}</title>
    <style>
        * { box-sizing: border-box; }
        @media print {
            @page { size: auto; margin: 3mm; }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            html, body {
                width: auto !important;
                max-width: 220px !important;
                margin: 0 auto !important;
                padding: 0 !important;
                background: #fff !important;
                color: #000 !important;
            }
            .no-print { display: none !important; }
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            max-width: 48mm;
            margin: 0 auto;
            padding: 2mm;
            font-size: 9px;
            line-height: 1.2;
            background: #fff;
            color: #000;
        }
        h1 {
            margin: 0 0 2mm;
            font-size: 11px;
            text-align: center;
            border-bottom: 1px dashed #333;
            padding-bottom: 2mm;
        }
        .sub { text-align: center; font-size: 10px; font-weight: bold; margin-bottom: 2mm; }
        .row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
            font-size: 8px;
        }
        .row span { display: table-cell; vertical-align: top; }
        .row .l { color: #000; padding-right: 2px; }
        .row .r { text-align: right; font-weight: 600; }
        .sep { border-top: 1px dashed #333; margin: 2mm 0; padding-top: 2mm; }
        .muted { font-size: 7px; color: #333; text-align: center; margin-top: 3mm; border-top: 1px dashed #ccc; padding-top: 2mm; }
        .obs { font-size: 7px; margin-top: 2mm; white-space: pre-wrap; word-break: break-word; }
        .btn { text-align: center; margin-top: 4mm; }
        button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 6px 12px;
            font-size: 10px;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Danielles</h1>
    <div class="sub">${escapeHtml(titulo)}</div>
    ${row('Caja', `${p.cajaNombre || '—'} (#${p.cajaId})`)}
    ${row('Usuario', p.usuarioNombre || '—')}
    ${row('Apertura', fechaAperturaStr)}
    ${row('Monto apertura', money(p.montoApertura))}
    ${!p.esBorrador ? row('Cierre', fechaCierreStr) : row('Impreso', fechaCierreStr)}
    <div class="sep"></div>
    ${row('Cant. ventas', String(p.cantidadVentas))}
    ${row('Total ventas', money(p.totalVentas))}
    ${row('Ingresos caja', money(p.totalIngresos))}
    ${row('Egresos caja', money(p.totalEgresos))}
    <div class="sep"></div>
    ${row('Efectivo (ventas)', money(pm.efectivo))}
    ${row('Tarjeta', money(pm.tarjeta))}
    ${row('Transferencia', money(pm.transferencia))}
    ${row('Cta. corriente', money(pm.cuenta_corriente))}
    <div class="sep"></div>
    ${row('Monto esperado', money(p.montoEsperado))}
    ${row('Monto real', money(p.montoReal))}
    ${row('Diferencia', difStr)}
    ${obsBlock}
    <p class="muted">Documento interno de control de caja.</p>
    <div class="btn no-print">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>
</body>
</html>`;
}

export function abrirImpresionTicketCierre(payload) {
    const html = buildHtml(payload);
    const w = window.open('', '_blank');
    if (!w) {
        return false;
    }
    w.document.open();
    w.document.write(html);
    w.document.close();
    setTimeout(() => {
        try {
            w.focus();
            w.print();
        } catch (e) {
            /* ignore */
        }
    }, 450);
    return true;
}
