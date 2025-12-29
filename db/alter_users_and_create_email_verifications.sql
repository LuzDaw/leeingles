-- SQL para modificar la tabla `users` y crear `verificaciones_email`
-- Fecha: 2025-12-20 (Corregido)

-- Añadir columnas a la tabla `users`
ALTER TABLE `users`
ADD COLUMN `estado` ENUM('pendiente', 'activo', 'bloqueado') NOT NULL DEFAULT 'pendiente' AFTER `is_admin`,
ADD COLUMN `email_verificado_en` DATETIME NULL DEFAULT NULL AFTER `estado`;

-- Crear la tabla `verificaciones_email`
CREATE TABLE `verificaciones_email` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `id_usuario` INT(11) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `expira_en` DATETIME NOT NULL,
    `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `tipo` ENUM('email_verification', 'password_reset') NOT NULL DEFAULT 'email_verification',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_token_hash` (`token_hash`),
    FOREIGN KEY (`id_usuario`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
