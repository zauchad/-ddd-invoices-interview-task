# Invoice Module - DDD Modular Monolith

Symfony 7 + Doctrine with **true DDD** - domain entities have zero framework dependencies.

## Quick Start

```bash
make up              # Start containers
make install         # Install dependencies
make migrate         # Run migrations
make ci              # Run full CI pipeline
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

## Commands

### Testing

```bash
make test              # All tests
make test-unit         # Unit tests (no DB, no framework)
make test-integration  # Integration tests (with DB)
make test-functional   # HTTP endpoint tests
make test-e2e          # Full workflow tests
```

### Static Analysis

```bash
make phpstan           # PHPStan level 9
make psalm             # Psalm level 1
make deptrac           # Architecture constraints
make analyse           # All analysis tools
```

### Code Style

```bash
make cs-check          # Check style
make cs-fix            # Fix style
```

### CI Pipeline

```bash
make ci                # cs-check → analyse → test
```

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
