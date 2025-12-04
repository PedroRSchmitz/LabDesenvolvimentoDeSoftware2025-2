-- Adicionar campo de disciplina/matéria nas tarefas
ALTER TABLE tasks ADD COLUMN subject VARCHAR(100) AFTER category_id;

-- Adicionar índice para melhorar performance de ordenação
CREATE INDEX idx_tasks_subject ON tasks(subject);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
