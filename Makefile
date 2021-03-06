.PHONY: all composer build config deploy rollback deploy-db rollback-db

all: build composer ## Default task to do build.

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(lastword $(MAKEFILE_LIST)) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: ## Build web-front.
	cd assets && npm install && npm run build
	cd assets && bower install --allow-root

composer: ## Install composer packages without dev tools.
	composer install --no-dev --optimize-autoloader

composer-dev: ## Install all composer packages.
	composer update

run-docker: ## Run web app with Docker.
	docker run -d --name ridi-intranet -p 8000:80 -v `pwd`:/var/www/html --env-file .env ridibooks/intranet:latest

build-docker: build composer ## Build a Docker image. (ridibooks/intranet)
	docker build -t ridibooks/intranet:latest .

deploy: ## Deploy codes with Deployer.
ifndef stage
	$(eval stage := $(shell read -p "Enter Deployer stage: " REPLY; echo $$REPLY))
endif
	vendor/bin/dep --file=docs/deployer/deploy.php deploy $(stage) -p

rollback: ## Rollback with Deployer.
ifndef stage
	$(eval stage := $(shell read -p "Enter Deployer stage: " REPLY; echo $$REPLY))
endif
	vendor/bin/dep --file=docs/deployer/deploy.php rollback $(stage) -p

deploy-db: ## Migrate DB with Phinx
ifndef env
	$(eval env := $(shell read -p "Enter Phinx environment: " REPLY; echo $$REPLY))
endif
	vendor/bin/phinx migrate -e $(env)

rollback-db: ## Rollback DB with Phinx
ifndef env
	$(eval env := $(shell read -p "Enter Phinx environment: " REPLY; echo $$REPLY))
endif
	vendor/bin/phinx rollback -e $(env)

