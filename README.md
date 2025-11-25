## Invoice Structure:

The invoice should contain the following fields:
* **Invoice ID**: Auto-generated during creation.
* **Invoice Status**: Possible states include `draft,` `sending,` and `sent-to-client`.
* **Customer Name** 
* **Customer Email** 
* **Invoice Product Lines**, each with:
  * **Product Name**
  * **Quantity**: Integer, must be positive. 
  * **Unit Price**: Integer, must be positive.
  * **Total Unit Price**: Calculated as Quantity x Unit Price. 
* **Total Price**: Sum of all Total Unit Prices.

## Required Endpoints:

1. **View Invoice**: Retrieve invoice data in the format above.
2. **Create Invoice**: Initialize a new invoice.
3. **Send Invoice**: Handle the sending of an invoice.

## Functional Requirements:

### Invoice Criteria:

* An invoice can only be created in `draft` status. 
* An invoice can be created with empty product lines. 
* An invoice can only be sent if it is in `draft` status. 
* An invoice can only be marked as `sent-to-client` if its current status is `sending`. 
* To be sent, an invoice must contain product lines with both quantity and unit price as positive integers greater than **zero**.

### Invoice Sending Workflow:

* **Send an email notification** to the customer using the `NotificationFacade`. 
  * The email's subject and message may be hardcoded or customized as needed. 
  * Change the **Invoice Status** to `sending` after sending the notification.

### Delivery:

* Upon successful delivery by the Dummy notification provider:
  * The **Notification Module** triggers a `ResourceDeliveredEvent` via webhook.
  * The **Invoice Module** listens for and captures this event.
  * The **Invoice Status** is updated from `sending` to `sent-to-client`.
  * **Note**: This transition requires that the invoice is currently in the `sending` status.

## Technical Requirements:

* **Preferred Approach**: Domain-Driven Design (DDD) is preferred for this project. If you have experience with DDD, please feel free to apply this methodology. However, if you are more comfortable with another approach, you may choose an alternative structure.
* **Alternative Submission**: If you have a different, comparable project or task that showcases your skills, you may submit that instead of creating this task.
* **Unit Tests**: Core invoice logic should be unit tested. Testing the returned values from endpoints is not required.
* **Documentation**: Candidates are encouraged to document their decisions and reasoning in comments or a README file, explaining why specific implementations or structures were chosen.

## Setup Instructions:

* Start the project by running `./start.sh`.
* To access the container environment, use: `docker compose exec app bash`.

---

## Implementation Reasoning & Architectural Decisions

The implementation follows a **Modular Monolith** architecture with strict adherence to **Domain-Driven Design (DDD)** principles. The goal was to create a system that is robust, testable, and explicitly defines its boundaries.

### 1. Domain-Driven Design (DDD)
The core logic is encapsulated within the Domain Layer, isolated from Infrastructure and Presentation concerns.
*   **Rich Domain Model**: The `Invoice` model is not just a data container. It encapsulates business invariants and state transitions (e.g., `markAsSending`, `markAsSentToClient`). This ensures that an Invoice can never be in an invalid state, regardless of where the code is called from.
*   **Repositories**: Access to the database is abstracted via interfaces (`InvoiceRepositoryInterface`), adhering to the Dependency Inversion Principle.

### 2. Data Transfer Objects (DTOs)
To prevent "Primitive Obsession" and leaky abstractions, strict DTOs (`CreateInvoiceDto`, `InvoiceProductLineDto`) were introduced.
*   **Guard Rails**: The Service Layer acts as a trusted boundary. By requiring typed DTOs instead of loose arrays, we ensure that the service only receives valid, structured data.
*   **Composition**: `CreateInvoiceDto` composes an array of `InvoiceProductLineDto`, strictly enforcing the structure of complex nested data.

### 3. Service Layer Responsibility
The `InvoiceService` acts purely as an orchestrator. It does not contain business rules (which belong in the Model) or HTTP logic (which belongs in the Controller).
*   **Workflow**: Retrieve Aggregate -> Call Domain Behavior -> Persist -> Handle Side Effects.

### 4. Presentation Layer
*   **Thin Controllers**: The controller delegates all logic to the Service and maps Requests to DTOs.
*   **API Resources**: Response formatting is decoupled from the internal model structure using Laravel Resources.

### 5. Conciseness & Modern PHP
The codebase utilizes PHP 8.2+ features like Constructor Property Promotion, Readonly Properties, and Arrow Functions to reduce boilerplate while maintaining readability.
