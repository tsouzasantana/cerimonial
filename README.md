# Cerimonial — Gestão de Contratos de Cerimonial

Sistema de gestão para empresas de cerimonial: cadastro de clientes, contratos,
serviços contratados, controle de parcelas de pagamento, histórico de
ocorrências, documentos (com inativação/recuperação) e geração/envio do
contrato padrão em PDF.

Construído em **Laravel** (PHP), pensado para rodar em hospedagem
compartilhada (cPanel) com banco de dados **MySQL**.

## Funcionalidades

- **Clientes**: cadastro completo (documento, contato, endereço), inativação
  (soft delete) com possibilidade de reativação. Vários campos têm máscara e
  validação:
  - **CPF/CNPJ**: formatado enquanto digita e validado no servidor pelo
    dígito verificador oficial (rejeita sequências como 111.111.111-11),
    tanto no cadastro de cliente quanto no de fornecedor.
  - **Telefone**: formatado como `(00) 00000-0000` e validado (DDD válido,
    celular com 9 dígitos começando em 9).
  - **CEP**: formatado como `00000-000`, validado no servidor e, ao sair do
    campo, busca o endereço (rua, bairro, cidade, UF) via API ViaCEP e
    preenche os demais campos automaticamente.
  - **UF**: lista suspensa com os 27 estados, em vez de texto livre.
  - **Data de nascimento**: não aceita datas futuras nem anteriores a 1900.
  - **Valores em R$** (preço de serviço, desconto do contrato, parcelas):
    campo com máscara de milhar/centavos (ex: "R$ 1.500,00") enquanto digita.
- **Serviços**: catálogo de serviços de cerimonial (nome, código, valor),
  usado para compor os itens de um contrato.
- **Contratos de cerimonial**: dados do evento, status, itens/serviços
  contratados (com recálculo automático de subtotal/desconto/total),
  parcelas de pagamento e histórico de ocorrências.
