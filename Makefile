.PHONY: up down build setup seed logs

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

setup: up
	@echo "Waiting for MySQL..."
	@sleep 8
	docker compose exec -u root php chown -R www-data:www-data /var/www/backend/storage /var/www/backend/bootstrap/cache
	docker compose exec php php artisan key:generate --force
	docker compose exec php php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction
	docker compose exec php php artisan filament:assets
	docker compose exec php php artisan migrate --seed --force
	docker compose exec php php artisan storage:link
	@echo "Done! Frontend: http://localhost:5173 | API: http://localhost:8080/api/v1 | Admin: http://localhost:8080/admin"

seed:
	docker compose exec php php artisan db:seed --force

logs:
	docker compose logs -f
