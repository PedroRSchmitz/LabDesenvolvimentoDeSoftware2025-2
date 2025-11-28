# Sistema Gerenciador de Tarefas

Sistema completo de gerenciamento de tarefas com PHP, MySQL e Bootstrap.

## Funcionalidades

- ✅ Sistema de login e registro com sessões seguras
- ✅ CRUD completo de tarefas
- ✅ Categorização de tarefas com cores personalizadas
- ✅ Upload e gerenciamento de anexos
- ✅ Três status: Fazendo, Feito, Cancelado
- ✅ Visualização em lista e calendário
- ✅ Filtros por status e categoria
- ✅ Design responsivo com Bootstrap 5
- ✅ Botão flutuante para adicionar tarefas

## Instalação

### Requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache ou Nginx
- Extensões PHP: PDO, PDO_MySQL

### Passos

1. **Clone ou baixe o projeto**

2. **Configurar banco de dados**
   - Abra `config/database.php`
   - Ajuste as credenciais (host, usuário, senha)
   - Execute o arquivo `database/setup.sql` no MySQL

3. **Criar pasta de uploads**
   \`\`\`bash
   mkdir uploads
   chmod 777 uploads
   \`\`\`

4. **Configurar permissões**
   \`\`\`bash
   chmod 755 -R .
   chmod 777 uploads
   \`\`\`

5. **Acessar o sistema**
   - Abra no navegador: `http://localhost/task_manager`
   - Crie uma conta na página de registro
   - Faça login e comece a usar!

## Estrutura do Projeto

\`\`\`
task_manager/
├── config/
│   ├── database.php      # Configuração do banco
│   └── session.php       # Gerenciamento de sessões
├── database/
│   └── setup.sql         # Script de criação do banco
├── actions/
│   ├── update_status.php # Atualizar status da tarefa
│   ├── delete_task.php   # Deletar tarefa
│   ├── delete_attachment.php
│   └── delete_category.php
├── assets/
│   └── css/
│       └── style.css     # Estilos customizados
├── includes/
│   └── navbar.php        # Menu de navegação
├── uploads/              # Pasta de arquivos
├── login.php             # Página de login
├── register.php          # Página de registro
├── dashboard.php         # Lista de tarefas
├── calendar.php          # Visualização em calendário
├── add_task.php          # Adicionar tarefa
├── edit_task.php         # Editar tarefa
├── view_task.php         # Visualizar detalhes
├── categories.php        # Gerenciar categorias
└── logout.php            # Sair do sistema
\`\`\`

## Uso

### Criar uma tarefa
1. Clique no botão flutuante "Adicionar Tarefa"
2. Preencha título, categoria, descrição e data
3. Adicione arquivos (opcional)
4. Clique em "Salvar Tarefa"

### Gerenciar tarefas
- **Marcar como feito**: Clique no ícone ✓
- **Cancelar**: Clique no ícone ✗
- **Editar**: Clique no ícone de lápis
- **Ver detalhes**: Clique no ícone de olho

### Filtrar tarefas
- Use os filtros de status e categoria
- No calendário, navegue pelos meses

### Categorias
- Acesse "Categorias" no menu
- Adicione categorias com nome e cor
- Delete categorias não utilizadas

## Segurança

- Senhas criptografadas com `password_hash()`
- Proteção contra SQL Injection com PDO
- Validação de sessões em todas as páginas
- Verificação de propriedade de tarefas
- Upload seguro de arquivos

## Customização

### Alterar cores
Edite o arquivo `assets/css/style.css`:
\`\`\`css
:root {
    --primary-color: #007bff;
    --success-color: #28a745;
    --danger-color: #dc3545;
}
\`\`\`

### Limites de upload
Edite o arquivo `.htaccess`:
\`\`\`
php_value upload_max_filesize 10M
php_value post_max_size 10M
\`\`\`

## Suporte

Para problemas comuns:
- **Erro de conexão**: Verifique `config/database.php`
- **Upload não funciona**: Verifique permissões da pasta `uploads/`
- **Sessão expira**: Ajuste configurações PHP de sessão

## Licença

Projeto livre para uso pessoal e comercial.
