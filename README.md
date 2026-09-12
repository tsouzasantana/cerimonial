# Cerimonial — Gestão de Contratos de Cerimonial

Sistema de gestão para empresas de cerimonial: cadastro de clientes, contratos,
serviços contratados, controle de parcelas de pagamento, histórico de
ocorrências, documentos (com inativação/recuperação) e geração/envio do
contrato padrão em PDF.

Construído em **Laravel** (PHP), pensado para rodar em hospedagem
compartilhada (cPanel) com banco de dados **MySQL**.

## Funcionalidades

- **Clientes**: cadastro completo (documento, contato, endereço), inativação
  (soft delete) com possibilidade de reativação.
- **Serviços**: catálogo de serviços de cerimonial (nome, código, valor),
  usado para compor os itens de um contrato.
- **Contratos de cerimonial**: dados do evento, status, itens/serviços
  contratados (com recálculo automático de subtotal/desconto/total),
  parcelas de pagamento e histórico de ocorrências.
- **Ocorrências**: histórico do andamento do contrato (ex: "Orçamento
  enviado", "Contrato assinado"); cada tipo pode alterar automaticamente o
  status do contrato ao ser registrado.
- **Documentos**: upload vinculado a cliente e/ou contrato. Ao "excluir" um
  documento ele é apenas **inativado** (soft delete) e pode ser recuperado
  depois — o arquivo nunca é apagado do disco.
- **PDF do contrato**: gera a minuta do contrato padrão em PDF (via
  `barryvdh/laravel-dompdf`, 100% PHP — não exige nenhum binário extra,
  funciona em hospedagem compartilhada) para impressão e assinatura fora do
  sistema.
- **E-mail**: envio do PDF do contrato e de documentos avulsos por e-mail
  diretamente para o cliente.
- **Usuário único**: sistema pensado para um único usuário administrador (sem
  tela pública de cadastro); crie outros usuários manualmente se precisar.

> **Aviso legal:** o modelo de contrato gerado em `resources/views/pdf/contract.blade.php`
> traz uma estrutura básica de cláusulas. Revise e ajuste o texto com um
> advogado antes de usar em produção.

## Requisitos

- PHP 8.3 ou superior (com extensões padrão do Laravel: `pdo_mysql`, `mbstring`, `gd`/`dom` para o PDF)
- MySQL 5.7+/MariaDB 10.3+ em produção (SQLite é usado por padrão em desenvolvimento)
- Composer
- Node.js + npm (apenas para compilar os assets do Tailwind; não é necessário em produção após o build)

## Configuração local

```bash
composer install
cp .env.example .env
php artisan key:generate
# ajuste DB_* no .env (ou deixe sqlite para testar rapidamente)
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

O seeder cria:

- Um usuário administrador com os dados de `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` do `.env` (padrão: `admin@example.com` / `password` — **troque a senha em produção**).
- Tipos de ocorrência básicos (Orçamento enviado, Contrato assinado, Evento realizado, Cancelamento).
- Alguns serviços de exemplo do cerimonial.

## Deploy em hospedagem compartilhada (cPanel)

1. **Banco de dados**: crie um banco MySQL e um usuário com todos os
   privilégios via cPanel → MySQL Databases.
2. **Envio dos arquivos**: envie todo o projeto para uma pasta **fora** de
   `public_html` (ex: `~/cerimonial`), mantendo a pasta `public/` como única
   parte exposta.
   - Se o provedor permitir apontar o domínio/subdomínio diretamente para uma
     pasta customizada, aponte o *document root* para `~/cerimonial/public`.
   - Caso não seja possível alterar o document root, copie o conteúdo de
     `public/` para `public_html/` e edite `public_html/index.php`
     ajustando os `require` para os caminhos corretos de `../cerimonial/vendor/autoload.php`
     e `../cerimonial/bootstrap/app.php`.
3. **Dependências**: se o cPanel tiver acesso SSH/Composer, rode
   `composer install --optimize-autoloader --no-dev` dentro da pasta do
   projeto. Caso não haja SSH, gere a pasta `vendor/` localmente com esse
   mesmo comando e envie via FTP.
4. **Ambiente**: copie `.env.example` para `.env` no servidor e preencha:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` com o domínio real.
   - `APP_KEY`: gere localmente com `php artisan key:generate --show` e cole o valor.
   - `DB_*` com os dados do banco MySQL criado no passo 1.
   - `MAIL_*` com os dados de SMTP fornecidos pelo cPanel (ou outro provedor de e-mail).
   - `ADMIN_*` com os dados do usuário administrador antes de rodar o seeder.
   - `COMPANY_*` com os dados da empresa exibidos no PDF do contrato.
5. **Migrações e usuário admin**:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```
6. **Permissões**: garanta que `storage/` e `bootstrap/cache/` tenham
   permissão de escrita pelo usuário do PHP (geralmente `755`/`775`).
7. **Assets**: rode `npm run build` localmente (ou em qualquer máquina com
   Node) e envie a pasta `public/build` gerada — não é necessário Node no
   servidor de produção.
8. **Cache de produção** (opcional, recomendado):
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   Sempre que alterar o `.env` ou rotas, rode `php artisan config:clear` /
   `route:clear` antes de gerar o cache novamente.

### Armazenamento de documentos

Os documentos enviados (RG, comprovantes, contratos assinados digitalizados
etc.) ficam em `storage/app/private` (disco `local`), **fora** da pasta
pública — não são acessíveis diretamente por URL, apenas pelas rotas
autenticadas de download do sistema. Garanta backups periódicos dessa pasta
junto com o banco de dados.

## Testes

```bash
php artisan test
```

## Estrutura de domínio (resumo)

| Módulo | Descrição |
| --- | --- |
| `clients` | Cadastro de clientes |
| `services` | Catálogo de serviços de cerimonial |
| `contracts` | Contratos, com subtotal/desconto/total recalculados a partir dos itens |
| `contract_items` | Itens (serviços) de cada contrato |
| `installments` | Parcelas de pagamento de cada contrato |
| `document_files` | Documentos anexados a clientes/contratos (soft delete) |
| `occurrence_types` | Tipos de ocorrência configuráveis, podendo alterar o status do contrato |
| `occurrences` | Histórico de ocorrências de cada contrato |
