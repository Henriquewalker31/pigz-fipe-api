# PIGZ - API de Veículos e Tabela FIPE

API REST em Symfony 7.4 com CRUD de veículos, CRUD da Tabela FIPE (modelos e histórico de preços),
níveis de usuário (admin / user) e comparação do preço anunciado com a FIPE.

## Rodando com Docker (recomendado)

    docker-compose up -d

A API sobe em http://localhost:8080 (o primeiro boot leva cerca de 1 minuto).
Admin inicial: admin@pigz.com / admin123. Os dados de demonstração são criados automaticamente.

## Rodando com XAMPP

Requisitos: PHP 8.2+ com as extensões intl, pdo_mysql, mbstring e zip, Composer e MySQL/MariaDB ligado.

    composer install
    php bin/console doctrine:database:create --if-not-exists
    php bin/console doctrine:migrations:migrate -n
    php bin/console app:create-admin admin@pigz.com admin123
    php bin/console app:seed-demo
    php -S localhost:8080 -t public

## Níveis de usuário

- user: consulta veículos disponíveis, Tabela FIPE e comparações.
- admin: tudo do user, mais o CRUD de veículos, FIPE e usuários.

## Endpoints

| Método | Rota | Nível | Descrição |
|---|---|---|---|
| POST | /api/auth/login | público | Gera o token Bearer (8h) |
| POST | /api/auth/logout | usuário | Revoga o token |
| GET | /api/me | usuário | Dados do usuário logado |
| GET / POST | /api/users | admin | Lista / cadastra usuários |
| GET | /api/vehicles | usuário | Disponíveis + fipe_comparison (filtros: brand, model, fuel, year_min, year_max, price_min, price_max, sort, page, limit; status=sold/reserved/all só para admin) |
| GET | /api/vehicles/{id} | usuário | Detalhe com comparação |
| GET | /api/vehicles/{id}/fipe-comparison | usuário | Comparação, resumo e histórico mês a mês |
| POST / PUT / DELETE | /api/vehicles[/{id}] | admin | CRUD de veículos |
| GET | /api/fipe/models[/{id}] | usuário | Consulta a Tabela FIPE |
| POST / PUT / DELETE | /api/fipe/models[/{id}] | admin | CRUD de modelos FIPE |
| GET | /api/fipe/models/{id}/prices | usuário | Histórico de preços |
| POST / PUT / DELETE | /api/fipe/models/{id}/prices[/{priceId}] | admin | CRUD do histórico |

## Decisões técnicas

- Comparação calculada em centavos inteiros, sem erro de ponto flutuante.
- A listagem busca o último preço FIPE de todos os veículos da página em uma única query (sem N+1).
- Tolerância de 2% para classificar como "na média da FIPE".
- Ano 32000 = zero km, seguindo a convenção da FIPE.
- O ano modelo do anúncio precisa bater com o ano do modelo FIPE vinculado.
- Schema sem colunas JSON: roda igual no MariaDB (XAMPP) e no MySQL 8 (Docker).