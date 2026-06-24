// ============================================
// SISTEMA DE GESTIÓN DE INVENTARIO
// ============================================

const API_URLS = {
  categorias:   'api_categorias.php',
  proveedores:  'api_proveedores.php',
  productos:    'api_productos.php',
  cotizaciones: 'api_cotizaciones.php'
};

// ============================================
// SERVICIOS
// ============================================

const ApiService = {
  _fetch: async (url, options) => {
    try {
      if (options && options.method === 'POST' && !options.headers) {
        options.headers = { 'Content-Type': 'application/json' };
      }
      const response = await fetch(url, options);
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.error || 'Error en la solicitud');
      }
      return data;
    } catch (error) {
      console.error('Error en fetch:', error);
      throw error;
    }
  }
};

const CategoriaService = {
  getAll:  () => ApiService._fetch(`${API_URLS.categorias}?accion=listar`, { method: 'GET' }),
  create:  (nombre) => ApiService._fetch(`${API_URLS.categorias}?accion=crear`, { method: 'POST', body: JSON.stringify({ nombre: nombre.trim() }) }),
  update:  (id, nuevoNombre) => ApiService._fetch(`${API_URLS.categorias}?accion=actualizar`, { method: 'POST', body: JSON.stringify({ id, nombre: nuevoNombre.trim() }) }),
  delete:  (id) => ApiService._fetch(`${API_URLS.categorias}?accion=eliminar`, { method: 'POST', body: JSON.stringify({ id }) }),
};

const ProveedorService = {
  getAll:  () => ApiService._fetch(`${API_URLS.proveedores}?accion=listar`, { method: 'GET' }),
  create:  (nombre, telefono, correo) => ApiService._fetch(`${API_URLS.proveedores}?accion=crear`, { method: 'POST', body: JSON.stringify({ nombre, telefono, correo }) }),
  update:  (id, nombre, telefono, correo) => ApiService._fetch(`${API_URLS.proveedores}?accion=actualizar`, { method: 'POST', body: JSON.stringify({ id, nombre, telefono, correo }) }),
  delete:  (id) => ApiService._fetch(`${API_URLS.proveedores}?accion=eliminar`, { method: 'POST', body: JSON.stringify({ id }) }),
};

const ProductoService = {
  getAll:           (filtros = {}) => ApiService._fetch(`${API_URLS.productos}?${new URLSearchParams({ accion: 'listar', ...filtros })}`, { method: 'GET' }),
  create:           (producto) => ApiService._fetch(`${API_URLS.productos}?accion=crear`, { method: 'POST', body: JSON.stringify(producto) }),
  update:           (producto) => ApiService._fetch(`${API_URLS.productos}?accion=actualizar`, { method: 'POST', body: JSON.stringify(producto) }),
  updateExistencia: (id, existencia) => ApiService._fetch(`${API_URLS.productos}?accion=actualizarExistencia`, { method: 'POST', body: JSON.stringify({ id, existencia }) }),
  delete:           (id) => ApiService._fetch(`${API_URLS.productos}?accion=eliminar`, { method: 'POST', body: JSON.stringify({ id }) }),
  autocompletar:    (termino) => ApiService._fetch(`${API_URLS.productos}?${new URLSearchParams({ accion: 'autocompletar', termino })}`, { method: 'GET' }),
};

const CotizacionService = {
  create:  (data) => ApiService._fetch(`${API_URLS.cotizaciones}?accion=crear`, { method: 'POST', body: JSON.stringify(data) }),
  getAll:  (filtros = {}) => ApiService._fetch(`${API_URLS.cotizaciones}?${new URLSearchParams({ accion: 'listar', ...filtros })}`, { method: 'GET' }),
  obtener: (id) => ApiService._fetch(`${API_URLS.cotizaciones}?accion=obtener&id=${id}`, { method: 'GET' }),
  anular:  (id) => ApiService._fetch(`${API_URLS.cotizaciones}?accion=anular`, { method: 'POST', body: JSON.stringify({ id }) }),
  
  // ✅ Pega esta nueva función aquí adentro:
  obtenerPDFBlob: async (id) => {
    try {
      const response = await fetch(`${API_URLS.cotizaciones}?accion=generar_pdf&id=${id}`, { method: 'GET' });
      if (!response.ok) throw new Error('No se pudo generar el archivo PDF en el servidor.');
      return await response.blob();
    } catch (error) {
      console.error('Error descargando el PDF binario:', error);
      throw error;
    }
  }
};

