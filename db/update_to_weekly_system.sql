-- SQL para cambiar el sistema de mensual a semanal
-- Fecha: 2026-03-01

USE traductor_app;

-- 1. Añadir columna para la semana
ALTER TABLE uso_traducciones ADD COLUMN semana TINYINT AFTER user_id;

-- 2. Eliminar el índice único mensual antiguo
ALTER TABLE uso_traducciones DROP INDEX unique_user_month;

-- 3. Crear el nuevo índice único semanal
ALTER TABLE uso_traducciones ADD UNIQUE KEY unique_user_week (user_id, semana, anio);

-- 4. (Opcional) Limpiar datos antiguos si es necesario
-- DELETE FROM uso_traducciones;
