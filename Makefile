# Database variables
DB_NAME=laravel
DB_USER=sail
DB_PASSWORD=password
DB_HOST=mysql
DB_PORT=3306
DB_CONNECTION=mysql

# Project deployment
deploy:
	@echo "Starting deployment process..."
	make copy-env
	./vendor/bin/sail up -d
	@echo "Installing dependencies..."
	./vendor/bin/sail composer install
	./vendor/bin/sail npm install
	@echo "Generating application key..."
	./vendor/bin/sail artisan key:generate
	@echo "Creating database..."
	make create-database
	@echo "Running migrations..."
	./vendor/bin/sail artisan migrate --force
	@echo "Deployment complete!"

# Copying .env.example to .env
copy-env:
	@echo "Copying .env.example to .env..."
	cp -n .env.example .env
	@echo "Configuring database in .env..."
	# Определение операционной системы для корректной работы sed
	@if [ "$(shell uname)" = "Darwin" ]; then \
		sed -i '' "s/^DB_CONNECTION=.*/DB_CONNECTION=$(DB_CONNECTION)/" .env; \
		sed -i '' "s/^DB_HOST=.*/DB_HOST=$(DB_HOST)/" .env; \
		sed -i '' "s/^DB_PORT=.*/DB_PORT=$(DB_PORT)/" .env; \
		sed -i '' "s/^DB_DATABASE=.*/DB_DATABASE=$(DB_NAME)/" .env; \
		sed -i '' "s/^DB_USERNAME=.*/DB_USERNAME=$(DB_USER)/" .env; \
		sed -i '' "s/^DB_PASSWORD=.*/DB_PASSWORD=$(DB_PASSWORD)/" .env; \
	else \
		sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=$(DB_CONNECTION)/" .env; \
		sed -i "s/^DB_HOST=.*/DB_HOST=$(DB_HOST)/" .env; \
		sed -i "s/^DB_PORT=.*/DB_PORT=$(DB_PORT)/" .env; \
		sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$(DB_NAME)/" .env; \
		sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$(DB_USER)/" .env; \
		sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$(DB_PASSWORD)/" .env; \
	fi
	@echo ".env file configured."

# Database creation
create-database:
	@echo "Creating database $(DB_NAME)..."
	./vendor/bin/sail exec mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS $(DB_NAME);"
	@echo "Database $(DB_NAME) created (or already exists)."