- **Ocorrências**: histórico do andamento do contrato (ex: "Orçamento
  enviado", "Contrato assinado"); cada tipo pode alterar automaticamente o
  status do contrato ao ser registrado.
- **Documentos**: upload de arquivo **ou** link de documento na nuvem (Google
  Drive, Dropbox etc.), vinculado a cliente e/ou contrato, organizado por
  **tipo configurável** (contrato assinado, documento pessoal, inspiração,
  contrato de outro fornecedor, etc. — editável em Configurações → Tipos de
  Documento). Ao "excluir" um documento ele é apenas **inativado** (soft
  delete) e pode ser recuperado depois — o arquivo nunca é apagado do disco.
- **PDF do contrato**: gera a minuta do contrato padrão em PDF (via
  `barryvdh/laravel-dompdf`, 100% PHP — não exige nenhum binário extra,
  funciona em hospedagem compartilhada) para impressão e assinatura fora do
  sistema.
- **E-mail**: envio do PDF do contrato e de documentos avulsos por e-mail
  diretamente para o cliente.
- **Checklist do evento**: lista de tarefas com prazo, status (a iniciar / em
  andamento / concluída / cancelada) e observações, aplicada automaticamente
  a cada novo contrato a partir de um **checklist padrão editável**
  (Configurações → Checklist Padrão), com o prazo de cada tarefa calculado em
  dias antes (ou depois) da data do evento. A tela do checklist mostra um
  painel com o total de tarefas, atrasadas e progresso, além de filtro por
  status e ordenação por coluna; tarefas podem ser adicionadas ou inativadas
  tanto pela equipe quanto pelo cliente. Ao mudar a data do evento de um
  contrato com checklist já aplicado, o sistema pergunta se deseja manter os
  prazos originais ou deslocar todos pela mesma diferença de dias.
- **Fornecedores**: cadastro de fornecedores por contrato (nome, CPF/CNPJ,
  tipo de serviço prestado — lista **configurável** em Configurações → Tipos
  de Serviço de Fornecedor, com opção de digitar um tipo novo na hora),
  status do serviço (a prestar / prestado / cancelado), situação de
  pagamento (não pago / pago parcialmente / pago integralmente), observações
  e anexo de um ou mais documentos (contratos, comprovantes, propostas —
  reaproveitando o mesmo sistema de documentos com tipo configurável).
  Fornecedores podem ser cadastrados, editados e inativados tanto pela
  equipe quanto pelo cliente.
- **Portal público do cliente**: cada contrato tem um link único (sem login)
  onde o cliente confirma o CPF cadastrado e pode acompanhar o contrato,
  gerenciar o checklist (status, prazo, observações, adicionar/inativar
  tarefas) e os fornecedores (cadastrar, atualizar status/pagamento/
  observações, inativar), e enviar documentos — sem poder alterar dados do
  contrato, itens, parcelas, o nome/tipo de fornecedores já cadastrados, nem
  documentos já existentes. O link pode ser regenerado a qualquer momento
  pela tela do contrato, invalidando o anterior.
- **Relatórios**: dashboard gerencial com indicadores financeiros (recebido,
  a receber, parcelas em atraso, **fluxo de caixa projetado para os próximos
  6 meses** com total acumulado), de contratos (por status, novos por mês,
  próximos eventos), de checklist (tarefas atrasadas, taxa de conclusão) e
  de clientes (novos cadastros por mês).
- **Parcelas em lote**: além de lançar parcelas uma a uma, é possível gerar
  várias de uma vez informando valor total, quantidade, primeiro vencimento
  e intervalo em meses — o sistema divide o valor igualmente (ajustando
  centavos de arredondamento na última parcela) e continua a numeração a
  partir da última parcela existente.
- **Calendário de eventos**: visão mensal (sem dependência de biblioteca de
  calendário) com a data de evento de cada contrato, navegação entre meses e
  link direto para o contrato.
- **Auditoria**: toda criação, edição, inativação e restauração de contratos,
  tarefas do checklist, fornecedores, parcelas, ocorrências, documentos e
  clientes fica registrada (quem fez — equipe ou cliente pelo portal público
  — e o que mudou, com valor anterior e novo de cada campo), disponível na
  aba "Atividades" de cada contrato e numa página global de Auditoria com
  filtros por contrato, autor, ação e período. Uma edição registrada pode ser
  **revertida** com um clique, o que gera por sua vez um novo registro de
  auditoria ("revertido"). Contratos, tarefas do checklist e fornecedores
  também exibem quem fez a última atualização diretamente na tela.
- **Notificação de atividade do cliente**: sempre que o cliente altera algo
  pelo portal público (checklist, fornecedores ou documentos), a equipe
  recebe um e-mail com o resumo da alteração.
- **Busca global**: campo de busca no topo de todas as telas, procurando
  simultaneamente por clientes (nome, CPF/CNPJ, e-mail, telefone), contratos
  (número, nome do cliente, local do evento) e fornecedores (nome,
  CPF/CNPJ); registros inativados não aparecem nos resultados.
- **Usuário único**: sistema pensado para um único usuário administrador (sem
  tela pública de cadastro); crie outros usuários manualmente se precisar.

> **Aviso legal:** o modelo de contrato gerado em `resources/views/pdf/contract.blade.php`
> traz uma estrutura básica de cláusulas. Revise e ajuste o texto com um
> advogado antes de usar em produção.

## Marca (Maria Casamenteira Assessoria)

O sistema está personalizado com a identidade visual da Maria Casamenteira:

- **Logo**: os arquivos ficam em `public/images/` — `logo.png` (logo completa, usada
  no cabeçalho de todas as telas e no PDF do contrato) e `logo-icon.png` (apenas o
  emblema, usado como favicon). Para trocar a logo no futuro, basta substituir esses
  dois arquivos (mantendo os mesmos nomes) e regenerar os ícones de favicon a partir
  de `logo-icon.png` nos tamanhos em `public/images/favicon-*.png`,
  `public/images/apple-touch-icon.png` e `public/favicon.ico`.
- **Cores**: a paleta bordô extraída da logo está definida em `tailwind.config.js`
  (cores `brand.50` a `brand.900`). Toda a interface usa essa escala no lugar da cor
  padrão do Breeze — para ajustar o tom, basta editar os valores lá e rodar
  `npm run build` novamente.
- **Nome da empresa**: definido por `COMPANY_NAME` no `.env` (usado no PDF, e-mails e
  título das páginas).

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

1. **Banco de dados**: crie um banco MySQL e um usuário via cPanel → MySQL
   Databases. Conceda apenas os privilégios que a aplicação realmente usa —
   `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES`
   — em vez de "todos os privilégios" (`ALL PRIVILEGES`). Isso cobre as
   consultas do Eloquent e a execução de `migrate`, sem conceder permissões
   administrativas como `GRANT OPTION`.
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
   - Se o banco MySQL estiver em um host remoto (fora do mesmo servidor da
     aplicação), defina `MYSQL_ATTR_SSL_CA` com o caminho do certificado CA
     do provedor para cifrar a conexão via TLS (a verificação do certificado
     do servidor é habilitada automaticamente quando essa variável está
     definida). Não é necessário em bancos no mesmo servidor (conexão via
     `localhost`/socket), cenário comum em hospedagem cPanel.
   - `MAIL_*` com os dados de SMTP fornecidos pelo cPanel (ou outro provedor de e-mail).
   - `ADMIN_*` com os dados do usuário administrador antes de rodar o seeder.
   - `COMPANY_*` com os dados da empresa exibidos no PDF do contrato.
     `COMPANY_EMAIL` também é usado como destinatário das notificações de
     atividade do cliente; se ficar em branco, a notificação é enviada para
     o e-mail de todos os usuários administradores cadastrados.
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
pública — não são acessíveis diretamente por URL, apenas pelas rotas de
download do sistema (autenticadas para a equipe, ou pelo portal público do
cliente após confirmar o CPF do contrato). O comando de backup abaixo
inclui os documentos junto com o banco de dados.

### Backup automatizado do banco de dados

O comando `php artisan db:backup` gera um dump compactado (`.gz`) do banco
em `storage/app/private/backups`:

```bash
php artisan db:backup             # mantém backups dos últimos 30 dias (padrão)
php artisan db:backup --keep-days=7   # ajusta a retenção
```

- Em SQLite (desenvolvimento), copia o arquivo do banco.
- Em MySQL/MariaDB (produção), usa `mysqldump` (precisa estar disponível no
  `PATH` do servidor).
- Backups mais antigos que `--keep-days` são removidos automaticamente a
  cada execução.

Esse comando já está agendado para rodar diariamente via
`routes/console.php` (`Schedule::command('db:backup')->daily()`). Para que o
agendador do Laravel funcione, adicione uma única entrada de cron no cPanel
(Cron Jobs) apontando para o `artisan` do projeto, executada a cada minuto:

```
* * * * * php /home/SEU_USUARIO/cerimonial/artisan schedule:run >> /dev/null 2>&1
```

O Laravel decide internamente quando cada tarefa agendada (como o backup
diário) deve realmente rodar — não é necessário criar um cron por tarefa.
Como os documentos enviados ficam fora do dump do banco, inclua também a
pasta `storage/app/private` numa rotina de backup de arquivos (ex: backup
de conta completo do próprio cPanel).

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
| `document_types` | Tipos de documento configuráveis (contrato assinado, inspiração, contrato de fornecedor etc.) |
| `document_files` | Documentos anexados a clientes/contratos/fornecedores — upload de arquivo OU link na nuvem (soft delete) |
| `occurrence_types` | Tipos de ocorrência configuráveis, podendo alterar o status do contrato |
| `occurrences` | Histórico de ocorrências de cada contrato |
| `checklist_templates` | Checklist padrão editável, com prazo em dias antes/depois do evento |
| `contract_tasks` | Tarefas do checklist de cada contrato (copiadas do template ao criar o contrato) |
| `vendor_service_types` | Tipos de serviço de fornecedor configuráveis (buffet, decoração, etc.) |
| `vendors` | Fornecedores de cada contrato, com status de serviço e de pagamento |

`contracts.public_token` guarda o token do link público de cada contrato
(rota `portal/{token}`), usado pelo cliente para acessar o checklist e enviar
documentos sem login, após confirmar o CPF cadastrado.
