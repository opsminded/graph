# Opsminded Graph - Project Documentation

## Overview

**Opsminded Graph** is a PHP library that provides a lightweight graph database system with REST API capabilities. It manages graph structures (nodes and edges) with comprehensive auditing and status tracking, using SQLite for persistence.

**Key Features:**
- Graph data structure management (nodes and edges)
- **Required node attributes validation** (category and type)
- SQLite-based persistence layer
- Comprehensive audit logging for all operations
- Node status tracking with history
- RESTful HTTP API
- Transaction support
- PSR-12 compliant code
- Full test coverage

**License:** MIT
**Namespace:** `Opsminded\Graph`
**Minimum PHP Version:** 8.0+

---

## Project Structure

```
/home/tarcisio/projects/graph/
├── src/                          # Source code
│   ├── Graph.php                 # Main graph abstraction (217 lines)
│   ├── Database.php              # SQLite persistence layer (524 lines)
│   ├── AuditContext.php          # Global audit context (81 lines)
│   └── NodeStatus.php            # Status value object (44 lines)
├── tests/                        # PHPUnit test suite
│   ├── GraphTest.php
│   ├── DatabaseTest.php
│   ├── AuditContextTest.php
│   └── NodeStatusTest.php
├── vendor/                       # Composer dependencies
├── coverage/                     # Test coverage reports
├── index.php                     # REST API entry point (110 lines)
├── composer.json                 # Package configuration
├── phpunit.xml                   # Test configuration
├── phpcs.xml                     # Code style rules
├── .php-cs-fixer.dist.php       # CS Fixer configuration
└── README.md                     # User documentation
```

---

## Architecture

### Layered Architecture

```
┌─────────────────────────────────┐
│   REST API Layer (index.php)   │  HTTP endpoints for graph operations
└───────────────┬─────────────────┘
                │
┌───────────────▼─────────────────┐
│  Business Logic (Graph.php)     │  Public API for graph operations
└───────────────┬─────────────────┘
                │
┌───────────────▼─────────────────┐
│  Persistence (Database.php)     │  Data access and schema management
└───────────────┬─────────────────┘
                │
┌───────────────▼─────────────────┐
│      SQLite Database            │  File-based storage
└─────────────────────────────────┘
```

### Design Patterns

1. **Singleton Pattern** - AuditContext for global state management
2. **Value Object Pattern** - NodeStatus for immutable data containers
3. **Data Access Object (DAO)** - Database class abstracts data access
4. **Repository Pattern** - Graph acts as a repository for graph operations
5. **Factory Pattern** - Database auto-initializes schema on first use

---

## Core Components

### 1. Graph.php (src/Graph.php:1)

**Purpose:** High-level abstraction for graph operations with enforced validation.

**Node Validation Rules:**

All nodes **MUST** have two required attributes:
- **`category`**: Must be one of: `business`, `application`, `infrastructure`
- **`type`**: Must be one of: `server`, `database`, `application`, `network`

These are enforced at creation time and validated during updates.

**Key Methods:**

```php
// Node Operations
addNode(string $id, array $data = []): bool  // Requires 'category' and 'type' in $data
updateNode(string $id, array $data): bool    // Validates 'category' and 'type' if provided
removeNode(string $id): bool
nodeExists(string $id): bool

// Validation Helpers
static getAllowedCategories(): array  // Returns ['business', 'application', 'infrastructure']
static getAllowedTypes(): array       // Returns ['server', 'database', 'application', 'network']

// Edge Operations
addEdge(string $source, string $target): bool
removeEdge(string $source, string $target): bool
edgeExists(string $source, string $target): bool

// Status Management
setNodeStatus(string $nodeId, string $status): bool
getNodeStatus(string $nodeId): ?NodeStatus
getNodeStatusHistory(string $nodeId): array

// Audit Operations
auditLog(string $entityType, string $entityId, string $action,
         ?array $oldData = null, ?array $newData = null): void
getAuditHistory(?string $entityType = null, ?string $entityId = null,
                ?string $action = null): array

// Retrieval
get(): array  // Returns {nodes: [...], edges: [...]}
status(): array  // Returns latest status for all nodes
```

