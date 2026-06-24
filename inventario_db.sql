-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-06-2026 a las 17:12:09
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `inventario_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(15, 'categoría 1'),
(16, 'categoría 2');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nombre_razon_social` varchar(255) NOT NULL,
  `rut` varchar(12) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `nombre_razon_social`, `rut`, `telefono`, `correo`, `direccion`) VALUES
(1, 'MPG', '21.565.814-7', '312312', 'afdasd@gmail.com', NULL),
(2, 'Cliente 1', '10.100.104-4', '312312', 'correo@gmail.com', NULL),
(5, 'cliente 2', '11.212.312-4', '', '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizaciones`
--

CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_emision` date NOT NULL DEFAULT curdate(),
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('Pendiente','Aprobada','Rechazada','Vencida') NOT NULL DEFAULT 'Pendiente',
  `descuento_global_porcentaje` decimal(5,2) DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL,
  `iva` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `forma_de_pago` varchar(100) DEFAULT NULL,
  `validez_oferta` varchar(100) DEFAULT NULL,
  `fecha_entrega` varchar(100) DEFAULT NULL,
  `garantia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizaciones`
--

INSERT INTO `cotizaciones` (`id`, `cliente_id`, `fecha_emision`, `fecha_vencimiento`, `estado`, `descuento_global_porcentaje`, `subtotal`, `iva`, `total`, `forma_de_pago`, `validez_oferta`, `fecha_entrega`, `garantia`) VALUES
(6, 2, '2026-06-17', '0000-00-00', 'Pendiente', 0.00, 100.00, 19.00, 119.00, '50% anticipo', '10 dias', 'inmediata', '3 meses'),
(7, 2, '2026-06-17', '0000-00-00', 'Pendiente', 0.00, 100.00, 19.00, 119.00, '50% anticipo', '10 dias', 'inmediata', '3 meses'),
(8, 2, '2026-06-17', '2026-06-27', 'Pendiente', 0.00, 100.00, 19.00, 119.00, '50% anticipo', '10 dias', 'inmediata', '3 meses'),
(9, 2, '2026-06-17', '2026-06-27', 'Pendiente', 0.00, 300.00, 57.00, 357.00, '50% anticipo', '10 dias', 'inmediata', '3 meses'),
(13, 2, '2026-06-17', '2026-06-27', 'Pendiente', 0.00, 360.00, 68.40, 428.40, '50% anticipo', '10 dias', 'inmediata', ''),
(14, 2, '2026-06-18', '2026-07-18', 'Pendiente', 0.00, 120.00, 22.80, 142.80, '', '', '', ''),
(15, 2, '2026-06-18', '2026-07-03', 'Pendiente', 0.00, 1230.00, 233.70, 1463.70, '', '15 dias', '', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizacion_detalles`
--

CREATE TABLE `cotizacion_detalles` (
  `id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario_congelado` decimal(10,2) NOT NULL,
  `subtotal_linea` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cotizacion_detalles`
--

INSERT INTO `cotizacion_detalles` (`id`, `cotizacion_id`, `producto_id`, `cantidad`, `precio_unitario_congelado`, `subtotal_linea`) VALUES
(7, 6, 15, 1, 100.00, 100.00),
(8, 7, 15, 1, 100.00, 100.00),
(9, 8, 15, 1, 100.00, 100.00),
(10, 9, 15, 3, 100.00, 300.00),
(15, 13, 15, 3, 120.00, 360.00),
(16, 14, 15, 1, 120.00, 120.00),
(17, 15, 18, 1, 1230.00, 1230.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `codigo_producto` varchar(10) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `proveedor_id` int(11) DEFAULT NULL,
  `existencia` int(11) NOT NULL DEFAULT 0,
  `precio_costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_ingreso` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo_producto`, `nombre`, `descripcion`, `categoria_id`, `proveedor_id`, `existencia`, `precio_costo`, `fecha_ingreso`) VALUES
(15, 'PRO001', 'Producto 3', 'ejemplo1', 15, 8, 10, 120.00, '2026-06-17 21:43:11'),
(18, 'PRO002', 'Producto 2', '', 16, 8, 2, 1230.00, '2026-06-18 03:45:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) NOT NULL,
  `correo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedores`
--

INSERT INTO `proveedores` (`id`, `nombre`, `telefono`, `correo`) VALUES
(8, 'nombre', '9 72342342', 'nombre1@gmail.com');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_unico` (`nombre`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rut` (`rut`);

--
-- Indices de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `cotizacion_detalles`
--
ALTER TABLE `cotizacion_detalles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cotizacion_id` (`cotizacion_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_producto_unico` (`codigo_producto`),
  ADD KEY `fk_producto_area` (`categoria_id`),
  ADD KEY `fk_producto_proveedor` (`proveedor_id`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_unico` (`nombre`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `cotizacion_detalles`
--
ALTER TABLE `cotizacion_detalles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cotizaciones`
--
ALTER TABLE `cotizaciones`
  ADD CONSTRAINT `cotizaciones_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`);

--
-- Filtros para la tabla `cotizacion_detalles`
--
ALTER TABLE `cotizacion_detalles`
  ADD CONSTRAINT `cotizacion_detalles_ibfk_1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cotizacion_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_area` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `fk_producto_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
