# Set absolut pass for mysql volumes in docker-compose.yml
set-absolut-pass:
	@./scripts/set-absolut-pass.sh

# Project deployment
deploy:
	@./scripts/deploy.sh

# Copying .env.example to .env
copy-env:
	@./scripts/copy-env.sh
