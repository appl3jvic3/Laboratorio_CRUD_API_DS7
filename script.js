// ============================================
// CONFIGURACIÓN INICIAL
// ============================================

const API_URL = 'registrar.php';
const LOGIN_URL = 'login.php';
let token = localStorage.getItem('jwt_token') || '';

console.log('🔑 Token inicial:', token ? '✅ Existe' : '❌ No existe');

// ============================================
// 1. FUNCIONES DE LOGIN
// ============================================

document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value.trim();

    if (!username || !password) {
        Swal.fire('Error', 'Ingresa usuario y contraseña', 'error');
        return;
    }

    try {
        console.log('📡 Intentando login...');
        
        const response = await fetch(LOGIN_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json();
        console.log('📡 Respuesta login:', data);

        if (response.ok && data.success) {
            token = data.token;
            localStorage.setItem('jwt_token', token);
            console.log('✅ Token guardado:', token.substring(0, 30) + '...');
            
            Swal.fire('Éxito', 'Login exitoso', 'success');
            mostrarCRUD();
            listarProductos();
        } else {
            Swal.fire('Error', data.error || data.message || 'Credenciales inválidas', 'error');
        }
    } catch (error) {
        console.error('❌ Error en login:', error);
        Swal.fire('Error', 'Error de conexión al servidor', 'error');
    }
});

// ============================================
// 2. FUNCIONES DE INTERFAZ
// ============================================

function mostrarCRUD() {
    document.getElementById('loginSection').style.display = 'none';
    document.getElementById('crudSection').style.display = 'block';
}

function mostrarLogin() {
    document.getElementById('loginSection').style.display = 'block';
    document.getElementById('crudSection').style.display = 'none';
    localStorage.removeItem('jwt_token');
    token = '';
}

// ============================================
// 3. FETCH CON TOKEN
// ============================================

async function fetchWithToken(url, options = {}) {
    if (!token) {
        token = localStorage.getItem('jwt_token') || '';
    }
    
    const defaultOptions = {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/x-www-form-urlencoded',
        },
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    if (options.headers) {
        mergedOptions.headers = { ...defaultOptions.headers, ...options.headers };
    }
    
    return fetch(url, mergedOptions);
}

// ============================================
// 4. VALIDACIÓN DE CARACTERES PELIGROSOS
// ============================================

function validarTextoSeguro(texto) {
    const patronesPeligrosos = [
        /<script/i, /<[^>]*>/, /javascript:/i, /on\w+=/i,
        /alert\s*\(/i, /eval\s*\(/i, /document\./i, /window\./i,
        /\bSELECT\b/i, /\bINSERT\b/i, /\bUPDATE\b/i, /\bDELETE\b/i,
        /\bDROP\b/i, /\bUNION\b/i,
    ];
    
    for (let patron of patronesPeligrosos) {
        if (patron.test(texto)) {
            return false;
        }
    }
    return true;
}

// ============================================
// 5. LISTAR PRODUCTOS
// ============================================

async function listarProductos() {
    const tbody = document.getElementById('tablaProductos');
    tbody.innerHTML = '';
    
    try {
        token = localStorage.getItem('jwt_token') || '';
        if (!token) {
            mostrarLogin();
            return;
        }
        
        const formData = new URLSearchParams();
        formData.append('accion', 'Listar');
        
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });

        if (response.status === 401) {
            Swal.fire('Sesión expirada', 'Inicia sesión nuevamente', 'warning');
            mostrarLogin();
            return;
        }

        const result = await response.json();

        if (result.success && result.data) {
            renderizarTabla(result.data);
            document.getElementById('buscarInput').value = '';
            document.getElementById('btnLimpiarBusqueda').classList.add('d-none');
        } else {
            throw new Error(result.message || 'Error al cargar productos');
        }
    } catch (error) {
        console.error('❌ Error en listarProductos:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    Error: ${error.message}
                </td>
            </tr>
        `;
        Swal.fire('Error', 'No se pudieron cargar los productos', 'error');
    }
}

// ============================================
// 6. RENDERIZAR TABLA
// ============================================

function renderizarTabla(productos, mensaje = '') {
    const tbody = document.getElementById('tablaProductos');
    const contador = document.getElementById('contadorProductos');

    if (!productos || productos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">${mensaje || 'No hay productos registrados'}</td>
            </tr>
        `;
        contador.textContent = '0 productos';
        const headerContador = document.getElementById('contadorProductosHeader');
        if (headerContador) headerContador.textContent = '0 productos';
        return;
    }

    tbody.innerHTML = '';
    productos.forEach(p => {
        tbody.innerHTML += `
            <tr>
                <td>${p.id}</td>
                <td><span class="badge bg-secondary">${p.codigo}</span></td>
                <td>${p.producto}</td>
                <td>$${parseFloat(p.precio).toFixed(2)}</td>
                <td>${p.cantidad}</td>
                <td>
                    <button class="btn btn-sm btn-warning editar" data-id="${p.id}">✏️ Editar</button>
                    <button class="btn btn-sm btn-danger eliminar" data-id="${p.id}">🗑️ Eliminar</button>
                </td>
            </tr>
        `;
    });

    contador.textContent = `${productos.length} productos`;
    const headerContador = document.getElementById('contadorProductosHeader');
    if (headerContador) headerContador.textContent = `${productos.length} productos`;

    document.querySelectorAll('.editar').forEach(btn => {
        btn.addEventListener('click', () => cargarParaEditar(btn.dataset.id));
    });
    document.querySelectorAll('.eliminar').forEach(btn => {
        btn.addEventListener('click', () => eliminarProducto(btn.dataset.id));
    });
}

// ============================================
// 7. GUARDAR / MODIFICAR (CON SWITCH)
// ============================================

document.getElementById('productForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('productId').value;
    const codigo = document.getElementById('codigo').value.trim();
    const producto = document.getElementById('producto').value.trim();
    const precio = document.getElementById('precio').value;
    const cantidad = document.getElementById('cantidad').value;

    if (!codigo || !producto || !precio || !cantidad) {
        Swal.fire('Error', 'Todos los campos son obligatorios', 'error');
        return;
    }

    if (!validarTextoSeguro(producto) || !validarTextoSeguro(codigo)) {
        Swal.fire('Error', 'El producto o código contiene caracteres no permitidos', 'error');
        return;
    }

    if (isNaN(precio) || parseFloat(precio) <= 0) {
        Swal.fire('Error', 'El precio debe ser un número mayor a 0', 'error');
        return;
    }

    if (isNaN(cantidad) || parseInt(cantidad) <= 0) {
        Swal.fire('Error', 'La cantidad debe ser un número mayor a 0', 'error');
        return;
    }

    let accion = id ? 'Modificar' : 'Guardar';

    switch (accion) {
        case 'Guardar':
            await procesarProducto('Guardar', null, { codigo, producto, precio, cantidad });
            break;
        case 'Modificar':
            if (!id) {
                Swal.fire('Error', 'ID de producto no válido', 'error');
                return;
            }
            await procesarProducto('Modificar', id, { codigo, producto, precio, cantidad });
            break;
        default:
            Swal.fire('Error', 'Acción no válida', 'error');
            break;
    }
});

