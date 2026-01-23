# Invoice Module - DDD Modular Monolith

Modern PHP application built with **Symfony 7.2 + Doctrine ORM**, following **Domain-Driven Design** principles with strict architectural boundaries.

## Architecture Overview

### Core Principles

- **Pure Domain Layer**: Domain entities are Plain Old PHP Objects (POPOs) with zero framework dependencies
- **Modular Monolith**: Bounded contexts (Invoices, Notifications) with clear module boundaries
- **Hexagonal Architecture**: Domain at the center, infrastructure at the edges
- **Dependency Inversion**: Infrastructure depends on Domain, never the reverse

### Layer Structure

```
src/Modules/Invoices/
├── Domain/              # Pure business logic (POPOs)
│   ├── Models/         # Aggregate roots & entities
│   ├── Enums/          # Value objects
│   ├── Exceptions/     # Domain-specific exceptions
│   └── Repositories/   # Repository interfaces
├── Application/        # Use case orchestration
│   ├── Services/       # Application services
│   └── Listeners/      # Event listeners
├── Infrastructure/     # Framework & external concerns
│   ├── Repositories/   # Doctrine implementations
│   ├── Persistence/    # ORM mappings (XML)
│   └── Providers/      # Service providers
├── Presentation/       # HTTP layer
│   └── Http/          # Symfony controllers
└── Api/                # Public contracts
    ├── Dtos/          # Data Transfer Objects
    ├── Events/        # Domain events
    └── Interfaces/    # Facades
```

### Architectural Constraints

Enforced by **Deptrac**:

| Layer | Depends On | Cannot Access |
|-------|------------|---------------|
| **Domain** | Nothing (except ramsey/uuid) | Application, Infrastructure, Presentation |
| **Application** | Domain, Api | Infrastructure, Presentation |
| **Infrastructure** | Domain, Application, Api | Presentation |
| **Presentation** | Application, Api, Domain | Infrastructure |
| **Api** | Nothing | Everything |

### Domain-Driven Design Features

- **Aggregate Roots**: `Invoice` manages `InvoiceProductLine` children
- **Factory Methods**: Static `create()` methods for entity construction
- **Rich Domain Model**: Business logic encapsulated in entities (e.g., `markAsSending()`)
- **Domain Events**: `ResourceDeliveredEvent` for cross-module communication
- **Value Objects**: `StatusEnum` for type-safe status handling
- **Domain Exceptions**: Specific exceptions for business rule violations

### Persistence Strategy

- **Doctrine XML Mapping**: Keeps domain entities free from ORM annotations
- **Repository Pattern**: Interfaces in Domain, implementations in Infrastructure
- **Unit of Work**: Doctrine manages transactions and change tracking

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Setup (Unix/Linux/Mac)

```bash
# Clone and start
git clone <repository-url>
cd interview-task

# Start application
docker compose up -d --build

# Install dependencies & run migrations
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Verify setup
docker compose exec app composer test:unit
```

### Setup (Windows PowerShell)

```powershell
# Clone and start
git clone <repository-url>
cd interview-task

# Start application
docker compose up -d --build

# Install dependencies & run migrations
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Verify setup
docker compose exec app composer test:unit
```

## Development

### Running Tests

```bash
# All tests
docker compose exec app composer test

# By suite
docker compose exec app composer test:unit          # Domain logic only
docker compose exec app composer test:integration   # Repository + DB
docker compose exec app composer test:functional    # HTTP endpoints
docker compose exec app composer test:e2e          # Full workflows
```

### Static Analysis

```bash
# PHPStan (level 9)
docker compose exec app composer phpstan

# Psalm (level 1)
docker compose exec app composer psalm

# Deptrac (architectural boundaries)
docker compose exec app composer deptrac

# All analysis tools
docker compose exec app composer analyse
```

### Code Quality

```bash
# Check code style
docker compose exec app composer cs-check

# Fix code style
docker compose exec app composer cs-fix

# Full CI pipeline (style + analysis + tests)
docker compose exec app composer ci
```

### Database Management

```bash
# Create new migration
docker compose exec app php bin/console doctrine:migrations:generate

# Run migrations
docker compose exec app php bin/console doctrine:migrations:migrate

# Check migration status
docker compose exec app php bin/console doctrine:migrations:status
```

## API Endpoints

### Invoices

