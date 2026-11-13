#!/bin/sh
set -e
cd /var/www/app

# O cache gerado no Windows (XAMPP) tem caminhos C:\... e quebra no Linux
rm -rf var/cache/*

if [ ! -f vendor/autoload_runtime.php ]; then
  composer install --prefer-dist --no-interaction --no-progress
fi

echo "Aguardando o banco de dados..."
until php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; do
  sleep 2
done

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:create-admin "${ADMIN_EMAIL}" "${ADMIN_PASSWORD}"

if [ "${SEED_DEMO}" = "1" ]; then
  php bin/console app:seed-demo
fi

mkdir -p var && chown -R www-data:www-data var
exec "$@"