// ============================================
// UI - CATEGORÍAS
// ============================================
const CategoriaUI = {
  init: () => {
    document.getElementById('categoria-form').addEventListener('submit', CategoriaUI.handleSubmit);
    document.getElementById('categoria-cancel-btn').addEventListener('click', CategoriaUI.cancelEdit);
    CategoriaUI.renderTable();
  },
  handleSubmit: async (e) => {
    e.preventDefault();
    const idInput     = document.getElementById('categoria-id');
    const nombreInput = document.getElementById('categoria-nombre');
    const errorSpan   = document.getElementById('categoria-nombre-error');
    errorSpan.textContent = '';
    nombreInput.classList.remove('error');
    try {
      if (idInput.value) {
        await CategoriaService.update(parseInt(idInput.value), nombreInput.value);
      } else {
        await CategoriaService.create(nombreInput.value);
      }
      CategoriaUI.cancelEdit();
      await CategoriaUI.renderTable();
      await ProductoUI.updateCategoriaSelects();
    } catch (error) {
      errorSpan.textContent = error.message;
      nombreInput.classList.add('error');
    }
  },
  cancelEdit: () => {
    document.getElementById('categoria-form').reset();
    document.getElementById('categoria-id').value = '';
    document.getElementById('categoria-form-title').textContent = 'Registrar Nueva Categoría';
    document.getElementById('categoria-submit-btn').textContent = 'Guardar';
    document.getElementById('categoria-cancel-btn').style.display = 'none';
    document.getElementById('categoria-nombre-error').textContent = '';
    document.getElementById('categoria-nombre').classList.remove('error');
  },
  edit: (id, nombre) => {
    document.getElementById('categoria-id').value = id;
    document.getElementById('categoria-nombre').value = nombre;
    document.getElementById('categoria-form-title').textContent = 'Editar Categoría';
    document.getElementById('categoria-submit-btn').textContent = 'Actualizar';
    document.getElementById('categoria-cancel-btn').style.display = 'inline-block';
    window.scrollTo(0, 0);
  },
  delete: (id, nombre) => {
    Modal.confirm(`¿Está seguro de eliminar la categoría "${nombre}"?`, async () => {
      try {
        await CategoriaService.delete(id);
        await CategoriaUI.renderTable();
        await ProductoUI.updateCategoriaSelects();
      } catch (error) { alert(error.message); }
    });
  },
  renderTable: async () => {
    const tbody = document.getElementById('categorias-tbody');
    tbody.innerHTML = '<tr><td colspan="2" style="text-align:center">Cargando...</td></tr>';
    try {
      const categorias = await CategoriaService.getAll();
      tbody.innerHTML = '';
      if (categorias.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" style="text-align:center">No hay categorías registradas</td></tr>';
        return;
      }
      categorias.forEach(c => {
        const tr = document.createElement('tr');
        const nombreHtml = c.nombre.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        tr.innerHTML = `
          <td>${c.nombre}</td>
          <td>
            <button class="btn-edit"   onclick="CategoriaUI.edit(${c.id}, '${nombreHtml}')">Editar</button>
            <button class="btn-delete" onclick="CategoriaUI.delete(${c.id}, '${nombreHtml}')">Eliminar</button>
          </td>`;
        tbody.appendChild(tr);
      });
    } catch (error) {
      tbody.innerHTML = `<tr><td colspan="2" style="text-align:center;color:red">${error.message}</td></tr>`;
    }
  }
};