**Usage Example:**
```php
use Opsminded\Graph\Graph;

$graph = new Graph('/path/to/graph.db');

// Get allowed values
$allowedCategories = Graph::getAllowedCategories(); // ['business', 'application', 'infrastructure']
$allowedTypes = Graph::getAllowedTypes();           // ['server', 'database', 'application', 'network']

// Add nodes (category and type are REQUIRED)
$graph->addNode('user1', [
    'category' => 'application',
    'type' => 'server',
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

$graph->addNode('user2', [
    'category' => 'business',
    'type' => 'application',
    'name' => 'Jane Smith'
]);

// Add edges
$graph->addEdge('user1', 'user2');

// Update node (category and type are validated if provided)
$graph->updateNode('user1', ['active' => true]);

// Track status
$graph->setNodeStatus('user1', 'active');

// Retrieve graph
$graphData = $graph->get();
```

### 2. Database.php (src/Database.php:1)

**Purpose:** SQLite persistence layer with schema management.

**Database Schema:**

```sql
CREATE TABLE nodes (
    id TEXT PRIMARY KEY,
    data TEXT,              -- JSON encoded
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE edges (
    source TEXT NOT NULL,
    target TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (source, target),
    FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
    FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
);

CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type TEXT NOT NULL,
    entity_id TEXT NOT NULL,
    action TEXT NOT NULL,
    old_data TEXT,          -- JSON encoded
    new_data TEXT,          -- JSON encoded
    user_id TEXT,
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE node_status (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    node_id TEXT NOT NULL,
    status TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_audit_log_entity ON audit_log(entity_type, entity_id);
CREATE INDEX idx_audit_log_created_at ON audit_log(created_at);
CREATE INDEX idx_node_status_node_id ON node_status(node_id);
CREATE INDEX idx_node_status_created_at ON node_status(created_at);
```

**Key Features:**
- Lazy connection initialization
- Foreign key constraints with cascading deletes
- Transaction support (begin/commit/rollback)
- Idempotent operations using `INSERT OR IGNORE`
- Comprehensive error handling with logging
- Indexed queries for performance

**Key Methods:**
```php
insertNode(string $id, array $data): void
updateNode(string $id, array $data): void
deleteNode(string $id): void
getNode(string $id): ?array
getAllNodes(): array

insertEdge(string $source, string $target): void
deleteEdge(string $source, string $target): void
edgeExists(string $source, string $target): bool
getAllEdges(): array

insertAuditLog(string $entityType, string $entityId, string $action,
               ?array $oldData, ?array $newData, ?string $userId, ?string $ip): void
getAuditHistory(?string $entityType, ?string $entityId, ?string $action): array

insertNodeStatus(string $nodeId, string $status): void
getLatestNodeStatus(string $nodeId): ?array
getNodeStatusHistory(string $nodeId): array
getAllLatestNodeStatuses(): array

beginTransaction(): void
commit(): void
rollback(): void
```

### 3. AuditContext.php (src/AuditContext.php:1)

**Purpose:** Global singleton for tracking user and IP across requests.

**Key Methods:**
```php
AuditContext::set(?string $user, ?string $ip): void
AuditContext::getUser(): ?string
AuditContext::getIp(): ?string
AuditContext::initFromRequest(): void  // Auto-detects from $_SERVER
AuditContext::clear(): void
```

**Usage Example:**
```php
use Opsminded\Graph\AuditContext;

// Manual setting
AuditContext::set('user123', '192.168.1.1');

// Auto-detect from HTTP request
AuditContext::initFromRequest();

// All Graph operations will now include this context in audit logs
$graph->addNode('node1', ['data' => 'value']);
```

**IP Detection Priority:**
1. HTTP_X_FORWARDED_FOR (first IP in list)
2. HTTP_X_REAL_IP
3. REMOTE_ADDR

### 4. NodeStatus.php (src/NodeStatus.php:1)

**Purpose:** Immutable value object representing node status.

**Properties:**
```php
public readonly string $nodeId;
public readonly string $status;
public readonly string $createdAt;
```

**Methods:**
```php
__construct(string $nodeId, string $status, string $createdAt)
getNodeId(): string
getStatus(): string
getCreatedAt(): string
toArray(): array
```

---

## REST API (index.php:1)

### Available Endpoints

| Method | Path | Description | Request Body |
|--------|------|-------------|--------------|
| GET | `/` | List available endpoints | - |
| GET | `/graph` | Retrieve full graph structure | - |
| POST | `/node` | Create a new node | `{"id": "...", ...data}` |
| PUT | `/node/{id}` | Update node data | `{...data}` |
| PATCH | `/node/{id}` | Alternative update method | `{...data}` |
| DELETE | `/node/{id}` | Remove a node | - |
| POST | `/edge` | Create an edge | `{"source": "...", "target": "..."}` |
| DELETE | `/edge/{source}/{target}` | Remove an edge | - |
| GET | `/audit` | Retrieve audit history | Query params: `entity_type`, `entity_id`, `action` |

