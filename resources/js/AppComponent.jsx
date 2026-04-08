import React, { useEffect } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import { canAccess } from './utils/permissions';
import Layout from './components/Layout';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Categorias from './pages/Categorias';
import Productos from './pages/Productos';
import Proveedores from './pages/Proveedores';
import Clientes from './pages/Clientes';
import Cajas from './pages/Cajas';
import CuentasCorrientes from './pages/CuentasCorrientes';
import DeudasClientes from './pages/DeudasClientes';
import MovimientosStock from './pages/MovimientosStock';
import Ventas from './pages/Ventas';
import AumentoMasivoPrecios from './pages/AumentoMasivoPrecios';
import Cheques from './pages/Cheques';
import VentaDetalle from './pages/VentaDetalle';
import Usuarios from './pages/Usuarios';

/** Panel Blade de caja; recarga completa para salir del SPA. */
function FullPageBlade({ href }) {
    useEffect(() => {
        window.location.replace(href);
    }, [href]);
    return <div className="p-6 text-center text-gray-600">Abriendo punto de caja…</div>;
}

function HomeRoute() {
    const { user } = useAuth();
    if (canAccess(user, 'dashboard.view')) {
        return <Dashboard />;
    }
    if (canAccess(user, 'ventas.view')) {
        return <Navigate to="/ventas" replace />;
    }
    if (user) {
        return <Navigate to="/cajas" replace />;
    }
    return (
        <div className="p-6 text-center text-gray-600">
            No tenés permisos para acceder al panel.
        </div>
    );
}

function AppRoutes() {
    const { user, loading } = useAuth();

    if (loading) {
        return <div className="flex items-center justify-center min-h-screen">Cargando...</div>;
    }

    return (
        <Routes>
            <Route path="/login" element={!user ? <Login /> : <Navigate to="/" />} />
            <Route
                path="/*"
                element={user ? <Layout /> : <Navigate to="/login" />}
            >
                <Route index element={<HomeRoute />} />
                <Route path="categorias" element={<Categorias />} />
                <Route path="productos" element={<Productos />} />
                <Route path="proveedores" element={<Proveedores />} />
                <Route path="clientes" element={<Clientes />} />
                <Route path="cajas" element={<Cajas />} />
                <Route path="cajas/punto" element={<FullPageBlade href="/cajas/punto" />} />
                <Route path="caja" element={<FullPageBlade href="/cajas/punto" />} />
                <Route path="cajas/mostrador" element={<FullPageBlade href="/cajas/punto" />} />
                <Route path="punto-caja" element={<FullPageBlade href="/cajas/punto" />} />
                <Route path="mi-caja" element={<FullPageBlade href="/cajas/punto" />} />
                <Route path="mcaja" element={<FullPageBlade href="/cajas/punto" />} />
                <Route path="cuentas-corrientes" element={<CuentasCorrientes />} />
                <Route path="deudas-clientes" element={<DeudasClientes />} />
                <Route path="movimientos-stock" element={<MovimientosStock />} />
                <Route path="ventas" element={<Ventas />} />
                <Route path="ventas/:id" element={<VentaDetalle />} />
                <Route path="aumento-masivo-precios" element={<AumentoMasivoPrecios />} />
                <Route path="cheques" element={<Cheques />} />
                <Route path="usuarios" element={<Usuarios />} />
            </Route>
        </Routes>
    );
}

function App() {
    return (
        <AuthProvider>
            <AppRoutes />
        </AuthProvider>
    );
}

export default App;
