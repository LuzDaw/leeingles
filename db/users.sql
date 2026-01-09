-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 09-01-2026 a las 19:18:27
-- Versión del servidor: 10.11.14-MariaDB-0+deb12u2
-- Versión de PHP: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `leeingles`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('pendiente','activo','bloqueado') NOT NULL DEFAULT 'pendiente',
  `email_verificado_en` datetime DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `tipo_usuario` enum('EnPrueba','limitado','Inicio','Ahorro','Pro') DEFAULT 'EnPrueba',
  `ultima_conexion` datetime DEFAULT NULL,
  `ultimo_email_recordatorio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`, `estado`, `email_verificado_en`, `fecha_registro`, `tipo_usuario`, `ultima_conexion`, `ultimo_email_recordatorio`) VALUES
(74, 'luz', 'luz@idoneoweb.es', '$2y$12$kGV1Dww3o2lk66BJcplf3eELFIMU8VKWhdGa7oU0pSgDe79GJ/oO2', 1, 'activo', NULL, '2026-01-04 20:16:18', 'limitado', '2026-01-06 19:56:56', '2026-01-05 21:42:58'),
(108, 'luza', 'idoneoweb@gmail.com', '$2y$12$lBvo8VSyg0fKZeYshVOX1O368rbeEEqSaNAY//jftThgfYGMRAJkO', 0, 'activo', '2025-01-02 21:09:43', '2027-02-12 17:42:43', 'limitado', '2026-01-09 19:00:05', '2026-01-04 14:05:49'),
(109, 'luz4', 'info@idoneoweb.es', '$2y$12$/DK/As.89rakPyBe4RF8DOLs7GXzeGUvo/TNA59YBBN7US2Qz.ebW', 0, 'pendiente', NULL, '2026-01-03 22:25:35', 'gratis', NULL, NULL),
(110, 'luna', 'piknte@gmail.com', '$2y$12$2yo9ugWDhe.uBE0050qrCOjx4IBiLo0KQxXPDWVgDEheM8ZV/gRdG', 0, 'activo', '2026-01-06 13:23:13', '2025-12-06 14:22:33', 'premium', '2026-01-09 18:59:40', '2026-01-06 13:28:43');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
