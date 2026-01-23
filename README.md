# Invoice Module - DDD Modular Monolith

Symfony 7 + Doctrine implementation with **true DDD** - domain entities have zero framework dependencies.

## Architecture

```
src/Modules/Invoices/
├── Domain/           # Pure PHP - NO framework deps
├── Application/      # Use case orchestration  
├── Infrastructure/   # Doctrine, external services
├── Presentation/     # Symfony controllers
└── Api/              # Public DTOs & interfaces
```

### Key Principle

Domain entities are pure PHP objects - no Eloquent, no Doctrine annotations:

```php
final class Invoice
{
    public static function create(string $name, string $email): self { ... }
    public function markAsSending(): void { ... }  // Business rules here
}
```

Persistence via external XML mapping in Infrastructure layer.

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/invoices` | Create draft invoice |
| GET | `/api/invoices/{id}` | View invoice |
| POST | `/api/invoices/{id}/send` | Send invoice |
| GET | `/api/notification/hook/delivered/{id}` | Delivery webhook |

## Setup

```bash
./start.sh
```

## Tests

```bash
docker compose exec app php bin/phpunit
```
