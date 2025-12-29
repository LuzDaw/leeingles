-- Agregar campo email a la tabla users
ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE;

-- Crear usuario administrador
INSERT INTO users (username, email, password, is_admin) 
VALUES ('Luz S.M', 'luz@gmail.es', '$2y$10$YourHashedPasswordHere', 1);
