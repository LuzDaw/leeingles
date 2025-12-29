-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-12-2025 a las 14:28:30
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
-- Base de datos: `traductor_app`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `verificaciones_email`
--

CREATE TABLE `verificaciones_email` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expira_en` datetime NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `verificaciones_email`
--

INSERT INTO `verificaciones_email` (`id`, `id_usuario`, `token_hash`, `expira_en`, `creado_en`) VALUES
(47, 75, '$2y$10$jxTnmoEleyXt4KBcgVt4X.7qu.5ciVR0OD.CpxRNzXVWmmnYY/lhi', '2025-12-23 13:53:29', '2025-12-22 13:53:29'),
(48, 76, '$2y$10$SZ03SMFt4aUfuuYnen4Mb.qjhrv0QdbUofZoA/cJngpfJ4viN5Pti', '2025-12-23 13:57:21', '2025-12-22 13:57:21'),
(49, 78, '$2y$10$Hb55Gaav9X5e6jSf8rapQeen2/HAsNxjfbm9HRNEg17IB1jJaLyAS', '2025-12-23 14:09:03', '2025-12-22 14:09:03'),
(50, 79, '$2y$10$1cV3bEYQQiwKUMJelis2DOrOHLWI/YLSilS/mFQz7i81szbUAT.AW', '2025-12-23 14:13:17', '2025-12-22 14:13:17'),
(51, 80, '$2y$10$mHVoVXBT6rqFEousoBKYB.uQhUq209t61mgHNk1LmvM6ucc9c.oZG', '2025-12-23 14:15:13', '2025-12-22 14:15:13'),
(52, 81, '$2y$10$7XTIgwEp0V3ZJt6f1xA3gOZvQTT00juNl9K1nugeSHO.QM8eXTxhW', '2025-12-23 14:15:42', '2025-12-22 14:15:42');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `verificaciones_email`
--
ALTER TABLE `verificaciones_email`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_token_hash` (`token_hash`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `verificaciones_email`
--
ALTER TABLE `verificaciones_email`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `verificaciones_email`
--
ALTER TABLE `verificaciones_email`
  ADD CONSTRAINT `verificaciones_email_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
