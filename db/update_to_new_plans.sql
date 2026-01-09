-- SQL para actualizar el sistema de suscripciones a los nuevos planes comerciales
-- Fecha: 2026-01-09

-- 1. Crear tabla para el control detallado de suscripciones y tiempos
CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_name ENUM('Inicio', 'Ahorro', 'Pro') NOT NULL,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME NOT NULL,
    paypal_subscription_id VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Modificar la tabla users para reflejar los nuevos nombres de planes
-- Primero actualizamos los datos existentes para que no fallen al cambiar el ENUM
UPDATE users SET tipo_usuario = 'gratis' WHERE tipo_usuario IS NULL;

-- Cambiamos el ENUM (Temporalmente permitimos los antiguos para la transición)
ALTER TABLE users MODIFY COLUMN tipo_usuario ENUM('gratis', 'limitado', 'premium', 'EnPrueba', 'Inicio', 'Ahorro', 'Pro') DEFAULT 'EnPrueba';

-- Migramos los datos de los estados antiguos a los nuevos
UPDATE users SET tipo_usuario = 'EnPrueba' WHERE tipo_usuario = 'gratis';
UPDATE users SET tipo_usuario = 'Pro' WHERE tipo_usuario = 'premium';

-- Ahora dejamos el ENUM definitivo
ALTER TABLE users MODIFY COLUMN tipo_usuario ENUM('EnPrueba', 'limitado', 'Inicio', 'Ahorro', 'Pro') DEFAULT 'EnPrueba';
