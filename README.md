
# 📦 Sistema de Gestión de Inventario - ELECTRIFER

Sistema web profesional para gestionar categorías, proveedores, productos y cotizaciones con generación de PDF nativo automatizado. Desarrollado en PHP + MySQL + JavaScript puro (sin frameworks de frontend) y Dompdf en el backend.

---

## 🗂️ Estructura de Archivos

proyecto/
├── index.html              # Interfaz principal (todas las vistas en tabs)
├── app.js                  # Lógica del frontend (servicios + UI asíncrona)
├── styles.css              # Estilos visuales de la plataforma web
├── db.php                  # Conexión centralizada a la base de datos (PDO)
├── api_areas.php           # API REST: Gestión de categorías/áreas
├── api_proveedores.php     # API REST: Proveedores
├── api_productos.php       # API REST: Productos e inventario
├── api_cotizaciones.php    # API REST: Cotizaciones, historial y motor PDF
└── vendor/                 # Dependencias de Composer (Dompdf)

---

## ⚙️ Requisitos

| Herramienta | Versión recomendada |
| --- | --- |
| **XAMPP / Laragon** | 8.x (Apache + MySQL) |
| **PHP** | 7.4 o superior (Con extensión `GD` activa para imágenes) |
| **MySQL / MariaDB** | 5.7 o superior |
| **Composer** | Para la gestión de dependencias del backend |

---

## 🚀 Instalación y Configuración

### 1. Copiar archivos

Copia toda la carpeta del proyecto dentro del directorio raíz de tu servidor local (por ejemplo, en XAMPP):

C:\xampp\htdocs\inventario\


### 2. Instalar dependencias del Backend (Dompdf)

Abre una terminal en la raíz del proyecto (donde está el archivo `composer.json`) y ejecuta el siguiente comando para reconstruir las librerías de generación de PDF:

composer install


> 💡 **Nota Corporativa:** Asegúrate de guardar el archivo de tu logotipo en la raíz del proyecto con el nombre exacto de **`logo.png`** para que el sistema lo procese en los reportes.

### 3. Crear la base de datos

Abre phpMyAdmin (`http://localhost/phpmyadmin`), crea una base de datos llamada `inventario_db` y ejecuta el siguiente script SQL:

CREATE DATABASE IF NOT EXISTS inventario_db CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE inventario_db;

CREATE TABLE categorias (
  id     INT(11) AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL
);

CREATE TABLE proveedores (
  id       INT(11) AUTO_INCREMENT PRIMARY KEY,
  nombre   VARCHAR(255) NOT NULL,
  telefono VARCHAR(50),
  correo   VARCHAR(255)
);

CREATE TABLE productos (
  id              INT(11) AUTO_INCREMENT PRIMARY KEY,
  codigo_producto VARCHAR(10) NOT NULL UNIQUE,
  nombre          VARCHAR(255) NOT NULL,
  descripcion     TEXT,
  categoria_id    INT(11) NOT NULL,
  proveedor_id    INT(11),
  existencia      INT(11) NOT NULL DEFAULT 0,
  precio_costo    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  fecha_income    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
);

CREATE TABLE clientes (
  id                  INT(11) AUTO_INCREMENT PRIMARY KEY,
  nombre_razon_social VARCHAR(255) NOT NULL,
  rut                 VARCHAR(12),
  telefono            VARCHAR(50),
  correo              VARCHAR(255),
  direccion           VARCHAR(255)
);

CREATE TABLE cotizaciones (
  id                          INT(11) AUTO_INCREMENT PRIMARY KEY,
  cliente_id                  INT(11) NOT NULL,
  fecha_emision               DATE NOT NULL,
  fecha_vencimiento           DATE,
  estado                      ENUM('Pendiente','Aprobada','Rechazada','Vencida') NOT NULL DEFAULT 'Pendiente',
  descuento_global_porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  subtotal                    DECIMAL(12,2) NOT NULL,
  iva                         DECIMAL(12,2) NOT NULL,
  total                       DECIMAL(12,2) NOT NULL,
  forma_de_pago               VARCHAR(100),
  validez_oferta              VARCHAR(100),
  fecha_entrega               VARCHAR(100),
  garantia                    VARCHAR(100),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

CREATE TABLE cotizacion_detalles (
  id                        INT(11) AUTO_INCREMENT PRIMARY KEY,
  cotizacion_id             INT(11) NOT NULL,
  producto_id               INT(11) NOT NULL,
  cantidad                  INT(11) NOT NULL,
  precio_unitario_congelado DECIMAL(10,2) NOT NULL,
  subtotal_linea            DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id)   REFERENCES productos(id)
);

