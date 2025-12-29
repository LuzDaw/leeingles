-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-07-2025 a las 22:13:29
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
-- Base de datos: `traductor_app`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hidden_texts`
--

CREATE TABLE `hidden_texts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text_id` int(11) NOT NULL,
  `hidden_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `hidden_texts`
--

INSERT INTO `hidden_texts` (`id`, `user_id`, `text_id`, `hidden_at`) VALUES
(10, 21, 156, '2025-07-23 17:35:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practice_progress`
--

CREATE TABLE `practice_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text_id` int(11) DEFAULT NULL,
  `mode` enum('selection','writing','sentences') NOT NULL,
  `total_words` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `incorrect_answers` int(11) NOT NULL,
  `accuracy` float NOT NULL,
  `session_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practice_progress`
--

INSERT INTO `practice_progress` (`id`, `user_id`, `text_id`, `mode`, `total_words`, `correct_answers`, `incorrect_answers`, `accuracy`, `session_date`, `completed_at`) VALUES
(106, 12, 160, 'selection', 6, 6, 6, 50, '2025-07-26 17:21:25', '2025-07-26 17:21:25'),
(107, 12, 160, 'writing', 6, 6, 1, 85.71, '2025-07-26 17:24:34', '2025-07-26 17:24:34'),
(108, 12, 160, 'sentences', 6, 6, 5, 54.55, '2025-07-26 18:47:18', '2025-07-26 18:47:18'),
(109, 12, 160, 'selection', 6, 6, 2, 75, '2025-07-26 20:05:10', '2025-07-26 20:05:10'),
(110, 12, 160, 'writing', 6, 6, 0, 100, '2025-07-26 20:10:15', '2025-07-26 20:10:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practice_time`
--

CREATE TABLE `practice_time` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mode` varchar(32) NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practice_time`
--

INSERT INTO `practice_time` (`id`, `user_id`, `mode`, `duration_seconds`, `created_at`) VALUES
(67, 12, 'selection', 1753550481, '2025-07-26 17:21:23'),
(68, 12, 'writing', 1753550672, '2025-07-26 17:24:36'),
(69, 12, 'selection', 1753560308, '2025-07-26 20:05:12'),
(70, 12, 'writing', 1753560612, '2025-07-26 20:10:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reading_progress`
--

CREATE TABLE `reading_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text_id` int(11) NOT NULL,
  `percent` int(11) NOT NULL DEFAULT 0,
  `pages_read` text NOT NULL,
  `updated_at` datetime NOT NULL,
  `read_count` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reading_progress`
--

INSERT INTO `reading_progress` (`id`, `user_id`, `text_id`, `percent`, `pages_read`, `updated_at`, `read_count`) VALUES
(41, 12, 160, 100, '[0,1]', '2025-07-26 19:18:17', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `hidden_texts`
--
ALTER TABLE `hidden_texts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_text` (`user_id`,`text_id`),
  ADD KEY `text_id` (`text_id`);

--
-- Indices de la tabla `practice_progress`
--
ALTER TABLE `practice_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_practice_progress_text_id` (`text_id`);

--
-- Indices de la tabla `practice_time`
--
ALTER TABLE `practice_time`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_text` (`user_id`,`text_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `hidden_texts`
--
ALTER TABLE `hidden_texts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `practice_progress`
--
ALTER TABLE `practice_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT de la tabla `practice_time`
--
ALTER TABLE `practice_time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `reading_progress`
--
ALTER TABLE `reading_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `hidden_texts`
--
ALTER TABLE `hidden_texts`
  ADD CONSTRAINT `hidden_texts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hidden_texts_ibfk_2` FOREIGN KEY (`text_id`) REFERENCES `texts` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `practice_progress`
--
ALTER TABLE `practice_progress`
  ADD CONSTRAINT `fk_practice_progress_text_id` FOREIGN KEY (`text_id`) REFERENCES `texts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `practice_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `practice_time`
--
ALTER TABLE `practice_time`
  ADD CONSTRAINT `practice_time_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