async function procesarProducto(accion, id, datos) {
    const formData = new URLSearchParams();
    formData.append('accion', accion);
    formData.append('codigo', datos.codigo);
    formData.append('producto', datos.producto);
    formData.append('precio', datos.precio);
    formData.append('cantidad', datos.cantidad);
    if (id) formData.append('id', id);

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });

        if (response.status === 401) {
            Swal.fire('Sesión expirada', 'Inicia sesión nuevamente', 'warning');
            mostrarLogin();
            return;
        }

        const result = await response.json();

        switch (result.success) {
            case true:
                Swal.fire('Éxito', result.message, 'success');
                resetForm();
                listarProductos();
                break;
            case false:
                Swal.fire('Error', result.message || 'Ocurrió un error', 'error');
                break;
            default:
                Swal.fire('Error', 'Respuesta inesperada', 'error');
                break;
        }
    } catch (error) {
        console.error('❌ Error:', error);
        Swal.fire('Error', 'Error de conexión con el servidor', 'error');
    }
}

// ============================================
// 8. CARGAR PARA EDITAR
// ============================================

async function cargarParaEditar(id) {
    try {
        const formData = new URLSearchParams();
        formData.append('accion', 'Buscar');
        formData.append('id', id);

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });

        if (response.status === 401) {
            Swal.fire('Sesión expirada', 'Inicia sesión nuevamente', 'warning');
            mostrarLogin();
            return;
        }

        const result = await response.json();

        if (result.success && result.data) {
            const producto = result.data;
            document.getElementById('productId').value = producto.id;
            document.getElementById('codigo').value = producto.codigo;
            document.getElementById('producto').value = producto.producto;
            document.getElementById('precio').value = producto.precio;
            document.getElementById('cantidad').value = producto.cantidad;
            document.getElementById('btnGuardar').textContent = 'Actualizar';
            document.getElementById('btnCancelar').classList.remove('d-none');
            document.getElementById('btnGuardar').className = 'btn btn-success w-100';
        } else {
            Swal.fire('Error', result.message || 'Producto no encontrado', 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        Swal.fire('Error', 'No se pudo cargar el producto', 'error');
    }
}

// ============================================
// 9. RESETEAR FORMULARIO
// ============================================

document.getElementById('btnCancelar').addEventListener('click', resetForm);

function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').classList.add('d-none');
    document.getElementById('btnGuardar').className = 'btn btn-primary w-100';
}

// ============================================
// 10. ELIMINAR PRODUCTO
// ============================================

async function eliminarProducto(id) {
    const confirm = await Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirm.isConfirmed) return;

    try {
        const formData = new URLSearchParams();
        formData.append('accion', 'Eliminar');
        formData.append('id', id);

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });

        if (response.status === 401) {
            Swal.fire('Sesión expirada', 'Inicia sesión nuevamente', 'warning');
            mostrarLogin();
            return;
        }

        const result = await response.json();

        if (result.success) {
            Swal.fire('Eliminado', result.message, 'success');
            listarProductos();
        } else {
            Swal.fire('Error', result.message || 'No se pudo eliminar', 'error');
        }
    } catch (error) {
        console.error('❌ Error:', error);
        Swal.fire('Error', 'Error de conexión con el servidor', 'error');
    }
}

