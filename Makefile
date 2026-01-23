.PHONY: help install test analyse ci

help: ## Show help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install

# Tests
test: ## Run all tests
	composer test

test-unit: ## Run unit tests only
	composer test:unit

test-integration: ## Run integration tests only
	composer test:integration

test-functional: ## Run functional tests only
	composer test:functional

test-e2e: ## Run E2E tests only
	composer test:e2e

# Static Analysis
phpstan: ## Run PHPStan
	composer phpstan

psalm: ## Run Psalm
	composer psalm

deptrac: ## Run Deptrac architecture checks
	composer deptrac

analyse: ## Run all static analysis tools
	composer analyse

# Code Style
cs-fix: ## Fix code style
	composer cs-fix

cs-check: ## Check code style
	composer cs-check

# CI
ci: ## Run full CI pipeline
	composer ci

# Docker
up: ## Start Docker containers
	docker compose up -d

down: ## Stop Docker containers
	docker compose down

logs: ## Show Docker logs
	docker compose logs -f

shell: ## Open shell in app container
	docker compose exec app bash

# Database
migrate: ## Run migrations
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

migrate-diff: ## Generate migration diff
	docker compose exec app php bin/console doctrine:migrations:diff
