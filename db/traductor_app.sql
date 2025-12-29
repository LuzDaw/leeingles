-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-07-2025 a las 20:47:30
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
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(15, 'Fiction - Ficción', 'Textos de ficción, novelas, cuentos', '2025-06-29 19:32:41'),
(16, 'Non-Fiction - No Ficción', 'Textos informativos, artículos, ensayos', '2025-06-29 19:32:41'),
(17, 'News - Noticias', 'Artículos de noticias y actualidad', '2025-06-29 19:32:41'),
(18, 'Technology - Tecnología', 'Textos relacionados con tecnología e informática', '2025-06-29 19:32:41'),
(19, 'Science - Ciencia', 'Artículos científicos y de investigación', '2025-06-29 19:32:41'),
(20, 'History - Historia', 'Textos históricos y documentales', '2025-06-29 19:32:41'),
(21, 'Literature - Literatura', 'Clásicos de la literatura en inglés', '2025-06-29 19:32:41'),
(22, 'Education - Educación', 'Material educativo y de aprendizaje', '2025-06-29 19:32:41'),
(23, 'Travel - Viajes', 'Textos sobre viajes y turismo', '2025-06-29 19:32:41'),
(24, 'Culture - Cultura', 'Textos sobre cultura y sociedad', '2025-06-29 19:32:41'),
(25, 'Business - Negocios', 'Textos sobre negocios y economía', '2025-06-29 19:32:41'),
(26, 'Health - Salud', 'Artículos sobre salud y bienestar', '2025-06-29 19:32:41'),
(27, 'Sports - Deportes', 'Textos sobre deportes y actividad física', '2025-06-29 19:32:41'),
(28, 'Entertainment - Entretenimiento', 'Textos sobre entretenimiento y ocio', '2025-06-29 19:32:41'),
(29, 'Politics - Política', 'Artículos sobre política y gobierno', '2025-06-29 19:32:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `practice_progress`
--

CREATE TABLE `practice_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mode` enum('selection','writing','sentences') NOT NULL,
  `total_words` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `incorrect_answers` int(11) NOT NULL,
  `accuracy` float NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `practice_progress`
--

INSERT INTO `practice_progress` (`id`, `user_id`, `mode`, `total_words`, `correct_answers`, `incorrect_answers`, `accuracy`, `completed_at`) VALUES
(1, 13, 'selection', 8, 8, 5, 100, '2025-06-26 18:28:00'),
(2, 13, 'selection', 8, 8, 1, 100, '2025-06-29 13:51:21'),
(3, 12, 'selection', 8, 8, 19, 100, '2025-07-01 19:50:54'),
(4, 15, 'selection', 8, 8, 17, 100, '2025-07-01 20:04:25'),
(5, 13, 'selection', 8, 8, 3, 100, '2025-07-01 20:25:59'),
(6, 12, 'selection', 8, 8, 1, 100, '2025-07-01 20:37:39'),
(7, 12, 'selection', 8, 8, 4, 100, '2025-07-02 06:49:57'),
(8, 12, 'selection', 8, 8, 0, 100, '2025-07-03 16:31:41'),
(12, 12, 'selection', 1, 1, 0, 100, '2025-07-04 17:39:54');

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
(1, 12, 'selection', 1751399451, '2025-07-01 19:50:56'),
(2, 15, 'selection', 1751400263, '2025-07-01 20:04:27'),
(3, 13, 'selection', 1751401557, '2025-07-01 20:26:01'),
(4, 12, 'selection', 1751402255, '2025-07-01 20:37:37'),
(5, 12, 'selection', 1751438993, '2025-07-02 06:49:55'),
(6, 12, 'selection', 1751560299, '2025-07-03 16:31:44'),
(7, 12, 'selection', 1751560802, '2025-07-03 16:40:06'),
(8, 12, 'selection', 1751580613, '2025-07-03 22:10:15'),
(9, 12, 'selection', 1751650501, '2025-07-04 17:35:05'),
(10, 12, 'selection', 1751650792, '2025-07-04 17:39:56');

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
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reading_progress`
--

INSERT INTO `reading_progress` (`id`, `user_id`, `text_id`, `percent`, `pages_read`, `updated_at`) VALUES
(1, 12, 123, 100, '[0,1]', '2025-07-04 18:53:43'),
(2, 13, 113, 100, '[0]', '2025-07-04 18:22:21'),
(3, 13, 117, 35, '[0]', '2025-07-04 18:23:26'),
(4, 12, 124, 55, '[0,1]', '2025-07-04 18:43:05'),
(5, 12, 116, 100, '[0]', '2025-07-04 19:02:28'),
(6, 17, 125, 100, '[0,1]', '2025-07-04 19:22:11'),
(7, 17, 126, 66, '[0]', '2025-07-04 19:46:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `saved_words`
--

CREATE TABLE `saved_words` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `word` text NOT NULL,
  `translation` text NOT NULL,
  `context` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `text_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `saved_words`
--

INSERT INTO `saved_words` (`id`, `user_id`, `word`, `translation`, `context`, `created_at`, `text_id`) VALUES
(336, 13, 'technology', 'tecnología', 'Despite the advantages, technology also presents challenges.', '2025-06-30 00:41:30', 113),
(337, 13, 'addiction,', 'adicción,', 'Privacy concerns, screen addiction, and the digital divide are just a few of the issues that need ongoing attention.', '2025-06-30 00:41:34', 113),
(380, 12, 'staff', 'personal', 'With the proper tools, hospital staff can get a.', '2025-06-30 11:35:21', NULL),
(381, 13, 'widely-used', 'ampliamente utilizado', 'JavaScript, PHP, and Java are powerful and widely-used languages that offer strong advantages when building applications that work on web, desktop, and mobile platforms.', '2025-06-30 15:43:26', 119),
(382, 13, 'together,', 'juntos,', 'Each language brings unique strengths, and when used together, they allow for a flexible and scalable development process across environments.', '2025-06-30 15:44:40', 119),
(383, 13, 'allow', 'permitir', 'Each language brings unique strengths, and when used together, they allow for a flexible and scalable development process across environments.', '2025-06-30 15:44:47', 119),
(384, 13, 'powerful', 'poderoso', 'JavaScript, PHP, and Java are powerful and widely-used languages that offer strong advantages when building applications that work on web, desktop, and mobile platforms.', '2025-06-30 15:50:29', 119),
(385, 13, 'strong', 'fuerte', 'JavaScript provides real-time interaction, PHP handles server-side logic efficiently, and Java offers a strong structure for mobile and desktop deployment.', '2025-06-30 15:54:10', 119),
(386, 13, 'cross-platform', 'multiplataforma', 'With tools like Electron or Tauri, JavaScript can also be used to create cross-platform desktop apps using web technologies.', '2025-06-30 15:51:26', 119),
(387, 13, 'widely', 'ampliamente', 'Java is widely used in enterprise systems, Android development, and with frameworks like Spring Boot, it can serve complex web backends.', '2025-06-30 15:53:09', 119),
(388, 13, 'enterprise', 'empresa', 'Java is widely used in enterprise systems, Android development, and with frameworks like Spring Boot, it can serve complex web backends.', '2025-06-30 15:53:14', 119),
(389, 13, 'standalone', 'autónomo', 'Java can also support desktop apps using JavaFX or Swing, making it a good option for rich, standalone applications.', '2025-06-30 15:53:38', 119),
(390, 12, 'maintainability.', 'mantenimiento.', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability.', '2025-06-30 16:03:46', 120),
(391, 12, 'seamlessly', 'sin problemas', 'PHP is also easy to deploy on most hosting services and integrates seamlessly with front-end technologies.', '2025-06-30 16:05:58', 120),
(392, 12, 'teams', 'equipos', 'For teams that need to get a server-side backend running quickly with minimal overhead, PHP remains a practical and efficient choice.', '2025-06-30 16:06:03', 120),
(393, 12, 'server-side', 'del lado del servidor', 'For teams that need to get a server-side backend running quickly with minimal overhead, PHP remains a practical and efficient choice.', '2025-06-30 16:06:09', 120),
(394, 12, 'overhead,', 'arriba,', 'For teams that need to get a server-side backend running quickly with minimal overhead, PHP remains a practical and efficient choice.', '2025-07-02 14:38:26', 120),
(395, 12, 'long-term', 'a largo plazo', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability.', '2025-06-30 18:42:47', 120),
(396, 12, 'Together', 'Juntos', 'Title: The Power of JavaScript and PHP Working Together.', '2025-06-30 20:49:10', NULL),
(397, 15, 'application', 'solicitud', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability.', '2025-07-01 20:06:39', 121),
(398, 15, 'careful', 'cuidadoso', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability.', '2025-07-01 20:08:52', 121),
(399, 15, 'ensure', 'asegurar', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability.', '2025-07-01 20:08:55', 121),
(400, 15, 'performance,', 'actuación,', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability.', '2025-07-01 20:08:57', 121),
(401, 13, 'presentation', 'presentación', 'Markup languages are especially important on the client side to define the structure and presentation of the user interface.', '2025-07-01 20:57:13', NULL),
(402, 13, 'web.', 'web.', 'HTML (HyperText Markup Language) is the core language used to structure content on the web.', '2025-07-01 20:57:22', NULL),
(403, 13, 'structure', 'estructura', 'HTML (HyperText Markup Language) is the core language used to structure content on the web.', '2025-07-01 20:57:34', NULL),
(404, 12, 'strategic', 'estratégico', 'Choosing PHP, JavaScript, and Java can be a strategic decision, especially when balancing development speed, platform compatibility, and community support.', '2025-07-01 21:09:31', 120),
(405, 12, 'three', 'tres', 'These three languages cover key areas of application architecture: client-side interaction, server-side logic, and mobile/desktop execution.', '2025-07-01 21:09:41', 120),
(406, 12, 'remains', 'restos', 'For teams that need to get a server-side backend running quickly with minimal overhead, PHP remains a practical and efficient choice.', '2025-07-02 14:38:36', 120),
(407, 12, 'requires', 'requerimiento', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability.', '2025-07-02 14:34:00', 120),
(408, 12, 'further', 'más', 'Tools like Electron and React Native further extend JavaScript’s reach to desktop and mobile apps, enabling code reuse and consistent behavior across devices.', '2025-07-02 14:36:47', 120),
(409, 12, 'reach', 'alcanzar', 'Tools like Electron and React Native further extend JavaScript’s reach to desktop and mobile apps, enabling code reuse and consistent behavior across devices.', '2025-07-02 14:36:55', 120),
(410, 12, 'behavior', 'comportamiento', 'Tools like Electron and React Native further extend JavaScript’s reach to desktop and mobile apps, enabling code reuse and consistent behavior across devices.', '2025-07-02 14:37:05', 120),
(411, 12, 'submissions,', 'presentaciones,', 'It excels at handling data processing, form submissions, and interaction with relational databases such as MySQL.', '2025-07-02 14:37:43', 120),
(412, 12, 'such', 'semejante', 'It excels at handling data processing, form submissions, and interaction with relational databases such as MySQL.', '2025-07-02 14:37:57', 120),
(413, 12, 'as', 'como', 'It excels at handling data processing, form submissions, and interaction with relational databases such as MySQL.', '2025-07-02 14:38:01', 120),
(414, 12, 'interaction', 'interacción', 'It excels at handling data processing, form submissions, and interaction with relational databases such as MySQL.', '2025-07-02 14:57:56', 120),
(415, 12, 'client-side', 'del lado del cliente', 'These three languages cover key areas of application architecture: client-side interaction, server-side logic, and mobile/desktop execution.', '2025-07-02 17:32:05', 120),
(416, 12, 'challenges.', 'desafíos.', 'Despite the advantages, technology also presents challenges.', '2025-07-02 18:49:21', 113),
(417, 13, 'languages', 'lenguas', 'JavaScript, PHP, and Java are powerful and widely-used languages that offer strong advantages when building applications that work on web, desktop, and mobile platforms.', '2025-07-03 19:35:18', 119),
(418, 13, 'strengths,', 'fortalezas,', 'Each language brings unique strengths, and when used together, they allow for a flexible and scalable development process across environments.', '2025-07-03 23:53:45', 119),
(419, 13, 'scalable', 'escalable', 'Each language brings unique strengths, and when used together, they allow for a flexible and scalable development process across environments.', '2025-07-03 23:53:48', 119),
(420, 13, 'building', 'edificio', 'JavaScript, PHP, and Java are powerful and widely-used languages that offer strong advantages when building applications that work on web, desktop, and mobile platforms.', '2025-07-03 23:53:51', 119),
(421, 13, 'browsers', 'navegadores', 'It powers dynamic user interfaces in web browsers and is the foundation of popular frameworks like React, Vue, and Angular.', '2025-07-03 23:53:55', 119),
(422, 12, 'often', 'a menudo', 'In mobile hybrid apps, HTML and CSS are often embedded inside a WebView, allowing the same technologies used for websites to be used for mobile apps as well.', '2025-07-04 17:04:56', NULL),
(423, 12, 'embedded', 'incorporado', 'In mobile hybrid apps, HTML and CSS are often embedded inside a WebView, allowing the same technologies used for websites to be used for mobile apps as well.', '2025-07-04 17:04:59', NULL),
(424, 12, 'WebView,', 'WebView,', 'In mobile hybrid apps, HTML and CSS are often embedded inside a WebView, allowing the same technologies used for websites to be used for mobile apps as well.', '2025-07-04 17:05:05', NULL),
(425, 12, 'inside', 'adentro', 'In mobile hybrid apps, HTML and CSS are often embedded inside a WebView, allowing the same technologies used for websites to be used for mobile apps as well.', '2025-07-04 17:05:09', NULL),
(426, 12, 'across', 'al otro lado de', 'Together with scripting and programming languages, they allow developers to build responsive, interactive, and personalized applications across platforms.', '2025-07-04 17:08:09', NULL),
(427, 12, 'compound.', 'compuesto.', 'But with time, the effects compound.', '2025-07-04 18:14:32', 123),
(428, 13, 'concerns,', 'preocupaciones,', 'Privacy concerns, screen addiction, and the digital divide are just a few of the issues that need ongoing attention.', '2025-07-04 18:22:26', 113),
(429, 13, 'issues', 'asuntos', 'Privacy concerns, screen addiction, and the digital divide are just a few of the issues that need ongoing attention.', '2025-07-04 18:22:30', 113),
(430, 12, 'longer', 'más extenso', 'It creates habits that no longer require motivation, only routine.', '2025-07-04 18:52:49', 123),
(431, 12, 'challenges.', 'desafíos.', 'Despite the advantages, technology also presents challenges.', '2025-07-04 19:02:04', NULL),
(432, 17, 'frameworks,', 'marcos,', 'Java has a mature and active developer community, a vast collection of libraries, frameworks, and tools, and extensive documentation.', '2025-07-04 19:20:01', NULL),
(433, 17, 'Stability', 'Estabilidad', 'High Performance and Stability.', '2025-07-04 19:20:06', NULL),
(434, 17, 'known', 'conocido', 'Java applications are known for their reliability and consistent performance, especially in enterprise and server-side applications.', '2025-07-04 19:20:11', NULL),
(435, 17, 'reliability', 'fiabilidad', 'Java applications are known for their reliability and consistent performance, especially in enterprise and server-side applications.', '2025-07-04 19:20:13', NULL),
(436, 17, 'checks,', 'cheques,', 'Java provides a secure environment with features like bytecode verification, runtime security checks, and an extensive API for cryptography and authentication.', '2025-07-04 19:20:28', NULL),
(437, 17, 'Backward', 'Hacia atrás', 'Backward Compatibility.', '2025-07-04 19:21:59', NULL),
(438, 17, 'Using', 'Usando', 'Advantages of Using SQL Databases.', '2025-07-04 19:45:10', NULL),
(439, 17, 'Storage', 'Almacenamiento', 'Structured Data Storage.', '2025-07-04 19:45:14', NULL),
(440, 17, 'organizing', 'organización', 'SQL databases use a well-defined schema with tables, rows, and columns, which makes organizing and retrieving data straightforward and consistent.', '2025-07-04 19:45:19', NULL),
(441, 17, 'retrieving', 'recuperación', 'SQL databases use a well-defined schema with tables, rows, and columns, which makes organizing and retrieving data straightforward and consistent.', '2025-07-04 19:45:22', NULL),
(442, 17, 'straightforward', 'directo', 'SQL databases use a well-defined schema with tables, rows, and columns, which makes organizing and retrieving data straightforward and consistent.', '2025-07-04 19:45:25', NULL),
(443, 17, 'Powerful', 'Poderoso', 'Powerful Query Language.', '2025-07-04 19:45:31', NULL),
(444, 17, 'querying,', 'Consulta,', 'SQL provides robust commands for querying, filtering, joining, grouping, and aggregating data, enabling precise and complex data manipulation.', '2025-07-04 19:45:37', NULL),
(445, 17, 'filtering,', 'filtración,', 'SQL provides robust commands for querying, filtering, joining, grouping, and aggregating data, enabling precise and complex data manipulation.', '2025-07-04 19:45:39', NULL),
(446, 17, 'joining,', 'unión,', 'SQL provides robust commands for querying, filtering, joining, grouping, and aggregating data, enabling precise and complex data manipulation.', '2025-07-04 19:45:41', NULL),
(447, 17, 'grouping,', 'agrupamiento,', 'SQL provides robust commands for querying, filtering, joining, grouping, and aggregating data, enabling precise and complex data manipulation.', '2025-07-04 19:45:44', NULL),
(448, 17, 'aggregating', 'agregado', 'SQL provides robust commands for querying, filtering, joining, grouping, and aggregating data, enabling precise and complex data manipulation.', '2025-07-04 19:45:46', NULL),
(449, 17, 'Performance', 'Actuación', 'Scalability and Performance.', '2025-07-04 19:45:58', NULL),
(450, 17, 'high', 'alto', 'Modern SQL databases (like MySQL, PostgreSQL, SQL Server) offer indexing, query optimization, and caching to support high performance even with large datasets.', '2025-07-04 19:46:17', NULL),
(451, 17, 'Consistency,', 'Consistencia,', 'SQL databases support Atomicity, Consistency, Isolation, and Durability (ACID), ensuring reliable transactions and preventing data corruption.', '2025-07-04 19:46:32', NULL),
(452, 17, 'preventing', 'prevenir', 'SQL databases support Atomicity, Consistency, Isolation, and Durability (ACID), ensuring reliable transactions and preventing data corruption.', '2025-07-04 19:46:35', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `texts`
--

CREATE TABLE `texts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `texts`
--

INSERT INTO `texts` (`id`, `user_id`, `title`, `content`, `is_public`, `category_id`, `created_at`) VALUES
(113, 13, 'Despite the advantages', 'Despite the advantages, technology also presents challenges. Privacy concerns, screen addiction, and the digital divide are just a few of the issues that need ongoing attention. As we continue to integrate technology into our lives, it is essential to use it responsibly and ensure it benefits everyone.', 1, 25, '2025-06-29 20:55:53'),
(119, 13, 'JavaScript, PHP, and Java', 'JavaScript, PHP, and Java are powerful and widely-used languages that offer strong advantages when building applications that work on web, desktop, and mobile platforms. Each language brings unique strengths, and when used together, they allow for a flexible and scalable development process across environments.\r\n\r\nJavaScript is essential for front-end development. It powers dynamic user interfaces in web browsers and is the foundation of popular frameworks like React, Vue, and Angular. With tools like Electron or Tauri, JavaScript can also be used to create cross-platform desktop apps using web technologies. Additionally, JavaScript can be used for mobile development through frameworks like React Native, enabling code reuse across web and mobile interfaces.\r\n\r\nPHP is a robust back-end language designed for server-side scripting. It handles data processing, authentication, database operations, and generates dynamic web content. PHP integrates well with databases like MySQL and can serve APIs to both desktop and mobile clients. Since it runs on most servers and is easy to deploy, PHP remains a popular and efficient choice for the backend of multi-platform apps.\r\n\r\nJava is a versatile, object-oriented language known for its portability. It runs on the Java Virtual Machine (JVM), making it ideal for building cross-platform applications. Java is widely used in enterprise systems, Android development, and with frameworks like Spring Boot, it can serve complex web backends. Java can also support desktop apps using JavaFX or Swing, making it a good option for rich, standalone applications.\r\n\r\nCombining JavaScript, PHP, and Java allows developers to cover all areas of app development. JavaScript provides real-time interaction, PHP handles server-side logic efficiently, and Java offers a strong structure for mobile and desktop deployment. This combination leads to cost-effective, maintainable, and scalable applications that can run consistently across web browsers, desktop environments, and mobile devices.', 1, 18, '2025-06-30 13:42:55'),
(120, 12, 'Developing a cross-platform', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability. Choosing PHP, JavaScript, and Java can be a strategic decision, especially when balancing development speed, platform compatibility, and community support. These three languages cover key areas of application architecture: client-side interaction, server-side logic, and mobile/desktop execution.\r\n\r\nJavaScript remains the undisputed standard for front-end development. It is supported by all modern browsers and enables interactive, real-time user interfaces. When combined with frameworks like React or Vue, JavaScript allows for rapid UI development across platforms. Tools like Electron and React Native further extend JavaScript’s reach to desktop and mobile apps, enabling code reuse and consistent behavior across devices.\r\n\r\nPHP is a mature, stable, and highly optimized server-side language. It excels at handling data processing, form submissions, and interaction with relational databases such as MySQL. PHP is also easy to deploy on most hosting services and integrates seamlessly with front-end technologies. For teams that need to get a server-side backend running quickly with minimal overhead, PHP remains a practical and efficient choice.\r\n\r\nJava, on the other hand, offers strong performance and type safety, making it a preferred option for large-scale mobile apps (especially Android) and desktop software. With JavaFX and frameworks like Spring Boot, Java can deliver powerful desktop GUIs and robust RESTful APIs. It also brings enterprise-level reliability and has a large ecosystem of tools for testing, security, and performance optimization.\r\n\r\nHowever, developers may also consider alternative stacks based on project goals. For instance, using Node.js (JavaScript runtime) for the backend can simplify the tech stack and allow full-stack JavaScript development. Similarly, Python with Django or Flask is preferred for fast backend development with advanced data handling, while Kotlin is often chosen for modern Android apps due to its concise syntax and Java compatibility.\r\n\r\nIn conclusion, choosing PHP, JavaScript, and Java offers a time-tested and versatile foundation for a cross-platform app, particularly when aiming for wide device support and a modular architecture. However, modern alternatives may offer advantages in developer productivity or performance depending on the app’s specific requirements. The key is to align language choice with the app’s scope, team expertise, and long-term support strategy.', 0, NULL, '2025-06-30 14:01:35'),
(121, 15, 'Developing a cross-platform', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability. Choosing PHP, JavaScript, and Java can be a strategic decision, especially when balancing development speed, platform compatibility, and community support. These three languages cover key areas of application architecture: client-side interaction, server-side logic, and mobile/desktop execution.\r\n\r\nJavaScript remains the undisputed standard for front-end development. It is supported by all modern browsers and enables interactive, real-time user interfaces. When combined with frameworks like React or Vue, JavaScript allows for rapid UI development across platforms. Tools like Electron and React Native further extend JavaScript’s reach to desktop and mobile apps, enabling code reuse and consistent behavior across devices.\r\n\r\nPHP is a mature, stable, and highly optimized server-side language. It excels at handling data processing, form submissions, and interaction with relational databases such as MySQL. PHP is also easy to deploy on most hosting services and integrates seamlessly with front-end technologies. For teams that need to get a server-side backend running quickly with minimal overhead, PHP remains a practical and efficient choice.\r\n\r\nJava, on the other hand, offers strong performance and type safety, making it a preferred option for large-scale mobile apps (especially Android) and desktop software. With JavaFX and frameworks like Spring Boot, Java can deliver powerful desktop GUIs and robust RESTful APIs. It also brings enterprise-level reliability and has a large ecosystem of tools for testing, security, and performance optimization.', 0, NULL, '2025-06-30 20:00:03'),
(122, 16, 'Developing a cross-platform', 'Developing a cross-platform application requires careful selection of technologies that ensure performance, scalability, and long-term maintainability.\r\n\r\nChoosing PHP, JavaScript, and Java can be a strategic decision, especially when balancing development speed, platform compatibility, and community support.\r\n\r\nThese three languages cover key areas of application architecture: client-side interaction, server-side logic, and mobile/desktop execution.\r\n\r\nJavaScript remains the undisputed standard for front-end development.', 0, NULL, '2025-07-01 18:35:04'),
(123, 12, 'The Power of', 'The Power of Consistency\r\n\r\nConsistency is one of the most powerful traits a person can develop. Whether you\'re learning a new language, building a business, or improving your health, steady, repeated action over time produces results that no short burst of effort can match. While talent and intelligence can give you a head start, it is consistency that determines long-term success.\r\n\r\nIn the beginning, progress often feels slow. You might study for weeks without seeing noticeable improvement, or go to the gym without seeing physical changes. But with time, the effects compound. The same way a single drop of water can eventually wear away a rock, small daily actions shape who we become.\r\n\r\nConsistency also builds discipline. Showing up even when you don\'t feel like it strengthens your mental resilience. It creates habits that no longer require motivation, only routine. You stop negotiating with yourself and simply follow the path you have committed to.\r\n\r\nMoreover, consistent behavior builds trust. In relationships, being reliable shows others they can count on you. In work, delivering quality results time after time earns respect. People begin to associate you with excellence, simply because you keep showing up.\r\n\r\nOf course, consistency doesn’t mean perfection. It’s okay to make mistakes or miss a day. What matters is returning to your routine as soon as possible, without losing momentum. Forgive the slip, learn from it, and continue moving forward.', 1, 25, '2025-07-02 16:02:57'),
(124, 12, 'Pros and Cons of Creating', 'Pros and Cons of Creating a Web and Mobile App Using JavaScript, Java, and PHP\r\nDeveloping a web and mobile application involves choosing the right technologies. JavaScript, Java, and PHP are among the most widely used programming languages. Each has its own strengths and weaknesses, depending on the nature and requirements of the project.\r\nJavaScript.Pros:\r\nCross-platform support: JavaScript, especially with frameworks like React Native or Ionic, allows for building both web and mobile applications using the same codebase.\r\nRich ecosystem: There are countless libraries and tools available, accelerating development.\r\nReal-time capabilities: JavaScript is ideal for real-time applications, such as chats or collaborative tools.\r\nLarge community: Extensive support and documentation are available.\r\n\r\nCons:\r\n\r\nPerformance limitations: JavaScript apps can sometimes have lower performance compared to native apps.\r\nSecurity concerns: Being a client-side language, it can be more exposed to certain types of attacks.\r\n\r\nRapidly changing ecosystem: Frequent updates can lead to compatibility issues and maintenance challenges.\r\n\r\nJava.Pros:\r\nStrong for Android development: Java is the official language for Android development and offers native performance.\r\n\r\nRobust and scalable: Java is known for its reliability, strong typing, and scalability for large applications.\r\nCross-platform (via frameworks): Java can be used for web apps with Spring Boot or for mobile apps with Android SDK.\r\n\r\nGood security features: Built-in features help reduce vulnerabilities.\r\nCons:\r\nVerbose syntax: Java code tends to be more verbose, which can slow down development.\r\n\r\nSteeper learning curve: Compared to JavaScript or PHP, Java can be harder for beginners.\r\n\r\nSlower development cycle: Especially when compared with more dynamic languages.\r\n\r\nPHP.Pros:\r\nExcellent for server-side web development: PHP is highly suited for building dynamic websites and integrates well with databases like MySQL.\r\n\r\nWide hosting support: Almost all hosting services support PHP, making deployment easy.\r\n\r\nMature frameworks: Tools like Laravel and Symfony help speed up development and enforce good practices.\r\n\r\nLarge developer base: There is a strong community and vast online resources.\r\n\r\nCons:\r\n\r\nNot designed for mobile apps: PHP is mainly for back-end development and not suitable for creating mobile apps directly.\r\n\r\nInconsistent syntax: Some developers find PHP’s syntax inconsistent and outdated.\r\n\r\nSecurity issues: Poorly written PHP code can lead to vulnerabilities, though this applies to any language.\r\n\r\nConclusion:\r\n\r\nUse JavaScript if you want a unified approach to web and mobile (with frameworks like React or React Native).\r\n\r\nUse Java for Android apps or when performance and scalability are key.\r\n\r\nUse PHP for server-side logic in web apps, but pair it with other technologies (like JavaScript) for the front-end or mobile parts.\r\n\r\nChoosing the right combination depends on your team’s expertise, the app’s goals, and performance requirements.', 1, 18, '2025-07-04 15:34:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`) VALUES
(12, 'Luz S.M', 'luz@idoneoweb.es', '$2y$10$.Kn4zK9UgJThQ7oAUuFLr.j.0SoO5pLBrTTbcyXO7.86FU.xMPiqW', 1),
(13, 'Lola', 'lo@idioma.es', '$2y$10$duhA4CPe.B5YlMqb18Ln4.ItyhHURsU1uee6l7JyrTAJfSnK2pmrO', 0),
(15, 'Pilar', 'pilar@jdk.com', '$2y$10$XSm0kVJ/PwFoX4rbIEzK9O9B9FosbTPFb/3gYtQ2b0OqU0jmvv6/y', 0),
(16, 'Luci', 'msiguenza29@GMAIL.COM', '$2y$10$Rp/SftQzNT15rkAlC9fJ6uLe5fldtId64hMYwzzYeVSuZSRIm46HK', 0),
(17, 'admin', 'admin@gmail.com', '$2y$10$5oaw0wq13Z5KZGmvxbOiPuYya7dWzbE8YI2pZ2CbYwaIiOg0YSsN6', 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `practice_progress`
--
ALTER TABLE `practice_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indices de la tabla `saved_words`
--
ALTER TABLE `saved_words`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_saved_words_text_id` (`text_id`);

--
-- Indices de la tabla `texts`
--
ALTER TABLE `texts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_category` (`category_id`);

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
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `practice_progress`
--
ALTER TABLE `practice_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `practice_time`
--
ALTER TABLE `practice_time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `reading_progress`
--
ALTER TABLE `reading_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `saved_words`
--
ALTER TABLE `saved_words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=453;

--
-- AUTO_INCREMENT de la tabla `texts`
--
ALTER TABLE `texts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `practice_progress`
--
ALTER TABLE `practice_progress`
  ADD CONSTRAINT `practice_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `practice_time`
--
ALTER TABLE `practice_time`
  ADD CONSTRAINT `practice_time_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `saved_words`
--
ALTER TABLE `saved_words`
  ADD CONSTRAINT `fk_saved_words_text_id` FOREIGN KEY (`text_id`) REFERENCES `texts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `saved_words_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `texts`
--
ALTER TABLE `texts`
  ADD CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `texts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