### API Examples

**Create a node:**
```bash
curl -X POST http://localhost/index.php/node \
  -H "Content-Type: application/json" \
  -d '{"id": "user1", "name": "John Doe", "email": "john@example.com"}'
```

**Create an edge:**
```bash
curl -X POST http://localhost/index.php/edge \
  -H "Content-Type: application/json" \
  -d '{"source": "user1", "target": "user2"}'
```

**Get graph:**
```bash
curl http://localhost/index.php/graph
```

**Get audit history:**
```bash
curl "http://localhost/index.php/audit?entity_type=node&entity_id=user1"
```

**Response Format:**
```json
{
  "success": true,
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Error message"
}
```

---

## Development

### Dependencies

**Runtime:**
- PHP 8.0+
- SQLite 3 (via PDO)

**Development:**
- PHPUnit ^9.0 - Unit testing
- PHP_CodeSniffer ^3.7 - Code style checking
- PHP-CS-Fixer ^3.0 - Code style fixing

### Composer Scripts

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Check code style
composer phpcs

# Fix code style
composer phpcbf
composer cs-fix
```

### Testing

Tests are located in `tests/` and use PHPUnit:

```bash
# Run all tests
./vendor/bin/phpunit

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

**Test Coverage:**
- `GraphTest.php` - Tests Graph class methods
- `DatabaseTest.php` - Tests Database operations and schema
- `AuditContextTest.php` - Tests context management
- `NodeStatusTest.php` - Tests NodeStatus value object

All tests use temporary databases in `/tmp` for isolation.

### Code Style

The project follows PSR-12 coding standards:
- Strict types enabled in all files
- Type hints for all parameters and return types
- Proper namespace organization
- Comprehensive error handling

---

## Common Workflows

### 1. Library Usage (Programmatic)

```php
<?php
require 'vendor/autoload.php';

use Opsminded\Graph\Graph;
use Opsminded\Graph\AuditContext;

// Set audit context
AuditContext::set('admin_user', '10.0.0.1');

// Create graph instance
$graph = new Graph('/var/data/my-graph.db');

// Build a social network (category and type are required)
$graph->addNode('alice', [
    'category' => 'business',
    'type' => 'application',
    'name' => 'Alice',
    'age' => 30
]);
$graph->addNode('bob', [
    'category' => 'business',
    'type' => 'application',
    'name' => 'Bob',
    'age' => 25
]);
$graph->addNode('charlie', [
    'category' => 'business',
    'type' => 'application',
    'name' => 'Charlie',
    'age' => 35
]);

$graph->addEdge('alice', 'bob');      // Alice knows Bob
$graph->addEdge('bob', 'charlie');    // Bob knows Charlie
$graph->addEdge('alice', 'charlie');  // Alice knows Charlie

// Track status
$graph->setNodeStatus('alice', 'active');
$graph->setNodeStatus('bob', 'active');
$graph->setNodeStatus('charlie', 'inactive');

// Query the graph
$graphData = $graph->get();
print_r($graphData);

// Get audit trail
$history = $graph->getAuditHistory('node', 'alice');
print_r($history);
```

### 2. REST API Usage

```bash
#!/bin/bash

# Create nodes (category and type are required)
curl -X POST http://localhost/index.php/node \
  -H "Content-Type: application/json" \
  -d '{"id": "product1", "category": "application", "type": "server", "name": "Laptop", "price": 999}'

curl -X POST http://localhost/index.php/node \
  -H "Content-Type: application/json" \
  -d '{"id": "category1", "category": "business", "type": "application", "name": "Electronics"}'

# Create relationship
curl -X POST http://localhost/index.php/edge \
  -H "Content-Type: application/json" \
  -d '{"source": "product1", "target": "category1"}'

# Retrieve graph
curl http://localhost/index.php/graph | jq
```

### 3. Transaction Usage

```php
use Opsminded\Graph\Database;

$db = new Database('/path/to/db');

try {
    $db->beginTransaction();

    $db->insertNode('node1', [
        'category' => 'infrastructure',
        'type' => 'server',
        'data' => 'value1'
    ]);
    $db->insertNode('node2', [
        'category' => 'application',
        'type' => 'database',
        'data' => 'value2'
    ]);
    $db->insertEdge('node1', 'node2');

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

---

## Data Storage

### Node Data Format

Nodes store data as JSON in the `data` column. **All nodes must include `category` and `type` attributes:**

**Required Attributes:**
- `category`: Must be one of: `business`, `application`, `infrastructure`
- `type`: Must be one of: `server`, `database`, `application`, `network`

**Example:**
```php
$graph->addNode('user1', [
    'category' => 'business',        // REQUIRED
    'type' => 'application',         // REQUIRED
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'metadata' => [
        'created_by' => 'admin',
        'tags' => ['customer', 'premium']
    ]
]);
```

**Validation Errors:**
```php
// This will throw RuntimeException: "Node category is required"
$graph->addNode('user1', ['name' => 'John Doe']);

