-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-11-2026 a las 06:43:18
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `coffeeapp_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido`
--

INSERT INTO `detalle_pedido` (`id`, `pedido_id`, `producto_id`, `nombre_producto`, `precio_unitario`, `cantidad`, `subtotal`) VALUES
(1, 1, 9, 'Muffin de arándanos', 42.00, 1, 42.00),
(2, 1, 8, 'Croissant', 38.00, 2, 76.00),
(3, 2, 9, 'Muffin de arándanos', 42.00, 1, 42.00),
(4, 2, 8, 'Croissant', 38.00, 2, 76.00),
(5, 3, 9, 'Muffin de arándanos', 42.00, 1, 42.00),
(6, 3, 8, 'Croissant', 38.00, 2, 76.00),
(7, 4, 9, 'Muffin de arándanos', 42.00, 1, 42.00),
(8, 4, 8, 'Croissant', 38.00, 2, 76.00),
(9, 5, 8, 'Croissant', 38.00, 2, 76.00),
(10, 5, 9, 'Muffin de arándanos', 42.00, 1, 42.00),
(11, 5, 10, 'Cheesecake de fresa', 68.00, 1, 68.00),
(12, 6, 2, 'Café Americano', 35.00, 1, 35.00),
(13, 6, 9, 'Muffin de arándanos', 42.00, 1, 42.00),
(14, 7, 2, 'Café Americano', 35.00, 1, 35.00),
(15, 8, 2, 'Café Americano', 35.00, 2, 70.00),
(16, 9, 2, 'Café Americano', 35.00, 1, 35.00),
(17, 10, 10, 'Cheesecake de fresa', 68.00, 1, 68.00),
(18, 11, 2, 'Café Americano', 35.00, 1, 35.00),
(19, 12, 7, 'Pastel de chocolate', 65.00, 1, 65.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `total`, `estado`, `fecha`) VALUES
(1, 2, 118.00, 'Pendiente', '2026-11-11 05:46:13'),
(2, 2, 118.00, 'Pendiente', '2026-11-11 05:46:58'),
(3, 2, 118.00, 'Pendiente', '2026-11-11 05:59:37'),
(4, 2, 118.00, 'Pendiente', '2026-11-11 06:06:05'),
(5, 2, 186.00, 'Pendiente', '2026-11-11 06:21:49'),
(6, 2, 77.00, 'Pendiente', '2026-11-11 06:24:35'),
(7, 2, 35.00, 'Pendiente', '2026-11-11 18:13:21'),
(8, 2, 70.00, 'Pendiente', '2026-11-11 18:58:29'),
(9, 2, 35.00, 'Pendiente', '2026-11-11 18:59:10'),
(10, 2, 68.00, 'Pendiente', '2026-11-11 19:02:34'),
(11, 2, 35.00, 'Pendiente', '2026-11-18 05:17:31'),
(12, 2, 65.00, 'Pendiente', '2026-11-18 05:39:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `stock`, `imagen`) VALUES
(2, 'Café Americano', ' Café preparado con espresso y agua caliente, de sabor intenso y aroma agradable.', 35.00, 19, 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=600'),
(3, 'Capuchino', 'Espresso combinado con leche vaporizada y espuma cremosa.', 48.00, 20, 'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?w=600'),
(4, 'Latte frío', 'Bebida de café espresso con leche fría y hielo, ideal para refrescarse.', 55.00, 18, 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=600'),
(7, 'Pastel de chocolate', 'Rebanada de pastel esponjoso de chocolate, cubierta con betún cremoso.', 65.00, 11, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600'),
(8, 'Croissant', 'Pan de origen francés, elaborado con masa hojaldrada y mantequilla, de textura crujiente.', 38.00, 6, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600'),
(9, 'Muffin de arándanos', 'Panecillo suave y esponjoso con arándanos, perfecto para acompañar el café.', 42.00, 8, 'https://images.unsplash.com/photo-1607958996333-41aef7caefaa?w=600'),
(10, 'Cheesecake de fresa', 'Postre cremoso de queso con base de galleta y cobertura de fresa.', 68.00, 8, 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=600');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('cliente','admin') DEFAULT 'cliente',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `email`, `password`, `rol`, `fecha_registro`) VALUES
(1, 'Lilia', 'liliaabigailtovarbautista14@gmail.com', '$2y$10$UN5LTwqGfGeZWttfX1p9MeO9lLfHCBH8L4eF7tDitltgC19V.3diW', 'cliente', '2026-11-03 04:08:39'),
(2, 'Abigail', 'lili@gmail.com', '$2y$10$hPwwfaFbG4Hi1pd8zns96e1CF5OfkXf75JXNIXjzDu1Afp9W7nI6u', 'cliente', '2026-11-11 00:52:51'),
(4, 'admin', 'admin@gmail.com', '$2y$10$RJjgGzVLLxI1skuDzGhvp.HazWT6O37SddxCmd3NdlzmA2amfzvtK', 'admin', '2026-11-11 05:15:02');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