### 4. Configurar la conexión (`db.php`)

Abre `db.php` y ajusta las credenciales según tu entorno si es necesario:

$host     = '127.0.0.1';
$db_name  = 'inventario_db';
$username = 'root';   
$password = '';       // Vacío por defecto en XAMPP

---

## 🔌 API Endpoints (Arquitectura REST Híbrida)

Todos los endpoints se comunican asíncronamente mediante peticiones HTTP estructuradas e intercambian payloads en formato JSON.

### `api_areas.php`

* **`listar` [GET]:** Obtiene todas las categorías registradas.
* **`crear` [POST]:** Registra una nueva categoría en el sistema.
* **`actualizar` [POST]:** Edita el nombre de una categoría existente.
* **`eliminar` [POST]:** Remueve de forma lógica o física una categoría.

### `api_productos.php`

* **`listar` [GET]:** Lista productos aplicando filtros por coincidencia de texto, buscador o id de categoría.
* **`autocompletar` [GET]:** Motor de búsqueda veloz en tiempo de ejecución para el módulo de cotizaciones.
* **`crear` [POST]:** Registra un producto enlazando la categoría mediante soporte híbrido de variables (`categoriaId`/`categoria_id`).
* **`actualizar` [POST]:** Modifica los datos generales y relacionales del ítem de inventario.
* **`actualizarExistencia` [POST]:** Ajuste rápido y directo de stock físico sin comprometer el resto de atributos.

### `api_cotizaciones.php`

* **`crear` [POST]:** Procesa la venta, registra o actualiza los datos del cliente de forma dinámica y congela los precios unitarios.
* **`listar` [GET]:** Extrae el historial general con formato de moneda chilena (CLP).
* **`obtener` [GET]:** Recupera el desglose completo del folio para auditoría interna.
* **`generar_pdf` [GET]:** Compila, renderiza y exporta el archivo binario del PDF corporativo usando **Dompdf**.

---

## 🧾 Exportación y Visualización de PDF Corporativo

El sistema procesa los documentos comerciales mediante un flujo avanzado de JavaScript (`Blob` binario y `URL.createObjectURL`). Al hacer clic en **"Ver / PDF"** o guardar una nueva cotización:

1. El servidor compila la vista HTML aislada, convierte la imagen corporativa (`logo.png`) a **Base64** para evitar problemas de rutas de Apache, inyecta las cuentas bancarias de **ELECTRIFER** y la firma autorizada de don *Patricio Zúñiga*.
2. El script `app.js` captura el flujo de bytes transmitido desde PHP.
3. El navegador ejecuta dos acciones en paralelo de forma asíncrona: **Abre el visor interactivo en una nueva pestaña** para revisión inmediata y **fuerza la descarga directa del archivo `.pdf**` en la carpeta local del dispositivo.

---

## 🐛 Solución de Problemas Comunes

* **Error de conexión `SQLSTATE[HY000] [2002] ... denegó expresamente`:** El motor de base de datos MySQL está apagado. Abre el panel de XAMPP y haz clic en *Start* en la fila de MySQL.
* **Error `Unexpected token '<', "..." is not valid JSON`:** Un script de PHP sufrió un error fatal de sintaxis o base de datos y devolvió una página HTML de Apache en lugar del JSON esperado. Revisa la pestaña *Network -> Response* pulsando `F12` para examinar el error real de PHP.
* **Los datos del cliente (RUT, Teléfono) salen con rayas (`—`) en el PDF:** El cliente ya existía en la base de datos previo a la optimización de actualización de datos. Registra una nueva cotización utilizando un cliente diferente o ingresa un RUT nuevo para forzar la actualización inteligente del registro.

---

Desarrollado para la gestión y automatización de **ELECTRIFER**.