// ============================================
// 11. FUNCIONES DE BÚSQUEDA
// ============================================

/**
 * Busca productos por ID, nombre o código
 * Muestra mensajes de retroalimentación específicos
 */
async function buscarProductos() {
    const termino = document.getElementById('buscarInput').value.trim();
    console.log('🔍 Buscando término:', termino);
    const tbody = document.getElementById('tablaProductos');
    
    // Si el campo está vacío, mostrar mensaje y salir
    if (!termino) {
        Swal.fire('Información', 'Escribe un término de búsqueda (ID, código o nombre)', 'info');
        return;
    }

    try {
        const formData = new URLSearchParams();
        formData.append('accion', 'BuscarProductos');
        formData.append('termino', termino);

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });

        if (response.status === 401) {
            Swal.fire('Sesión expirada', 'Inicia sesión nuevamente', 'warning');
            mostrarLogin();
            return;
        }

        const result = await response.json();
        console.log('📡 Resultado búsqueda:', result);

        // RENDERIZAR RESULTADOS
        if (result.success && result.data && result.data.length > 0) {
            // PRODUCTOS ENCONTRADOS
            renderizarTabla(result.data);
            document.getElementById('contadorProductos').textContent = result.message;
            document.getElementById('btnLimpiarBusqueda').classList.remove('d-none');
            
            // Mostrar mensaje de éxito con SweetAlert
            Swal.fire({
                icon: 'success',
                title: '✅ Productos encontrados',
                text: result.message,
                timer: 2000,
                showConfirmButton: false
            });
        } else if (result.success && result.data && result.data.length === 0) {
            // NO SE ENCONTRARON PRODUCTOS
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="alert alert-warning m-0">
                            <strong>🔍 No se encontraron productos</strong><br>
                            No hay coincidencias para "<strong>${termino}</strong>"
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('contadorProductos').textContent = '0 productos';
            document.getElementById('btnLimpiarBusqueda').classList.remove('d-none');
            
            // Mostrar mensaje de no encontrado con SweetAlert
            Swal.fire({
                icon: 'info',
                title: '🔍 Producto no encontrado',
                text: `No se encontraron productos que coincidan con "${termino}"`,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            // ERROR EN LA BÚSQUEDA
            throw new Error(result.message || 'Error al buscar productos');
        }
    } catch (error) {
        console.error('❌ Error en buscarProductos:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    <strong>❌ Error:</strong> ${error.message}
                </td>
            </tr>
        `;
        Swal.fire('Error', 'No se pudo completar la búsqueda: ' + error.message, 'error');
    }
}

/**
 * Limpia la búsqueda y recarga todos los productos
 */
function limpiarBusqueda() {
    document.getElementById('buscarInput').value = '';
    document.getElementById('btnLimpiarBusqueda').classList.add('d-none');
    document.getElementById('contadorProductos').textContent = 'Cargando...';
    listarProductos();
    Swal.fire({
        icon: 'info',
        title: '🔄 Lista completa',
        text: 'Mostrando todos los productos',
        timer: 1500,
        showConfirmButton: false
    });
}

// ============================================
// 12. CERRAR SESIÓN
// ============================================

document.getElementById('btnLogout').addEventListener('click', () => {
    Swal.fire({
        title: '¿Cerrar sesión?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            mostrarLogin();
        }
    });
});

// ============================================
// 13. CARGA INICIAL Y EVENTOS
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Inicialización
    token = localStorage.getItem('jwt_token') || '';
    
    if (token) {
        console.log('✅ Token encontrado en localStorage');
        mostrarCRUD();
        listarProductos();
    } else {
        console.log('❌ No hay token, mostrando login');
        mostrarLogin();
    }

    // === EVENTOS DE BÚSQUEDA ===
    const btnBuscar = document.getElementById('btnBuscar');
    const buscarInput = document.getElementById('buscarInput');
    const btnLimpiar = document.getElementById('btnLimpiarBusqueda');
    const btnRecargar = document.getElementById('btnRecargar');

    if (btnBuscar) {
        btnBuscar.addEventListener('click', buscarProductos);
        console.log('✅ Evento de búsqueda asignado');
    } else {
        console.error('❌ Botón de búsqueda no encontrado');
    }

    if (buscarInput) {
        buscarInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarProductos();
            }
        });
        console.log('✅ Evento Enter asignado');
    }

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', limpiarBusqueda);
        console.log('✅ Evento limpiar asignado');
    }

    if (btnRecargar) {
        btnRecargar.addEventListener('click', () => {
            document.getElementById('buscarInput').value = '';
            document.getElementById('btnLimpiarBusqueda').classList.add('d-none');
            listarProductos();
        });
        console.log('✅ Evento recargar asignado');
    }
});