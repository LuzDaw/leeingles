-- SQL para implementar el sistema de suscripción y conteo de traducciones
-- Fecha: 2026-03-01

-- 1. Añadir campos de control a la tabla de usuarios
-- fecha_registro: para determinar el fin del mes gratuito
-- tipo_usuario: gratis (mes inicial), limitado (500/mes), premium (ilimitado)
ALTER TABLE users 
ADD COLUMN fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN tipo_usuario ENUM('gratis', 'limitado', 'premium') DEFAULT 'gratis';

-- 2. Crear tabla para el conteo mensual de traducciones
-- Esta tabla permite llevar un registro histórico y actual del uso por usuario
CREATE TABLE IF NOT EXISTS uso_traducciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mes TINYINT NOT NULL, -- 1 a 12
    anio SMALLINT NOT NULL,
    contador INT DEFAULT 0,
    ultima_traduccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (user_id, mes, anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
