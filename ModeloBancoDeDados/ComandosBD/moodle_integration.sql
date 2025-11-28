-- Adicionar campos para integração com Moodle

ALTER TABLE users 
ADD COLUMN moodle_token VARCHAR(255) NULL,
ADD COLUMN moodle_userid INT NULL,
ADD COLUMN moodle_username VARCHAR(100) NULL,
ADD COLUMN last_moodle_sync TIMESTAMP NULL;

ALTER TABLE tasks 
ADD COLUMN moodle_id INT NULL,
ADD COLUMN moodle_course_id INT NULL,
ADD COLUMN is_from_moodle BOOLEAN DEFAULT FALSE,
ADD COLUMN moodle_sync_at TIMESTAMP NULL;

-- Adicionar índice para melhor performance
CREATE INDEX idx_moodle_id ON tasks(moodle_id);
CREATE INDEX idx_is_from_moodle ON tasks(is_from_moodle);
