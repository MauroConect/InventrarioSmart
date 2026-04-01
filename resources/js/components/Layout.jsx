import React, { useState, useEffect, useMemo } from 'react';
import { Outlet, Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { canAccess } from '../utils/permissions';

const ALL_MENU_ITEMS = [
    { path: '/', label: 'Dashboard', icon: '📊', permission: 'dashboard.view' },
    { path: '/categorias', label: 'Sabores', icon: '📁', permission: 'categorias.view' },
    { path: '/productos', label: 'Sabores y productos', icon: '📦', permission: 'productos.view' },
    { path: '/aumento-masivo-precios', label: 'Aumento Masivo', icon: '📈', permission: 'productos.manage' },
    { path: '/proveedores', label: 'Proveedores', icon: '🚚', permission: 'proveedores.view' },
    { path: '/clientes', label: 'Clientes', icon: '👥', permission: 'clientes.view' },
    { path: '/cajas', label: 'Cajas', icon: '💰', permission: null },
    { path: '/cuentas-corrientes', label: 'Cuentas Corrientes', icon: '💳', permission: 'cuentas_corrientes.view' },
    { path: '/deudas-clientes', label: 'Deudas', icon: '📋', permission: 'deudas.view' },
    { path: '/movimientos-stock', label: 'Stock', icon: '📊', permission: 'stock.view' },
    { path: '/ventas', label: 'Venta helados', icon: '🛒', permission: 'ventas.view' },
    { path: '/cheques', label: 'Cheques', icon: '💵', permission: 'cheques.view' },
];

export default function Layout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    // En desktop, el sidebar está abierto por defecto
    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth >= 1024) {
                setSidebarOpen(true);
            } else {
                setSidebarOpen(false);
            }
        };
        
        handleResize();
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    const menuItems = useMemo(
        () =>
            ALL_MENU_ITEMS.filter(
                (item) => !item.permission || canAccess(user, item.permission)
            ),
        [user]
    );

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-blue-600 text-white shadow-lg">
                <div className="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
                    <div className="flex justify-between items-center h-16">
                        <div className="flex items-center flex-1 min-w-0">
                            <button
                                onClick={() => setSidebarOpen(!sidebarOpen)}
                                className="p-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
                                aria-label="Toggle sidebar"
                            >
                                <span className="text-xl">☰</span>
                            </button>
                            <h1 className="ml-2 sm:ml-4 text-base sm:text-xl font-bold truncate">Danielles</h1>
                        </div>
                        <div className="flex items-center gap-2 sm:gap-4">
                            <span className="hidden sm:inline text-sm sm:text-base truncate max-w-[150px] sm:max-w-none">{user?.name}</span>
                            <button
                                onClick={handleLogout}
                                className="px-2 sm:px-4 py-2 bg-blue-700 rounded hover:bg-blue-800 text-sm sm:text-base whitespace-nowrap"
                            >
                                Salir
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <div className="flex relative">
                {/* Overlay para móviles */}
                {sidebarOpen && (
                    <div
                        className="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
                        onClick={() => setSidebarOpen(false)}
                    />
                )}

                {/* Sidebar */}
                <aside className={`
                    fixed lg:static
                    top-0 left-0
                    w-64 h-full
                    bg-white shadow-lg
                    z-50 lg:z-auto
                    transform transition-transform duration-300 ease-in-out
                    ${sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
                    lg:translate-x-0
                `}>
                    <div className="flex items-center justify-between p-4 border-b lg:hidden">
                        <h2 className="text-lg font-bold text-gray-800">Menú</h2>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="p-2 rounded-md hover:bg-gray-100 text-gray-600"
                            aria-label="Cerrar menú"
                        >
                            ✕
                        </button>
                    </div>
                    <nav className="mt-5 px-2 overflow-y-auto h-[calc(100vh-5rem)] lg:h-auto">
                        {menuItems.map((item) => (
                            <Link
                                key={item.path}
                                to={item.path}
                                onClick={() => {
                                    // Cerrar sidebar en móviles al hacer clic
                                    if (window.innerWidth < 1024) {
                                        setSidebarOpen(false);
                                    }
                                }}
                                className="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 mb-1 transition-colors"
                            >
                                <span className="mr-3 text-xl">{item.icon}</span>
                                <span className="text-sm sm:text-base">{item.label}</span>
                            </Link>
                        ))}
                    </nav>
                </aside>

                {/* Main content */}
                <main className="flex-1 w-full lg:w-auto min-w-0">
                    <div className="p-3 sm:p-4 lg:p-6">
                        <Outlet />
                    </div>
                </main>
            </div>
        </div>
    );
}