// ============================================
// UI - PROVEEDORES
// ============================================
const ProveedorUI = {
  init: () => {
    document.getElementById('proveedor-form').addEventListener('submit', ProveedorUI.handleSubmit);
    document.getElementById('proveedor-cancel-btn').addEventListener('click', ProveedorUI.cancelEdit);
    ProveedorUI.renderTable();
  },
  handleSubmit: async (e) => {
    e.preventDefault();
    const idInput     = document.getElementById('proveedor-id');
    const nombreInput = document.getElementById('proveedor-nombre');
    const errorSpan   = document.getElementById('proveedor-nombre-error');
    errorSpan.textContent = '';
    nombreInput.classList.remove('error');
    try {
      const nombre   = nombreInput.value;
      const telefono = document.getElementById('proveedor-telefono').value;
      const correo   = document.getElementById('proveedor-correo').value;
      if (idInput.value) {
        await ProveedorService.update(parseInt(idInput.value), nombre, telefono, correo);
      } else {
        await ProveedorService.create(nombre, telefono, correo);
      }
      ProveedorUI.cancelEdit();
      await ProveedorUI.renderTable();
      await ProductoUI.updateProveedorSelects();
    } catch (error) {
      errorSpan.textContent = error.message;
      nombreInput.classList.add('error');
    }
  },
  cancelEdit: () => {
    document.getElementById('proveedor-form').reset();
    document.getElementById('proveedor-id').value = '';
    document.getElementById('proveedor-form-title').textContent = 'Registrar Nuevo Proveedor';
    document.getElementById('proveedor-submit-btn').textContent = 'Guardar';
    document.getElementById('proveedor-cancel-btn').style.display = 'none';
    document.getElementById('proveedor-nombre-error').textContent = '';
    document.getElementById('proveedor-nombre').classList.remove('error');
  },
  edit: (id, nombre, telefono, correo) => {
    document.getElementById('proveedor-id').value      = id;
    document.getElementById('proveedor-nombre').value  = nombre;
    document.getElementById('proveedor-telefono').value = telefono;
    document.getElementById('proveedor-correo').value  = correo;
    document.getElementById('proveedor-form-title').textContent  = 'Editar Proveedor';
    document.getElementById('proveedor-submit-btn').textContent  = 'Actualizar';
    document.getElementById('proveedor-cancel-btn').style.display = 'inline-block';
    window.scrollTo(0, 0);
  },
  delete: (id, nombre) => {
    Modal.confirm(`¿Está seguro de eliminar el proveedor "${nombre}"?`, async () => {
      try {
        await ProveedorService.delete(id);
        await ProveedorUI.renderTable();
        await ProductoUI.updateProveedorSelects();
      } catch (error) { alert(error.message); }
    });
  },
  renderTable: async () => {
    const tbody = document.getElementById('proveedores-tbody');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center">Cargando...</td></tr>';
    try {
      const proveedores = await ProveedorService.getAll();
      tbody.innerHTML = '';
      if (proveedores.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center">No hay proveedores registrados</td></tr>';
        return;
      }
      proveedores.forEach(p => {
        const tr = document.createElement('tr');
        const nH = p.nombre.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        const tH = p.telefono.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        const cH = p.correo.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        tr.innerHTML = `
          <td>${p.nombre}</td><td>${p.telefono}</td><td>${p.correo}</td>
          <td>
            <button class="btn-edit"   onclick="ProveedorUI.edit(${p.id},'${nH}','${tH}','${cH}')">Editar</button>
            <button class="btn-delete" onclick="ProveedorUI.delete(${p.id},'${nH}')">Eliminar</button>
          </td>`;
        tbody.appendChild(tr);
      });
    } catch (error) {
      tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:red">${error.message}</td></tr>`;
    }
  }
};

