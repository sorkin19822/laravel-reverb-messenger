.PHONY: up down build migrate fresh shell logs

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

key:
	docker compose exec app php artisan key:generate

shell:
	docker compose exec app bash

logs:
	docker compose logs -f

cache-clear:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

npm-install:
	docker run --rm -v $(PWD):/app -w /app node:20-alpine npm install

npm-build:
	docker run --rm -v $(PWD):/app -w /app node:20-alpine npm run build

setup: up
	@echo "Waiting for MySQL to be ready..."
	@sleep 15
	docker compose exec app php artisan key:generate --force
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan storage:link
	@echo "Setup complete! Visit http://localhost:8080"
