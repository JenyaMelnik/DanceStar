# Project deployment
deploy:
	@./scripts/deploy.sh

# Copying .env.example to .env
copy-env:
	@./scripts/copy-env.sh

# Run terminal inside php container
terminal:
	@./scripts/terminal.sh

# Run Pint inside php container
pint:
	@./scripts/pint.sh
