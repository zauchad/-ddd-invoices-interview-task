# Invoice Module - DDD Modular Monolith

Symfony 7 + Doctrine with **true DDD** - domain entities have zero framework dependencies.

## Quick Start

```bash
# Start application
./start.sh

# Or manually:
docker compose up -d
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

## Run Commands

All commands run inside Docker:

```bash
# Testing
docker compose exec app composer test
docker compose exec app composer test:unit
docker compose exec app composer test:integration
docker compose exec app composer test:functional
docker compose exec app composer test:e2e

# Analysis
docker compose exec app composer phpstan
docker compose exec app composer psalm
docker compose exec app composer deptrac
docker compose exec app composer analyse

# Code Style
docker compose exec app composer cs-check
docker compose exec app composer cs-fix

# Full CI
docker compose exec app composer ci
```

## Architecture

```
src/Modules/Invoices/
├── Domain/           # Pure PHP - NO framework dependencies
├── Application/      # Use case orchestration  
├── Infrastructure/   # Doctrine, external services
├── Presentation/     # Symfony controllers
└── Api/              # Public DTOs & interfaces
```

### Layer Rules (enforced by Deptrac)

| Layer | Can Depend On |
|-------|---------------|
| Domain | Nothing (pure PHP) |
| Application | Domain, Api |
| Infrastructure | Domain, Application |
| Presentation | Application, Api, Domain |
| Api | Nothing |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/invoices` | Create draft invoice |
| GET | `/api/invoices/{id}` | View invoice |
| POST | `/api/invoices/{id}/send` | Send invoice |
| GET | `/api/notification/hook/delivered/{id}` | Delivery webhook |

## Test Structure

```
tests/
├── Unit/           # Pure logic, no framework, no DB
├── Integration/    # Repository + DB tests
├── Functional/     # HTTP endpoint tests
└── E2E/            # Complete workflow tests
```