// This will throw RuntimeException: "Invalid category. Allowed values: business, application, infrastructure"
$graph->addNode('user1', ['category' => 'invalid', 'type' => 'server']);
```

### Audit Log Format

Every mutation is logged with:
- `entity_type`: "node" or "edge"
- `entity_id`: Identifier of the entity
- `action`: "create", "update", or "delete"
- `old_data`: Previous state (JSON, null for create)
- `new_data`: New state (JSON, null for delete)
- `user_id`: From AuditContext
- `ip_address`: From AuditContext
- `created_at`: Timestamp

### Status History

Status changes are tracked with:
- `node_id`: Node identifier
- `status`: Status string (e.g., "active", "inactive", "pending")
- `created_at`: Timestamp

---

## Key Design Decisions

1. **Required Node Attributes**: All nodes must have `category` and `type` attributes with predefined allowed values. This enforces a consistent classification scheme across the graph.

2. **SQLite Choice**: Lightweight, file-based database suitable for small to medium graphs. No separate database server required.

3. **JSON Storage**: Node data stored as JSON for flexible, schema-less data structures (beyond the required category/type).

4. **Audit Trail**: All modifications automatically logged for compliance and debugging.

5. **Foreign Keys**: Cascading deletes ensure referential integrity (deleting a node removes its edges).

6. **Global Context**: AuditContext allows implicit tracking without passing user/IP to every method.

7. **Idempotent Operations**: Using `INSERT OR IGNORE` prevents duplicate errors on retry.

8. **Strict Typing**: PHP 8 strict types catch errors early and improve IDE support.

9. **Transaction Support**: Enables atomic multi-operation updates.

---

## Limitations and Considerations

1. **Scalability**: SQLite performs well for small to medium graphs. For very large graphs (millions of nodes), consider migrating to PostgreSQL or a dedicated graph database.

2. **Concurrency**: SQLite has limited write concurrency. Multiple simultaneous writes may face locking issues.

3. **Schema Evolution**: Node data is schema-less (JSON), but table schema changes require migrations.

4. **Query Performance**: Complex graph traversals (e.g., finding paths, deep relationships) are not optimized. Consider adding custom queries or caching for complex operations.

5. **Single File**: The entire graph is in one SQLite file. Ensure proper backup strategy.

6. **No Built-in Graph Algorithms**: No path finding, centrality calculation, or other graph algorithms. Implement at application level if needed.

---

## Future Enhancement Ideas

- Graph traversal methods (find paths, neighbors, etc.)
- Pagination for large result sets
- WebSocket support for real-time updates
- Graph visualization endpoints
- Export/import functionality (GraphML, JSON, CSV)
- Authentication and authorization for REST API
- Rate limiting and caching
- Migration to PostgreSQL for larger scale
- Graph query language support
- Batch operations API

---

## Error Handling

All errors are logged via PHP's `error_log()`:
- Database connection failures
- Query execution errors
- Transaction rollbacks
- Invalid operations
- **Validation errors** (missing or invalid category/type)

Example error patterns:
```php
try {
    $graph->addNode('node1', $data);
} catch (RuntimeException $e) {
    // Validation errors (missing category/type or invalid values)
    echo "Validation failed: " . $e->getMessage();
    // Examples:
    // - "Node category is required"
    // - "Node type is required"
    // - "Invalid category. Allowed values: business, application, infrastructure"
    // - "Invalid type. Allowed values: server, database, application, network"
} catch (Exception $e) {
    // Other errors (database, etc.) are logged automatically
    echo "Failed to add node: " . $e->getMessage();
}
```

---

## Contributing

1. Follow PSR-12 coding standards
2. Add tests for new features
3. Run `composer phpcs` before committing
4. Run `composer test` to ensure all tests pass
5. Maintain test coverage above 90%

---

## Contact and Support

- **Package**: opsminded/graph
- **License**: MIT
- **PHP Version**: 8.0+

For issues and feature requests, refer to the project's issue tracker.

---

**Last Updated**: 2025-12-30
**Documentation Version**: 1.0
