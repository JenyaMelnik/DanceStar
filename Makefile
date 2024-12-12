# Project deployment
deploy:
	@./scripts/deploy.sh

# Copying .env.example to .env
copy-env:
	@./scripts/copy-env.sh

# Database creation
create-database:
	@./scripts/create-database.sh
