import React from 'react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('Error capturado por ErrorBoundary:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="bg-white p-6 rounded-lg shadow m-4">
                    <h2 className="text-xl font-bold text-red-600 mb-4">Algo salió mal</h2>
                    <p className="text-gray-600 mb-4">
                        Hubo un error al cargar este componente. Por favor, recarga la página.
                    </p>
                    <button
                        onClick={() => window.location.reload()}
                        className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Recargar Página
                    </button>
                    {this.state.error && (
                        <details className="mt-4">
                            <summary className="cursor-pointer text-sm text-gray-500">
                                Detalles del error
                            </summary>
                            <pre className="mt-2 text-xs bg-gray-100 p-2 rounded overflow-auto">
                                {this.state.error.toString()}
                            </pre>
                        </details>
                    )}
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
