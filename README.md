# PIGZ — API de Veículos e Tabela FIPE

[![Smoke test](https://github.com/Henriquewalker31/pigz-fipe-api/actions/workflows/docker-smoke-test.yml/badge.svg)](https://github.com/Henriquewalker31/pigz-fipe-api/actions/workflows/docker-smoke-test.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)

API REST em **Symfony 7.4** para gerenciamento de veículos e consulta/comparação com a **Tabela FIPE**. Inclui autenticação por token, níveis de acesso (admin e user), CRUD de veículos, CRUD de modelos FIPE com histórico de preços e comparação entre o valor anunciado e a referência FIPE.

## Funcionalidades

- Autenticação por Bearer token com validade de 8 horas (revogável no logout).
- Dois níveis de acesso: `user` (consulta) e `admin` (gestão).
- CRUD de veículos com filtros por marca, modelo, combustível, ano, preço, ordenação e paginação.
- CRUD de modelos FIPE e histórico de preços mês a mês.
- Comparação do preço do anúncio com o último valor FIPE, com resumo e histórico.
- Seed de dados de demonstração e criação automática do admin no primeiro boot.
- Migration versionada e banco compatível com MySQL 8 e MariaDB.

## Stack

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.2+ |
| Framework | Symfony 7.4 |
| ORM | Doctrine ORM 3 + Migrations |
| Banco | MySQL 8 / MariaDB |
| Servidor | Nginx + PHP-FPM |
| Infra | Docker e Docker Compose |
| CI | GitHub Actions (smoke test do container) |

## Como rodar

### Docker (recomendado)

Pré-requisitos: **Docker** e **Docker Compose**.

```bash
git clone https://github.com/Henriquewalker31/pigz-fipe-api.git
cd pigz-fipe-api
docker compose up -d --build
```

A API sobe em <http://localhost:8080>. O primeiro boot leva cerca de um minuto: o entrypoint instala as dependências, aguarda o banco, roda as migrations, cria o admin e popula os dados de demonstração.

Credenciais iniciais:

```
admin@pigz.com / admin123
```

> Troque essas credenciais antes de qualquer uso fora do ambiente local.

### XAMPP / ambiente local

Requisitos: PHP 8.2+ com `intl`, `pdo_mysql`, `mbstring` e `zip`; Composer; MySQL ou MariaDB ligado.

```bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
php bin/console app:create-admin admin@pigz.com admin123
php bin/console app:seed-demo
php -S localhost:8080 -t public
```

## Autenticação

Faça login para obter o token:

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@pigz.com","password":"admin123"}'
```

Resposta:

```json
{
  "token": "…",
  "token_type": "Bearer",
  "expires_at": "2026-11-17T03:00:00+00:00"
}
```

Envie o token nas demais requisições:

```bash
curl http://localhost:8080/api/vehicles \
  -H "Authorization: Bearer SEU_TOKEN"
```

## Endpoints

| Método | Rota | Nível | Descrição |
|---|---|---|---|
| `POST` | `/api/auth/login` | público | Gera o token Bearer (8h) |
| `POST` | `/api/auth/logout` | usuário | Revoga o token |
| `GET` | `/api/me` | usuário | Dados do usuário logado |
| `GET` / `POST` | `/api/users` | admin | Lista / cadastra usuários |
| `GET` | `/api/vehicles` | usuário | Disponíveis + comparação FIPE |
| `GET` | `/api/vehicles/{id}` | usuário | Detalhe com comparação |
| `GET` | `/api/vehicles/{id}/fipe-comparison` | usuário | Comparação, resumo e histórico |
| `POST` / `PUT` / `DELETE` | `/api/vehicles[/{id}]` | admin | CRUD de veículos |
| `GET` | `/api/fipe/models[/{id}]` | usuário | Consulta a Tabela FIPE |
| `POST` / `PUT` / `DELETE` | `/api/fipe/models[/{id}]` | admin | CRUD de modelos FIPE |
| `GET` | `/api/fipe/models/{id}/prices` | usuário | Histórico de preços |
| `POST` / `PUT` / `DELETE` | `/api/fipe/models/{id}/prices[/{priceId}]` | admin | CRUD do histórico |

Filtros da listagem de veículos: `brand`, `model`, `fuel`, `year_min`, `year_max`, `price_min`, `price_max`, `sort`, `page` e `limit`. O parâmetro `status` (`sold`, `reserved`, `all`) é exclusivo para admin.

Exemplos prontos de requisições estão em [`api.http`](api.http), compatíveis com a extensão REST Client do VS Code.

## Estrutura do projeto

```
pigz-fipe-api/
├── bin/console
├── config/                     # bundles, rotas, segurança e Doctrine
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile + entrypoint.sh
├── migrations/                 # schema versionado
├── public/index.php
├── src/
│   ├── Command/                # create-admin e seed-demo
│   ├── Controller/             # Auth, Vehicle, Fipe e User
│   ├── Dto/                    # payloads validados
│   ├── Entity/                 # User, Vehicle, FipeModel, FipePrice, ApiToken
│   ├── Repository/
│   ├── Security/               # handler do Bearer token
│   └── Service/FipeComparator.php
├── docker-compose.yml
└── api.http
```

## Configuração

Variáveis usadas pelo container (definidas em `docker-compose.yml`):

| Variável | Padrão | Descrição |
|---|---|---|
| `APP_ENV` | `dev` | Ambiente do Symfony |
| `DATABASE_URL` | `mysql://app:app@database:3306/fipe` | Conexão com o banco |
| `ADMIN_EMAIL` | `admin@pigz.com` | E-mail do admin criado no boot |
| `ADMIN_PASSWORD` | `admin123` | Senha do admin criado no boot |
| `SEED_DEMO` | `1` | Popula dados de demonstração quando `1` |

Os arquivos `.env` e `.env.dev` seguem a convenção do Symfony (valores padrão, sem segredos de produção). Use `.env.local` para sobrescrever configurações na sua máquina — ele está no `.gitignore`.

## Decisões técnicas

- Comparação calculada em centavos inteiros, sem erro de ponto flutuante.
- A listagem busca o último preço FIPE de todos os veículos da página em uma única query (sem N+1).
- Tolerância de 2% para classificar como "na média da FIPE".
- Ano `32000` representa zero km, seguindo a convenção da FIPE.
- O ano modelo do anúncio precisa bater com o ano do modelo FIPE vinculado.
- Schema sem colunas JSON: roda igual no MariaDB (XAMPP) e no MySQL 8 (Docker).

## Qualidade e limitações

O CI executa um smoke test: sobe os containers e valida o login em `POST /api/auth/login`. Não há suíte de testes unitários nem de integração além disso.

Este é um projeto de portfólio. Para produção, vale adicionar:

- Testes automatizados dos controllers e do `FipeComparator`.
- Rate limiting no login e rotação de tokens.
- Senhas e `APP_SECRET` injetados por secret manager.
- HTTPS obrigatório e CORS restrito.

## Autor

Feito por [Henrique Walker](https://github.com/Henriquewalker31).
