-- Tabla para almacenar el progreso de práctica
CREATE TABLE IF NOT EXISTS practice_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    text_id INT,
    mode VARCHAR(50) NOT NULL, -- 'selection', 'writing', 'sentences'
    total_words INT NOT NULL,
    correct_answers INT NOT NULL,
    incorrect_answers INT NOT NULL,
    accuracy DECIMAL(5,2) NOT NULL, -- Porcentaje de aciertos
    session_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (text_id) REFERENCES texts(id) ON DELETE CASCADE
);

-- Índices para mejorar el rendimiento de las consultas
CREATE INDEX idx_practice_progress_user ON practice_progress(user_id);
CREATE INDEX idx_practice_progress_mode ON practice_progress(mode);
CREATE INDEX idx_practice_progress_date ON practice_progress(session_date); 