// ============================================
// UI - PRODUCTOS
// ============================================
const ProductoUI = {
  init: () => {
    document.getElementById('producto-form').addEventListener('submit', ProductoUI.handleSubmit);
    document.getElementById('producto-cancel-btn').addEventListener('click', ProductoUI.cancelEdit);
    document.getElementById('producto-busqueda').addEventListener('input', ProductoUI.renderTable);
    document.getElementById('filtro-categoria').addEventListener('change', ProductoUI.renderTable);
    document.getElementById('filtro-proveedor').addEventListener('change', ProductoUI.renderTable);
    ProductoUI.updateCategoriaSelects();
    ProductoUI.updateProveedorSelects();
    ProductoUI.renderTable();
  },
  updateCategoriaSelects: async () => {
    const selects = [document.getElementById('producto-categoria'), document.getElementById('filtro-categoria')];
    try {
      const categorias = await CategoriaService.getAll();
      selects.forEach(select => {
        const val = select.value;
        const first = select.id === 'filtro-categoria' ? 'Todas las categorías' : 'Seleccione una categoría';
        select.innerHTML = `<option value="">${first}</option>`;
        categorias.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id; opt.textContent = c.nombre;
          select.appendChild(opt);
        });
        select.value = val;
      });
    } catch (e) { console.error('Error cargando categorías:', e); }
  },
  updateProveedorSelects: async () => {
    const selects = [document.getElementById('producto-proveedor'), document.getElementById('filtro-proveedor')];
    try {
      const proveedores = await ProveedorService.getAll();
      selects.forEach(select => {
        const val = select.value;
        const first = select.id === 'filtro-proveedor' ? 'Todos los proveedores' : 'Sin proveedor';
        select.innerHTML = `<option value="">${first}</option>`;
        proveedores.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id; opt.textContent = p.nombre;
          select.appendChild(opt);
        });
        select.value = val;
      });
    } catch (e) { console.error('Error cargando proveedores:', e); }
  },
  handleSubmit: async (e) => {
    e.preventDefault();
    const idInput     = document.getElementById('producto-id');
    const nombreInput = document.getElementById('producto-nombre');
    const errorSpan   = document.getElementById('producto-error-span');
    errorSpan.textContent = '';
    nombreInput.classList.remove('error');
    const categoriaId = parseInt(document.getElementById('producto-categoria').value);
    if (isNaN(categoriaId) || categoriaId <= 0) {
      errorSpan.textContent = 'Debe seleccionar una categoría obligatoria.';
      return;
    }
    const producto = {
      id:          idInput.value ? parseInt(idInput.value) : null,
      nombre:      nombreInput.value,
      descripcion: document.getElementById('producto-descripcion').value,
      categoriaId: categoriaId,  // ◄— CAMBIADO: Antes decía areaId
      categoria_id: categoriaId,
      proveedorId: document.getElementById('producto-proveedor').value ? parseInt(document.getElementById('producto-proveedor').value) : null,
      existencia:  parseInt(document.getElementById('producto-existencia').value),
      precioCosto: parseFloat(document.getElementById('producto-precio-costo').value),
    };
    try {
      if (idInput.value) { await ProductoService.update(producto); }
      else               { await ProductoService.create(producto); }
      ProductoUI.cancelEdit();
      await ProductoUI.renderTable();
    } catch (error) {
      errorSpan.textContent = error.message;
      nombreInput.classList.add('error');
    }
  },
  cancelEdit: () => {
    document.getElementById('producto-form').reset();
    document.getElementById('producto-id').value = '';
    document.getElementById('producto-existencia').value = '0';
    document.getElementById('producto-precio-costo').value = '0';
    document.getElementById('producto-form-title').textContent = 'Registrar Nuevo Producto';
    document.getElementById('producto-submit-btn').textContent = 'Guardar';
    document.getElementById('producto-cancel-btn').style.display = 'none';
    document.getElementById('producto-error-span').textContent = '';
    document.getElementById('producto-nombre').classList.remove('error');
  },
  edit: (producto) => {
    document.getElementById('producto-id').value           = producto.id;
    document.getElementById('producto-form-title').textContent = `Editar Producto: ${producto.codigo_producto}`;
    document.getElementById('producto-nombre').value       = producto.nombre;
    document.getElementById('producto-descripcion').value  = producto.descripcion;
    document.getElementById('producto-categoria').value    = producto.categoriaId;
    document.getElementById('producto-proveedor').value    = producto.proveedor_id || '';
    document.getElementById('producto-existencia').value   = producto.existencia;
    document.getElementById('producto-precio-costo').value = producto.precio_costo;
    document.getElementById('producto-submit-btn').textContent  = 'Actualizar';
    document.getElementById('producto-cancel-btn').style.display = 'inline-block';
    window.scrollTo(0, 0);
  },
  updateExistencia: async (id, nombre, existenciaActual) => {
    const nueva = prompt(`Actualizar existencia para "${nombre}"\nActual: ${existenciaActual}\n\nNueva existencia:`, existenciaActual);
    if (nueva !== null) {
      try {
        await ProductoService.updateExistencia(id, parseInt(nueva));
        await ProductoUI.renderTable();
      } catch (error) { alert(error.message); }
    }
  },
  delete: (id, nombre) => {
    Modal.confirm(`¿Está seguro de eliminar el producto "${nombre}"?`, async () => {
      try {
        await ProductoService.delete(id);
        await ProductoUI.renderTable();
      } catch (error) { alert(error.message); }
    });
  },
  renderTable: async () => {
    const tbody  = document.getElementById('productos-tbody');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center">Cargando...</td></tr>';
    const filtros = {
      busqueda:    document.getElementById('producto-busqueda').value,
      categoriaId: document.getElementById('filtro-categoria').value,
      proveedorId: document.getElementById('filtro-proveedor').value,
    };
    try {
      const productos = await ProductoService.getAll(filtros);
      tbody.innerHTML = '';
      if (productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center">No se encontraron productos</td></tr>';
        return;
      }
      productos.forEach(p => {
        const tr = document.createElement('tr');
        const nH  = p.nombre.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        const pJ  = JSON.stringify(p).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        tr.innerHTML = `
          <td>${p.codigo_producto}</td>
          <td>${p.nombre}</td>
          <td>${p.categoria_nombre || 'N/A'}</td>
          <td>${p.proveedor_nombre || 'N/A'}</td>
          <td>${p.existencia}</td>
          <td>$${parseFloat(p.precio_costo).toFixed(2)}</td>
          <td>
            <button class="btn-edit"   onclick='ProductoUI.edit(${pJ})'>Editar</button>
            <button class="btn-update" onclick="ProductoUI.updateExistencia(${p.id},'${nH}',${p.existencia})">Actualizar Existencia</button>
            <button class="btn-delete" onclick="ProductoUI.delete(${p.id},'${nH}')">Eliminar</button>
          </td>`;
        tbody.appendChild(tr);
      });
    } catch (error) {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:red">${error.message}</td></tr>`;
    }
  }
};

