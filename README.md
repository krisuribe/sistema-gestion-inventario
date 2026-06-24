# Sistema de Gestión de Inventario

Sistema web completo para la gestión de inventario de un negocio, desarrollado con HTML, CSS y JavaScript puro. Incluye gestión de áreas (categorías), proveedores y productos con toda la lógica de negocio implementada.

## 🚀 Características

### Fase 1: Gestión de Áreas (Categorías)
- ✅ **RF-01**: Registro de Áreas con validación de nombre único
- ✅ **RF-02**: Listado de Áreas con acciones de Editar y Eliminar
- ✅ **RF-03**: Actualización de Áreas manteniendo unicidad
- ✅ **RF-04**: Eliminación de Áreas con validación de productos asociados

### Fase 2: Gestión de Proveedores
- ✅ **RF-05**: Registro de Proveedores (Nombre, Teléfono, Correo)
- ✅ **RF-06**: Listado de Proveedores completo
- ✅ **RF-07**: Actualización de Proveedores
- ✅ **RF-08**: Eliminación de Proveedores con validación de productos asociados

### Fase 3: Gestión de Productos e Inventario
- ✅ **RF-09**: Registro de Productos (SKU, Nombre, Descripción)
- ✅ **RF-10**: Asignación de Área a Producto
- ✅ **RF-11**: Asignación de Proveedor a Producto (opcional)
- ✅ **RF-12**: Registro automático de Fecha de Ingreso
- ✅ **RF-13**: Listado completo de Productos
- ✅ **RF-14**: Actualización de Productos (SKU no modificable)
- ✅ **RF-15**: Actualización de Existencia (Inventario)

### Fase 4: Lógica de Costos y Consultas
- ✅ **RF-16**: Registro de Costo y Margen
- ✅ **RF-17**: Cálculo automático de Precio de Venta
- ✅ **RF-18**: Búsqueda de Productos por SKU/Nombre
- ✅ **RF-19**: Filtro de Productos por Área
- ✅ **RF-20**: Filtro de Productos por Proveedor

## 📋 Requisitos

- Navegador web moderno (Chrome, Firefox, Edge, Safari)
- No se requiere instalación de servidor o base de datos
- Los datos se almacenan en LocalStorage del navegador

## 🎯 Instalación y Uso

1. **Descargar los archivos**:
   - `index.html`
   - `styles.css`
   - `app.js`

2. **Abrir el archivo**:
   - Abrir `index.html` en cualquier navegador web
   - El sistema se cargará automáticamente

3. **Usar el sistema**:
   - Navegar entre las pestañas: Áreas, Proveedores, Productos
   - Comenzar registrando Áreas y Proveedores
   - Luego registrar Productos asociados a las Áreas y Proveedores creados

## 📁 Estructura del Proyecto

```
probar/
├── index.html      # Estructura HTML de la aplicación
├── styles.css      # Estilos CSS modernos y responsivos
├── app.js          # Lógica de negocio y backend (JavaScript)
└── README.md       # Documentación del proyecto
```

## 🔧 Funcionalidades Técnicas

### Almacenamiento de Datos
- Utiliza **LocalStorage** del navegador para persistencia
- Los datos se guardan automáticamente al realizar operaciones
- Cada entidad (Áreas, Proveedores, Productos) se almacena por separado

### Validaciones Implementadas
- **Unicidad**: Nombres de áreas, nombres de proveedores, SKU de productos
- **Obligatoriedad**: Campos requeridos validados antes de guardar
- **Integridad referencial**: No se pueden eliminar áreas/proveedores con productos asociados
- **Tipos de datos**: Validación de números enteros para existencia, números decimales para precios
- **Cálculos**: Precio de venta calculado automáticamente: `PrecioVenta = PrecioCosto × (1 + PorcentajeMargen/100)`

### Interfaz de Usuario
- Diseño moderno y responsivo
- Navegación por pestañas
- Formularios intuitivos con validación en tiempo real
- Tablas con acciones de edición y eliminación
- Modal de confirmación para eliminaciones
- Búsqueda y filtros en tiempo real

## 📝 Flujo de Trabajo Recomendado

1. **Primer Paso**: Registrar Áreas (categorías de productos)
   - Ejemplo: "Electrónica", "Ropa", "Alimentación"

2. **Segundo Paso**: Registrar Proveedores
   - Ingresar nombre, teléfono y correo electrónico

3. **Tercer Paso**: Registrar Productos
   - Asignar SKU único, nombre, área y opcionalmente proveedor
   - Ingresar existencia, precio de costo y porcentaje de margen
   - El precio de venta se calcula automáticamente

4. **Gestionar Inventario**:
   - Actualizar existencia de productos
   - Editar información de productos
   - Buscar y filtrar productos según necesidades

## 🎨 Características de la Interfaz

- **Diseño Moderno**: Gradientes y sombras para una apariencia profesional
- **Responsive**: Adaptable a diferentes tamaños de pantalla
- **Animaciones**: Transiciones suaves para mejor experiencia de usuario
- **Validación Visual**: Campos con error se resaltan en rojo
- **Feedback Inmediato**: Mensajes de error claros y específicos

## 🔒 Restricciones de Negocio Implementadas

1. **Áreas**:
   - Nombre único y obligatorio
   - No se pueden eliminar si tienen productos asociados

2. **Proveedores**:
   - Nombre único
   - Todos los campos son obligatorios
   - No se pueden eliminar si tienen productos asociados

3. **Productos**:
   - SKU único y obligatorio (no modificable después de crear)
   - Nombre obligatorio
   - Debe pertenecer a un área
   - Proveedor opcional
   - Existencia debe ser entero >= 0
   - Precio de costo y margen deben ser > 0
   - Fecha de ingreso registrada automáticamente

## 🐛 Solución de Problemas

### Los datos no se guardan
- Verificar que el navegador soporte LocalStorage
- Asegurarse de no estar en modo incógnito (algunos navegadores bloquean LocalStorage)

### No se pueden eliminar áreas/proveedores
- Verificar que no haya productos asociados
- Primero eliminar o cambiar el área/proveedor de los productos asociados

### El precio de venta no se calcula
- Asegurarse de ingresar valores válidos para precio de costo y margen
- Los valores deben ser números mayores a cero

## 📞 Notas Adicionales

- Todos los datos se almacenan localmente en el navegador
- Para respaldar datos, se puede exportar el LocalStorage desde las herramientas de desarrollador
- El sistema es completamente funcional sin conexión a internet
- Los datos persisten entre sesiones del navegador

## 🎓 Tecnologías Utilizadas

- **HTML5**: Estructura semántica
- **CSS3**: Estilos modernos con gradientes, flexbox y grid
- **JavaScript (ES6+)**: Lógica de negocio y manipulación del DOM
- **LocalStorage API**: Almacenamiento persistente de datos

---

Desarrollado con ❤️ para gestión de inventario

