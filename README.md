# Invoice Module Implementation

## 1. Architectural Overview

This project implements a **Modular Monolith** architecture with **true Domain-Driven Design (DDD)** principles using **Symfony and Doctrine**.

### Key DDD Principle: Infrastructure Independence

**The Domain layer has ZERO dependencies on infrastructure.**

Unlike many "DDD" implementations that have domain entities extending ORM base classes (violating DDD's core principle), this implementation uses:
- **Pure PHP domain entities** - No Eloquent, no Doctrine annotations, no framework base classes
- **External mapping** - Doctrine XML mapping keeps persistence concerns in Infrastructure
- **Proper layer separation** - Domain can be tested without ANY framework dependencies

### Layered Structure

```
src/Modules/Invoices/
├── Domain/           # Pure PHP - NO framework dependencies
│   ├── Models/       # Aggregate Root (Invoice) and Entities
│   ├── Enums/        # Value Objects and Enums
│   ├── Exceptions/   # Domain-specific exceptions
│   └── Repositories/ # Repository INTERFACES only
│
├── Application/      # Orchestration layer
│   ├── Services/     # Use case orchestration
│   └── Listeners/    # Event handlers
│
├── Infrastructure/   # Framework integration
│   ├── Persistence/  # Doctrine XML mappings
│   └── Repositories/ # Repository implementations
│
├── Presentation/     # HTTP interface
│   └── Http/         # Symfony controllers
│
└── Api/              # Public module contracts
    └── Dtos/         # Data Transfer Objects
```

### Layer Responsibilities

| Layer | Depends On | Contains |
|-------|------------|----------|
| **Domain** | Nothing (pure PHP) | Entities, Value Objects, Repository Interfaces, Domain Events |
| **Application** | Domain | Services, Event Listeners, DTOs |
| **Infrastructure** | Domain, Application | Repository Implementations, ORM Mappings, External Services |
| **Presentation** | Application | Controllers, Request/Response handling |

---

## 2. Key Design Decisions

### A. Pure Domain Entities (TRUE DDD)

```php
// Domain entity - NO framework dependencies
class Invoice
{
    private UuidInterface $id;
    private StatusEnum $status;
    // ... pure PHP with business logic
    
    public function markAsSending(): void
    {
        if ($this->status !== StatusEnum::Draft) {
            throw InvalidInvoiceStateException::mustBeDraft($this->status);
        }
        // Business rules enforced here
    }
}
```

The `Invoice` class:
- Has NO `extends Model`, NO `extends Entity`, NO annotations
- Contains all business rules (state transitions, validations)
- Can be instantiated and tested without ANY framework

### B. External Doctrine Mapping

Persistence is configured via XML in Infrastructure layer:

```xml
<!-- Infrastructure/Persistence/Mapping/Invoice.orm.xml -->
<entity name="Modules\Invoices\Domain\Models\Invoice" table="invoices">
    <id name="id" type="uuid"/>
    <field name="customerName" column="customer_name"/>
    <!-- Domain knows nothing about this -->
</entity>
```

### C. Repository Pattern

```php
// Domain defines the CONTRACT
interface InvoiceRepositoryInterface
{
    public function find(string $id): ?Invoice;
    public function save(Invoice $invoice): void;
}

// Infrastructure provides the IMPLEMENTATION
class DoctrineInvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}
    
    public function save(Invoice $invoice): void
    {
        $this->em->persist($invoice);
        $this->em->flush();
    }
}
```

### D. Module Communication via Facades

Modules communicate through explicit public APIs:

```php
// NotificationFacadeInterface - public contract
interface NotificationFacadeInterface
{
    public function notify(NotifyData $data): void;
}
```

---

## 3. Testing Strategy

### Unit Tests (No Framework Required!)

```php
class InvoiceTest extends TestCase
{
    public function test_mark_as_sending_validates_state(): void
    {
        // Pure PHP - no database, no framework boot
        $invoice = Invoice::create('John', 'john@example.com');
        $invoice->addProductLine('Item', 1, 100);
        
        $invoice->markAsSending();
        
        $this->assertEquals(StatusEnum::Sending, $invoice->getStatus());
    }
}
```

### Integration Tests

Feature tests use Symfony's WebTestCase with database transactions.

---

## 4. Setup & Verification

### Run the Application

```bash
./start.sh
```

### Run the Test Suite

```bash
docker compose exec app php bin/phpunit
```

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/invoices` | Create draft invoice |
| GET | `/api/invoices/{id}` | View invoice |
| POST | `/api/invoices/{id}/send` | Send invoice |
| GET | `/api/notification/hook/delivered/{id}` | Delivery webhook |

---

## 5. Why This Matters

Many "DDD" implementations actually violate DDD principles by having domain entities extend ORM classes. This creates:

- **Tight coupling** - Domain depends on infrastructure
- **Testing difficulty** - Need database/framework to test business logic
- **Leaky abstractions** - ORM concerns bleed into domain

This implementation demonstrates **proper DDD** where:
- Domain is truly independent
- Business logic is testable in isolation
- Framework is an implementation detail, not a foundation