| Method | Endpoint | Description | Request Body |
|--------|----------|-------------|--------------|
| `POST` | `/api/invoices` | Create draft invoice | `{ "customer_name": "...", "customer_email": "...", "product_lines": [...] }` |
| `GET` | `/api/invoices/{id}` | View invoice details | - |
| `POST` | `/api/invoices/{id}/send` | Send invoice to customer | - |

### Notifications (Webhooks)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/notification/hook/delivered/{id}` | Delivery confirmation webhook |

### Example Usage

```bash
# Create invoice
curl -X POST http://localhost:8080/api/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "product_lines": [
      { "name": "Product 1", "quantity": 2, "price": 1000 },
      { "name": "Product 2", "quantity": 1, "price": 2500 }
    ]
  }'

# Get invoice
curl http://localhost:8080/api/invoices/{uuid}

# Send invoice
curl -X POST http://localhost:8080/api/invoices/{uuid}/send
```

## Project Structure

```
interview-task/
├── config/                    # Symfony configuration
│   ├── packages/             # Bundle configs
│   │   └── test/            # Test-specific config
│   ├── routes.yaml          # Routing (attribute-based)
│   └── services.yaml        # Service container
├── migrations/               # Doctrine migrations
├── public/                   # Web server document root
├── src/
│   ├── App/                 # Application kernel
│   └── Modules/             # Business modules
│       ├── Invoices/        # Invoice bounded context
│       └── Notifications/   # Notification bounded context
├── tests/
│   ├── Unit/                # Pure domain logic tests
│   ├── Integration/         # Repository + DB tests
│   ├── Functional/          # HTTP endpoint tests
│   └── E2E/                 # Full workflow tests
├── var/                      # Generated files (cache, logs)
├── composer.json            # PHP dependencies
├── deptrac.yaml            # Architecture constraints
├── phpstan.neon            # Static analysis config
├── psalm.xml               # Psalm config
└── phpunit.xml             # Test configuration
```

## Testing Strategy

### Unit Tests
- **Focus**: Pure domain logic
- **Dependencies**: None (no framework, no database)
- **Example**: `Invoice::markAsSending()` validation rules

### Integration Tests
- **Focus**: Repository operations with real database
- **Dependencies**: Doctrine + Test database
- **Example**: Persisting and retrieving invoices

### Functional Tests
- **Focus**: HTTP endpoints and request/response handling
- **Dependencies**: Symfony HTTP kernel + Database
- **Example**: POST `/api/invoices` returns 201 with correct JSON

### E2E Tests
- **Focus**: Complete business workflows
- **Dependencies**: Full application stack
- **Example**: Create invoice → Send → Receive webhook → Verify status

## Technology Stack

- **PHP**: 8.4 with modern features (readonly properties, constructor promotion, attributes)
- **Framework**: Symfony 7.2 (FrameworkBundle, Runtime, Validator, Serializer)
- **ORM**: Doctrine 3.3 with XML mapping
- **Database**: PostgreSQL 16
- **Testing**: PHPUnit 11 with DAMA Doctrine Test Bundle
- **Analysis**: PHPStan (level 9), Psalm (level 1), Deptrac
- **Code Style**: PHP-CS-Fixer

## Key Design Decisions

### Why Pure Domain Entities?

- Domain logic can be tested without framework overhead
- Business rules remain stable regardless of framework changes
- Clear separation between "what" (domain) and "how" (infrastructure)

### Why XML Mapping Over Annotations?

- Keeps domain entities free from ORM-specific metadata
- Enforces strict DDD: domain knows nothing about persistence
- Easier to refactor domain without breaking persistence layer

### Why Repository Pattern?

- Abstracts data access behind domain interfaces
- Enables testing with in-memory implementations
- Allows switching persistence mechanisms without domain changes

### Why Modular Monolith?

- Module boundaries prevent coupling between business contexts
- Easier to extract into microservices if needed
- Simpler deployment and development than distributed systems

## Troubleshooting

### Container won't start
```bash
docker compose down
docker compose up -d --build
```

### Database connection errors
```bash
docker compose exec app php bin/console doctrine:database:create
docker compose exec app php bin/console doctrine:migrations:migrate
```

### Permission errors (Linux)
```bash
sudo chown -R $USER:$USER var/
chmod -R 775 var/
```

### Tests failing
```bash
# Clear cache
docker compose exec app rm -rf var/cache/*

# Recreate test database
docker compose exec app php bin/console doctrine:database:drop --force --env=test
docker compose exec app php bin/console doctrine:database:create --env=test
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction --env=test
```