// ============================================
// UI - COTIZACIONES
// ============================================
let cotizacionTemporal = {
  cliente: {}, condiciones: {}, productos: [],
  totales: { subtotal: 0, iva: 0, total: 0 }
};

const CotizacionUI = {
  init: () => {
    document.getElementById('cotizacion-form').addEventListener('submit', CotizacionUI.handleSubmit);
    document.getElementById('cot-producto-buscar').addEventListener('input', CotizacionUI.handleAutocomplete);
  },
  handleAutocomplete: async (e) => {
    const termino      = e.target.value;
    const resultadosDiv = document.getElementById('cot-producto-resultados');
    if (termino.length < 2) { resultadosDiv.innerHTML = ''; return; }
    try {
      const productos = await ProductoService.autocompletar(termino);
      resultadosDiv.innerHTML = '';
      if (productos.length === 0) {
        resultadosDiv.innerHTML = '<div class="autocomplete-item">No se encontraron productos</div>';
        return;
      }
      productos.forEach(producto => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'autocomplete-item';
        itemDiv.textContent = `(${producto.codigo_producto}) ${producto.nombre} - $${producto.precio_costo}`;
        itemDiv.addEventListener('click', () => {
          CotizacionUI.seleccionarProducto(producto);
          resultadosDiv.innerHTML = '';
          e.target.value = '';
        });
        resultadosDiv.appendChild(itemDiv);
      });
    } catch (error) {
      resultadosDiv.innerHTML = '<div class="autocomplete-item error">Error al buscar</div>';
    }
  },
  seleccionarProducto: (producto) => {
    const stockDisponible    = parseInt(producto.existencia);
    const productoExistente  = cotizacionTemporal.productos.find(p => p.id === producto.id);
    if (productoExistente) {
      const nuevaCantidad = productoExistente.cantidad + 1;
      if (nuevaCantidad > productoExistente.existencia) {
        alert(`Stock insuficiente para "${producto.nombre}".\nStock disponible: ${productoExistente.existencia}\nYa tiene: ${productoExistente.cantidad}`);
        return;
      }
      productoExistente.cantidad = nuevaCantidad;
      productoExistente.subtotal = productoExistente.precio_unitario * nuevaCantidad;
    } else {
      if (1 > stockDisponible) { alert(`No hay stock disponible para "${producto.nombre}".`); return; }
      cotizacionTemporal.productos.push({
        id: producto.id, codigo: producto.codigo_producto, nombre: producto.nombre,
        cantidad: 1, precio_unitario: parseFloat(producto.precio_costo),
        subtotal: parseFloat(producto.precio_costo), existencia: stockDisponible,
      });
    }
    CotizacionUI.renderTablaCotizacion();
  },
  renderTablaCotizacion: () => {
    const tbody = document.getElementById('cot-tbody-productos');
    tbody.innerHTML = '';
    if (cotizacionTemporal.productos.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">Añada productos desde el buscador...</td></tr>';
      CotizacionUI.calcularTotales();
      return;
    }
    cotizacionTemporal.productos.forEach((p, index) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong>${p.codigo}</strong><br>${p.nombre}<br><small style="color:#4a5568">(Stock: ${p.existencia})</small></td>
        <td class="col-cantidad"><input type="number" min="1" max="${p.existencia}" value="${p.cantidad}" onchange="CotizacionUI.actualizarCantidad(${index}, this.value)"></td>
        <td class="col-precio">$${p.precio_unitario.toFixed(2)}</td>
        <td class="col-subtotal">$${p.subtotal.toFixed(2)}</td>
        <td class="col-acciones"><button type="button" class="btn-delete" onclick="CotizacionUI.eliminarProductoTemporal(${index})">X</button></td>`;
      tbody.appendChild(tr);
    });
    CotizacionUI.calcularTotales();
  },
  actualizarCantidad: (index, nuevaCantidad) => {
    const producto = cotizacionTemporal.productos[index];
    const cantidad = parseInt(nuevaCantidad);
    if (isNaN(cantidad) || cantidad < 1) {
      producto.cantidad = 1; producto.subtotal = producto.precio_unitario;
      CotizacionUI.renderTablaCotizacion(); return;
    }
    if (cantidad > producto.existencia) {
      alert(`Stock insuficiente.\nProducto: ${producto.nombre}\nDisponible: ${producto.existencia}`);
      CotizacionUI.renderTablaCotizacion(); return;
    }
    producto.cantidad = cantidad;
    producto.subtotal = producto.precio_unitario * cantidad;
    CotizacionUI.renderTablaCotizacion();
  },
  eliminarProductoTemporal: (index) => {
    cotizacionTemporal.productos.splice(index, 1);
    CotizacionUI.renderTablaCotizacion();
  },
  calcularTotales: () => {
    let subtotal = 0;
    cotizacionTemporal.productos.forEach(p => { subtotal += p.subtotal; });
    const iva   = subtotal * 0.19;
    const total = subtotal + iva;
    cotizacionTemporal.totales = { subtotal, iva, total };
    document.getElementById('cot-subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('cot-iva').textContent      = `$${iva.toFixed(2)}`;
    document.getElementById('cot-total').textContent    = `$${total.toFixed(2)}`;
  },
  handleSubmit: async (e) => {
    e.preventDefault();
    cotizacionTemporal.cliente = {
      nombre:   document.getElementById('cot-cliente-nombre').value,
      rut:      document.getElementById('cot-cliente-rut').value,
      telefono: document.getElementById('cot-cliente-telefono').value,
      correo:   document.getElementById('cot-cliente-correo').value,
    };
    cotizacionTemporal.condiciones = {
      forma_pago: document.getElementById('cot-forma-pago').value,
      validez:    document.getElementById('cot-validez').value,
      entrega:    document.getElementById('cot-entrega').value,
      garantia:   document.getElementById('cot-garantia').value,
    };
    if (cotizacionTemporal.productos.length === 0) { alert('No puede guardar una cotización sin productos.'); return; }
    if (!cotizacionTemporal.cliente.nombre.trim()) {
      alert("El 'Nombre o Razón Social' del cliente es obligatorio.");
      document.getElementById('cot-cliente-nombre').focus(); return;
    }
    try {
      const resultado = await CotizacionService.create(cotizacionTemporal);

      // Guardamos el ID antes de limpiar el formulario
      const idNuevo = resultado.id_cotizacion;
      const folioNuevo = `COT-${String(idNuevo).padStart(4, '0')}`;

      // Limpiamos el formulario
      cotizacionTemporal = { cliente: {}, condiciones: {}, productos: [], totales: { subtotal: 0, iva: 0, total: 0 } };
      document.getElementById('cotizacion-form').reset();
      CotizacionUI.renderTablaCotizacion();

      // Abrimos el PDF directamente sin alert previo
      await HistorialUI.verDetalle(idNuevo, folioNuevo);

    } catch (error) {
      alert('Error al guardar: ' + error.message);
    }
  }
};

// ============================================
// UI - HISTORIAL DE COTIZACIONES
// ============================================
const HistorialUI = {
  _filterTimeout: null,

  init: () => {
    document.getElementById('hist-filtro-cliente').addEventListener('input',  HistorialUI.handleFilter);
    document.getElementById('hist-filtro-fecha').addEventListener('change',   HistorialUI.handleFilter);
    HistorialUI.renderTable();
  },

  handleFilter: () => {
    clearTimeout(HistorialUI._filterTimeout);
    HistorialUI._filterTimeout = setTimeout(() => HistorialUI.renderTable(), 500);
  },

  renderTable: async () => {
    const tbody = document.getElementById('historial-tabla-body');
    // ✅ FIX: limpiamos las filas hardcodeadas del HTML desde el inicio
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">Cargando...</td></tr>';

    const filtros = {
      cliente: document.getElementById('hist-filtro-cliente').value,
      fecha:   document.getElementById('hist-filtro-fecha').value,
    };

    try {
      const cotizaciones = await CotizacionService.getAll(filtros);
      tbody.innerHTML = '';
      if (cotizaciones.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center">No se encontraron cotizaciones</td></tr>';
        return;
      }
      cotizaciones.forEach(cot => {
        const tr    = document.createElement('tr');
        const monto = parseFloat(cot.monto_total).toLocaleString('es-CL', { style: 'currency', currency: 'CLP' });
        const [y, m, d]        = cot.fecha_emision.split('-');
        const fechaFormateada  = `${d}-${m}-${y}`;
        const folio            = `COT-${String(cot.id).padStart(4, '0')}`;
        tr.innerHTML = `
          <td>${folio}</td>
          <td>${cot.cliente_nombre}</td>
          <td>${fechaFormateada}</td>
          <td>${monto}</td>
          <td>
            <button class="btn-view"   onclick="HistorialUI.verDetalle(${cot.id}, '${folio}')">Ver / PDF</button>
            <button class="btn-delete" onclick="HistorialUI.anular(${cot.id}, '${folio}')">Anular</button>
          </td>`;
        tbody.appendChild(tr);
      });
    } catch (error) {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:red">${error.message}</td></tr>`;
    }
  },

  // ============================================
  // VER EN PANTALLA Y DESCARGAR PDF AUTOMÁTICAMENTE
  // ============================================
  verDetalle: async (id, folio) => {
    try {
      // 1. Pedimos los bytes puros del PDF al backend
      const blob = await CotizacionService.obtenerPDFBlob(id);
      
      // 2. Creamos la URL temporal en la memoria del navegador
      const pdfUrl = URL.createObjectURL(blob);
      
      // -------------------------------------------------------------
      // ACCIÓN 1: Abrir en una nueva pestaña para visualizarlo
      // -------------------------------------------------------------
      const nuevaPestana = window.open(pdfUrl, '_blank');
      if (!nuevaPestana) {
        console.warn('El bloqueador de pop-ups impidió abrir la pestaña visual.');
      }

      // -------------------------------------------------------------
      // ACCIÓN 2: Forzar la descarga automática en segundo plano
      // -------------------------------------------------------------
      const enlaceDescarga = document.createElement('a');
      enlaceDescarga.href = pdfUrl;
      enlaceDescarga.download = `Cotizacion_${folio}.pdf`; // Nombre del archivo en la PC
      
      document.body.appendChild(enlaceDescarga);
      enlaceDescarga.click();
      document.body.removeChild(enlaceDescarga);
      
      // -------------------------------------------------------------
      // Limpieza de memoria (esperamos 5s para no romper la pestaña abierta)
      // -------------------------------------------------------------
      setTimeout(() => {
        URL.revokeObjectURL(pdfUrl);
      }, 5000);

    } catch (error) {
      alert('Error al procesar el documento digital: ' + error.message);
    }
  },

  anular: (id, folio) => {
    Modal.confirm(`¿Está seguro de anular la cotización "${folio}"? Esta acción no se puede deshacer.`, async () => {
      try {
        const resultado = await CotizacionService.anular(id);
        alert(resultado.mensaje || 'Cotización anulada.');
        await HistorialUI.renderTable();
      } catch (error) { alert(error.message); }
    });
  }
};

