# Invoice Module Implementation

## 1. Architectural Overview

This project implements a **Modular Monolith** architecture, treating the `Invoices` module as a bounded context with strict separation of concerns. The design follows **Domain-Driven Design (DDD)** principles to ensure the business logic remains pure, testable, and decoupled from the framework infrastructure.

### Layered Structure

*   **Domain Layer** (`src/Modules/Invoices/Domain`):
    *   **Role**: The "Core". Contains Entities (`Invoice`, `ProductLine`), Enums, and Repository Contracts.
    *   **Reasoning**: This layer contains the *business truth*. It has zero dependencies on the HTTP layer or external services. Validation logic (e.g., "An invoice must be draft to be sent") lives here, ensuring that an invoice can never be in an invalid state regardless of where it is called from.
    
*   **Application Layer** (`src/Modules/Invoices/Application`):
    *   **Role**: The "Orchestrator". Contains Services (`InvoiceService`) and Event Listeners.
    *   **Reasoning**: Services orchestrate the flow of data between the Domain and Infrastructure. They do not contain business rules; they simply tell the Domain objects to perform actions and then persist the result. This keeps the service thin and easily testable.

*   **Infrastructure Layer** (`src/Modules/Invoices/Infrastructure`):
    *   **Role**: The "Plumbing". Contains Repository Implementations (`EloquentInvoiceRepository`) and Service Providers.
    *   **Reasoning**: By hiding Eloquent implementation details behind an interface (`InvoiceRepositoryInterface`), we adhere to the **Dependency Inversion Principle**. This allows us to mock persistence easily in Unit Tests and potentially swap storage mechanisms without touching business logic.

*   **Presentation Layer** (`src/Modules/Invoices/Presentation`):
    *   **Role**: The "Interface". Contains Controllers and API Resources.
    *   **Reasoning**: Controllers are strictly "adapters" that convert HTTP requests into DTOs and delegate to the Application layer. They contain no business logic.

---

## 2. Key Design Decisions

### A. Rich Domain Model vs. Anemic Model
Instead of treating the `Invoice` model as a simple data container, I implemented a **Rich Domain Model**.
*   **Decision**: Methods like `markAsSending()` and `markAsSentToClient()` encapsulate state transitions and invariant checks.
*   **Benefit**: This prevents "leaky abstractions" where business rules are scattered across Services or Controllers. If you hold an `Invoice` object, you can be sure it obeys the rules of the domain.

### B. Data Transfer Objects (DTOs)
I introduced `CreateInvoiceDto` and `InvoiceProductLineDto` to handle data ingest.
*   **Decision**: Replaced loose associative arrays (e.g., `$request->all()`) with typed objects.
*   **Benefit**: This acts as a strict contract for the Service Layer. It eliminates "Primitive Obsession" and ensures the Service never has to guess the shape of its input data. It creates self-documenting code that is safer to refactor.

### C. Repository Pattern with Aggregate Persistence
The `Invoice` is treated as an **Aggregate Root**.
*   **Decision**: The `save(Invoice $invoice)` method in the repository handles persisting strictly the Invoice *and* its product lines transactionally.
*   **Benefit**: The Service doesn't need to know *how* to save related lines (Active Record pattern details). It simply hands the Aggregate to the Repository. This also solves complex testing issues where unit tests fail due to missing database connections.

### D. Testing Strategy
A multi-level testing strategy was employed to eliminate the need for manual QA:
*   **Unit Tests**: Focus on the Domain Logic (invariants) and Service orchestration (mocking external dependencies). They run fast and cover edge cases.
*   **Feature/E2E Tests**: Cover the full HTTP lifecycle, database persistence, and event handling. These ensure the system works as a cohesive whole.

---

## 3. Future Improvements (Production Readiness)

In a production environment, I would further enhance the robustness with:
*   **Static Analysis**: Integration of **PHPStan** (Level 8+) to enforce type safety at compile time.
*   **Architectural Linting**: Using **Deptrac** to strictly enforce that the Domain layer never depends on Infrastructure or Presentation layers.
*   **Code Style**: **PHP-CS-Fixer** to maintain consistent formatting across the team.
*   **Event Sourcing**: For a financial system like Invoicing, moving to an Event Sourced model would provide a perfect audit log of *what* happened and *why* (e.g., `InvoiceDrafted`, `InvoiceSent`, `InvoiceDelivered`).

---

## 4. Setup & Verification

### Run the Application
```bash
./start.sh
```

### Run the Test Suite
The project comes with a complete suite of automated tests proving functionality.
```bash
docker compose exec app php artisan test
```
