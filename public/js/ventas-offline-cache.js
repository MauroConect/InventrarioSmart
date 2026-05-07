/**
 * Caché local y cola de ventas para operar sin conexión (página Ventas / Blade).
 * Expone window.VentasOffline.
 */
(function () {
    'use strict';

    var DB_NAME = 'inventario_inteligente_offline';
    var DB_VERSION = 1;
    var SNAPSHOT_KEYS = {
        catalog: 'catalog',
        ventas: 'ventas_list',
    };

    function openDb() {
        return new Promise(function (resolve, reject) {
            var req = indexedDB.open(DB_NAME, DB_VERSION);
            req.onerror = function () {
                reject(req.error);
            };
            req.onupgradeneeded = function (e) {
                var db = e.target.result;
                if (!db.objectStoreNames.contains('snapshots')) {
                    db.createObjectStore('snapshots');
                }
                if (!db.objectStoreNames.contains('outbox')) {
                    db.createObjectStore('outbox', { keyPath: 'localId' });
                }
            };
            req.onsuccess = function () {
                resolve(req.result);
            };
        });
    }

    function idbPut(storeName, key, value) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(storeName, 'readwrite');
                var st = tx.objectStore(storeName);
                if (storeName === 'snapshots') {
                    st.put(value, key);
                } else {
                    st.put(value);
                }
                tx.oncomplete = function () {
                    resolve();
                };
                tx.onerror = function () {
                    reject(tx.error);
                };
            });
        });
    }

    function idbGet(storeName, key) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(storeName, 'readonly');
                var st = tx.objectStore(storeName);
                var g = storeName === 'snapshots' ? st.get(key) : st.get(key);
                g.onsuccess = function () {
                    resolve(g.result);
                };
                g.onerror = function () {
                    reject(g.error);
                };
            });
        });
    }

    function idbGetAllOutbox() {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction('outbox', 'readonly');
                var st = tx.objectStore('outbox');
                var g = st.getAll();
                g.onsuccess = function () {
                    resolve(g.result || []);
                };
                g.onerror = function () {
                    reject(g.error);
                };
            });
        });
    }

    function idbDeleteOutbox(localId) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction('outbox', 'readwrite');
                tx.objectStore('outbox').delete(localId);
                tx.oncomplete = function () {
                    resolve();
                };
                tx.onerror = function () {
                    reject(tx.error);
                };
            });
        });
    }

    function newLocalId() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'local-' + String(Date.now()) + '-' + String(Math.random()).slice(2, 11);
    }

    function newClientRequestId() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return newLocalId();
    }

    function isLikelyNetworkFailure(err) {
        if (!navigator.onLine) {
            return true;
        }
        if (!err || !err.response) {
            return true;
        }
        return false;
    }

    function buildVentaPreviewFromPayload(payload, productos, clientes) {
        var cliente = null;
        if (payload.cliente_id) {
            cliente = (clientes || []).find(function (c) {
                return String(c.id) === String(payload.cliente_id);
            });
        }
        var total = 0;
        var items = (payload.items || []).map(function (iv) {
            var p = (productos || []).find(function (x) {
                return String(x.id) === String(iv.producto_id);
            });
            var pu = p ? parseFloat(p.precio_venta || 0) || 0 : 0;
            var cant = parseFloat(iv.cantidad) || 0;
            var sub = pu * cant;
            total += sub;
            return {
                producto: p || { codigo: '-', nombre: 'Producto #' + iv.producto_id },
                cantidad: cant,
                precio_unitario: pu,
                subtotal: sub,
            };
        });
        var descuento = parseFloat(payload.descuento || 0) || 0;
        var totalFinal = total - descuento;
        return {
            id: null,
            offline_preview: true,
            fecha: new Date().toISOString(),
            cliente: cliente,
            total: total,
            descuento: descuento,
            total_final: totalFinal,
            tipo_pago: payload.tipo_pago || 'efectivo',
            monto_tarjeta: payload.monto_tarjeta,
            monto_efectivo: payload.monto_efectivo,
            monto_transferencia: payload.monto_transferencia,
            cuotas: payload.cuotas,
            items: items,
            numero_factura: null,
        };
    }

    function onOnline(callback) {
        function handler() {
            try {
                callback();
            } catch (e) {}
        }
        window.addEventListener('online', handler);
        return function () {
            window.removeEventListener('online', handler);
        };
    }

    function saveCatalog(productos, clientes, cajasAbiertas) {
        var row = {
            savedAt: Date.now(),
            productos: productos || [],
            clientes: clientes || [],
            cajasAbiertas: cajasAbiertas || [],
        };
        return idbPut('snapshots', SNAPSHOT_KEYS.catalog, row).catch(function () {});
    }

    function saveVentasList(ventas) {
        var row = {
            savedAt: Date.now(),
            ventas: ventas || [],
        };
        return idbPut('snapshots', SNAPSHOT_KEYS.ventas, row).catch(function () {});
    }

    function loadCatalog() {
        return idbGet('snapshots', SNAPSHOT_KEYS.catalog).then(function (r) {
            return r || null;
        });
    }

    function loadVentasList() {
        return idbGet('snapshots', SNAPSHOT_KEYS.ventas).then(function (r) {
            return r || null;
        });
    }

    function enqueuePending(row) {
        var localId = newLocalId();
        var rec = {
            localId: localId,
            clientRequestId: row.clientRequestId,
            payload: row.payload,
            preview: row.preview,
            createdAt: Date.now(),
            lastError: null,
        };
        return idbPut('outbox', null, rec).then(function () {
            return rec;
        });
    }

    function listOutbox() {
        return idbGetAllOutbox();
    }

    function removeOutbox(localId) {
        return idbDeleteOutbox(localId);
    }

    /**
     * @param {function} postVenta - (payload) => Promise<axiosResponse>
     * @param {function} onProgress - optional ({ done, total, error }) =>
     */
    function syncOutbox(postVenta, onProgress) {
        return listOutbox().then(function (rows) {
            if (!rows.length) {
                return { synced: 0, failed: 0 };
            }
            var synced = 0;
            var failed = 0;
            var chain = Promise.resolve();
            rows.forEach(function (row) {
                chain = chain.then(function () {
                    var body = Object.assign({}, row.payload, {
                        client_request_id: row.clientRequestId,
                    });
                    return postVenta(body)
                        .then(function () {
                            synced++;
                            return removeOutbox(row.localId);
                        })
                        .catch(function (err) {
                            failed++;
                            var msg =
                                (err && err.response && err.response.data && err.response.data.message) ||
                                (err && err.message) ||
                                'Error al sincronizar';
                            row.lastError = String(msg);
                            return idbPut('outbox', null, row);
                        })
                        .then(function () {
                            if (onProgress) {
                                onProgress({
                                    done: synced + failed,
                                    total: rows.length,
                                    lastError: row.lastError,
                                });
                            }
                        });
                });
            });
            return chain.then(function () {
                return { synced: synced, failed: failed };
            });
        });
    }

    window.VentasOffline = {
        SNAPSHOT_KEYS: SNAPSHOT_KEYS,
        isLikelyNetworkFailure: isLikelyNetworkFailure,
        newClientRequestId: newClientRequestId,
        saveCatalog: saveCatalog,
        saveVentasList: saveVentasList,
        loadCatalog: loadCatalog,
        loadVentasList: loadVentasList,
        enqueuePending: enqueuePending,
        listOutbox: listOutbox,
        removeOutbox: removeOutbox,
        syncOutbox: syncOutbox,
        onOnline: onOnline,
        buildVentaPreviewFromPayload: buildVentaPreviewFromPayload,
    };
})();