// ============================================
// NAVEGACIÓN POR TABS
// ============================================
const Navigation = {
  init: () => {
    const tabButtons  = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const targetTab = btn.getAttribute('data-tab');
        tabButtons.forEach(b  => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(targetTab).classList.add('active');
        // Recargar historial al entrar a esa pestaña
        if (targetTab === 'historial') { HistorialUI.renderTable(); }
      });
    });
  }
};

// ============================================
// MODAL DE CONFIRMACIÓN
// ============================================
const Modal = {
  init: () => {
    const modal     = document.getElementById('confirm-modal');
    const cancelBtn = document.getElementById('cancel-confirm-btn');
    cancelBtn.addEventListener('click', () => { modal.style.display = 'none'; });
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
  },
  confirm: (message, onConfirm) => {
    const modal      = document.getElementById('confirm-modal');
    const messageEl  = document.getElementById('confirm-message');
    const confirmBtn = document.getElementById('confirm-btn');
    messageEl.textContent = message;
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
    newBtn.addEventListener('click', () => { modal.style.display = 'none'; onConfirm(); });
    modal.style.display = 'flex';
  }
};

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', () => {
  Navigation.init();
  Modal.init();
  CategoriaUI.init();
  ProveedorUI.init();
  ProductoUI.init();
  CotizacionUI.init();
  HistorialUI.init();
});