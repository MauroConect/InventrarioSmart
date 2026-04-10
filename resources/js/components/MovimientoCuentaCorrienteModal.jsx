import React, { useState, useEffect } from 'react';
import axios from 'axios';

/**
 * Admin: registrar pago (haber) o cargo manual (debe) en una cuenta corriente.
 */
export default function MovimientoCuentaCorrienteModal({ cuentaId, tituloCliente, open, onClose, onGuardado, tipoInicial = 'haber' }) {
    const [tipo, setTipo] = useState(tipoInicial);
    const [monto, setMonto] = useState('');
    const [concepto, setConcepto] = useState('');
    const [observaciones, setObservaciones] = useState('');
    const [enviando, setEnviando] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (open) {
            setTipo(tipoInicial);
            setMonto('');
            setConcepto(tipoInicial === 'haber' ? 'Pago recibido' : 'Cargo / ajuste');
            setObservaciones('');
            setError('');
        }
    }, [open, tipoInicial, cuentaId]);

    if (!open || !cuentaId) {
        return null;
    }

    const guardar = async (e) => {
        e.preventDefault();
        setError('');
        const m = parseFloat(monto);
        if (!m || m < 0.01) {
            setError('Ingrese un monto válido (mínimo 0,01).');
            return;
        }
        if (!concepto.trim()) {
            setError('El concepto es obligatorio.');
            return;
        }
        try {
            setEnviando(true);
            await axios.post(`cuentas-corrientes/${cuentaId}/movimiento`, {
                tipo,
                monto: m,
                concepto: concepto.trim(),
                observaciones: observaciones.trim() || null,
            });
            onGuardado?.();
            onClose();
        } catch (err) {
            const msg = err.response?.data?.message;
            setError(typeof msg === 'string' ? msg : 'No se pudo registrar el movimiento.');
        } finally {
            setEnviando(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" onClick={onClose}>
            <div
                className="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="border-b px-4 py-3 flex justify-between items-center">
                    <h3 className="text-lg font-semibold text-gray-900">Movimiento en cuenta</h3>
                    <button type="button" onClick={onClose} className="text-gray-500 hover:text-gray-800 text-xl leading-none">
                        ×
                    </button>
                </div>
                <form onSubmit={guardar} className="p-4 space-y-4">
                    {tituloCliente && <p className="text-sm text-gray-600">{tituloCliente}</p>}
                    {error && <div className="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2">{error}</div>}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select
                            value={tipo}
                            onChange={(e) => setTipo(e.target.value)}
                            className="w-full border border-gray-300 rounded-md px-3 py-2"
                        >
                            <option value="haber">Pago / ingreso (reduce la deuda)</option>
                            <option value="debe">Cargo al debe (aumenta la deuda)</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            value={monto}
                            onChange={(e) => setMonto(e.target.value)}
                            className="w-full border border-gray-300 rounded-md px-3 py-2"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                        <input
                            type="text"
                            value={concepto}
                            onChange={(e) => setConcepto(e.target.value)}
                            className="w-full border border-gray-300 rounded-md px-3 py-2"
                            maxLength={255}
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea
                            value={observaciones}
                            onChange={(e) => setObservaciones(e.target.value)}
                            rows={2}
                            className="w-full border border-gray-300 rounded-md px-3 py-2"
                        />
                    </div>
                    <div className="flex justify-end gap-2 pt-2">
                        <button type="button" onClick={onClose} className="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={enviando}
                            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {enviando ? 'Guardando…' : 'Registrar'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
