# SAP RFC Manager - Production-Ready Laravel SAP Integration Package

A robust, enterprise-grade Laravel package for seamless SAP NW RFC (Remote Function Call) integration with built-in resilience patterns, observability, and advanced connection management.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Installation & Setup](#installation--setup)
- [Configuration](#configuration)
- [Usage Guide](#usage-guide)
- [API Reference](#api-reference)
- [Observability & Monitoring](#observability--monitoring)
- [Testing](#testing)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)

---

## 🎯 Overview

**SAP RFC Manager** is a sophisticated Laravel package that simplifies SAP ERP integration by providing:

- **Fluent API** for building and executing RFC function calls
- **Automatic Connection Management** with health checks and failover
- **Circuit Breaker Pattern** to prevent cascade failures
- **Retry Logic** with exponential backoff for transient errors
- **Prometheus Metrics** for real-time monitoring and observability
- **Event System** with pre/post execution hooks
- **Middleware Pipeline** for extensible request transformation
- **Multi-Router Support** for high availability across SAP systems

### Perfect For:

- Enterprise Laravel applications requiring SAP ERP integration
- Systems needing fault tolerance and automatic recovery
- Organizations requiring detailed monitoring and observability
- Applications with multiple SAP environments (dev, test, prod)
- Mission-critical SAP communication requiring resilience

---

## ✨ Features

### Core Features
- ✅ **Fluent Query Builder** - Clean, chainable API for RFC calls
- ✅ **Connection Pooling** - Reusable, cached SAP connections
- ✅ **Health Checking** - Automatic connection validation via ping
- ✅ **Router Failover** - Automatic failover across multiple SAP routers
- ✅ **Circuit Breaker** - Prevent cascade failures with state machine pattern
- ✅ **Retry Logic** - Configurable retry attempts with exponential backoff
- ✅ **Event System** - Pre/post execution events for extensibility
- ✅ **Middleware Pipeline** - Custom request/response transformation

### Observability Features
- 📊 **Prometheus Metrics** - Track requests, success rates, and execution time
- 📈 **Metrics Collection** - Per-environment, per-function statistics
- 🚨 **Circuit Breaker State** - Real-time visibility into system health
- 📝 **Event Logging** - Complete audit trail of RFC executions

### Enterprise Features
- 🔐 **Multi-Environment Support** - Separate credentials for each environment
- 🏢 **Multi-Router Architecture** - High availability across SAP systems
- ⚡ **Lazy Loading** - Connections created only when needed
- 🧹 **Graceful Cleanup** - Proper resource management and disconnection
- 📦 **Docker Support** - Pre-configured Docker & Docker Compose setup
- 🔄 **Automatic Failover** - Seamless migration to backup routers

---

## 🏗️ Architecture

### High-Level Architecture

```
┌──────────────────────────────────────────────────────────┐
│ Application Code (Laravel)                               │
│ SapRfc::on('prod')->call('BAPI_*')->with([])->execute() │
└────────────────┬─────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────┐
│ SapRfcQuery (Query Orchestrator)                         │
│ - Fluent Interface Builder                              │
│ - Middleware Pipeline                                   │
└────────────────┬─────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────┐
│ Core Execution Engine                                    │
│ - CircuitBreaker (Fault Tolerance)                      │
│ - MetricsCollector (Observability)                      │
│ - Retry Logic (Transient Error Handling)                │
└────────────────┬─────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────┐
│ SapConnectionManager (Connection Layer)                  │
│ - Connection Pooling                                    │
│ - Health Checks (Ping)                                  │
│ - Router Failover                                       │
└────────────────┬─────────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────────┐
│ SAP NW RFC Extension (C Extension)                       │
│ - Native SAP Communication                              │
└──────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Purpose | Key Responsibility |
|-----------|---------|-------------------|
| **SapRfcQuery** | Query Builder | Orchestrates execution pipeline, fluent interface |
| **SapConnectionManager** | Connection Layer | Manages connection lifecycle, pooling, failover |
| **CircuitBreaker** | Resilience | Prevents cascade failures, state management |
| **MetricsCollector** | Observability | Tracks request metrics per environment/function |
| **PrometheusExporter** | Monitoring | Exposes metrics in Prometheus format |
| **Event System** | Extensibility | Pre/post execution hooks |

### Design Patterns

- **Dependency Injection** - Constructor-based, container-managed
- **Facade Pattern** - Simple static interface via `SapRfc`
- **Circuit Breaker** - State machine for fault tolerance
- **Fluent Interface** - Method chaining for DSL
- **Middleware Pipeline** - Extensible request transformation
- **Data Transfer Object** - Immutable result container
- **Connection Pooling** - Resource reuse and management

---

## 📦 Installation & Setup

### Prerequisites

- PHP 8.2+
- Laravel 10.x or higher
- SAP NW RFC SDK (included in `saprfc-sdk/`)
- Redis (for metrics, cache, and circuit breaker state)
- Docker & Docker Compose (optional, for containerized setup)

### Quick Start with Docker

The easiest way to get started is using the provided Docker setup:

```bash
# Clone the repository
git clone <repository-url>
cd saprfc-manager

# Build and start containers
docker-compose up -d

# Install PHP dependencies
docker-compose exec app composer install

# Run tests to verify setup
docker-compose exec app ./vendor/bin/pest
```

### Manual Installation

#### 1. Install SAP NW RFC Extension

```bash
# Install system dependencies (Ubuntu/Debian)
sudo apt-get install -y build-essential php8.2-dev libsapnwrfc-dev

# Compile the extension from source
cd saprfc-sdk/sapnwrfc
phpize
./configure --with-sapnwrfc=/path/to/nwrfcsdk
make
sudo make install

# Enable in php.ini
echo "extension=sapnwrfc.so" | sudo tee /etc/php/8.2/cli/conf.d/20-sapnwrfc.ini
```

#### 2. Install PHP Dependencies

```bash
composer install
```

#### 3. Publish Configuration

```bash
php artisan vendor:publish --provider="App\Providers\SapRfcServiceProvider"
```

This creates `config/saprfc.php` with default configuration.

---

## ⚙️ Configuration

### Configuration File (`config/saprfc.php`)

```php
<?php

return [
    // Default environment and router
    'default' => [
        'router' => env('SAP_DEFAULT_ROUTER','bisnet'),        // Primary router
        'connection' => env('SAP_DEFAULT_CONNECTION', 'default'),   // Default environment
    ],

    // Retry configuration
    'retry' => [
        'times' => 3,                // Number of retry attempts
        'backoff_ms' => 500,         // Delay between retries (exponential)
    ],

    // SAP Router definitions (for connection string building)
    'routers' => [
        'bisnet' => env('SAP_BISNET_ROUTER', '/H/127.0.0.1/S/4600/W/SAP_DEFAULT'),
        'iforte' => env('SAP_IFORTE_ROUTER', '/H/127.0.0.1/S/4603/W/SAP_DEFAULT'),
    ],

    // Named connections (environments)
    'connections' => [
        'default' => [
            'ashost'  => env('SAP_ASHOST', 'sap-server.example.com'),
            'sysnr'   => env('SAP_SYSNR', '01'),
            'client'  => env('SAP_CLIENT', '100'),
            'user'    => env('SAP_USER', 'SYSTEM'),
            'passwd'  => env('SAP_PASSWD', 'password'),
        ],
        'production' => [
            'ashost'  => env('SAP_PROD_ASHOST'),
            'sysnr'   => env('SAP_PROD_SYSNR'),
            'client'  => env('SAP_PROD_CLIENT'),
            'user'    => env('SAP_PROD_USER'),
            'passwd'  => env('SAP_PROD_PASSWD'),
        ],
    ],

    // Middleware pipeline for request transformation
    'middleware' => [
        // Add custom middleware classes here
    ],

    // Circuit breaker configuration
    'circuit_breaker' => [
        'threshold' => 5,              // Failures before opening circuit
        'timeout_seconds' => 60,       // Time circuit stays open
    ],

    // Metrics and observability
    'metrics' => [
        'enabled' => true,
        'expose_route' => true,
        'route_path' => '/metrics/sap',  // Prometheus metrics endpoint
    ],
];
```

### Environment Variables (.env)

```env
# SAP Connection (Default Environment)
SAP_ASHOST=sap-server.example.com
SAP_SYSNR=01
SAP_CLIENT=100
SAP_USER=SYSTEM
SAP_PASSWD=your_password

# SAP Connection (Production Environment)
SAP_PROD_ASHOST=prod-sap-server.example.com
SAP_PROD_SYSNR=01
SAP_PROD_CLIENT=100
SAP_PROD_USER=SYSTEM
SAP_PROD_PASSWD=prod_password

# Cache store (Required for circuit breaker and metrics)
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Adding New Environments

To add a new SAP environment:

```php
// config/saprfc.php
'connections' => [
    'staging' => [
        'ashost'  => env('SAP_STAGING_ASHOST'),
        'sysnr'   => env('SAP_STAGING_SYSNR'),
        'client'  => env('SAP_STAGING_CLIENT'),
        'user'    => env('SAP_STAGING_USER'),
        'passwd'  => env('SAP_STAGING_PASSWD'),
    ],
],
```

---

## 🚀 Usage Guide

### Basic Usage

#### 1. Simple RFC Execution

```php
use App\Facades\SapRfc;

// Execute an RFC function with parameters
$result = SapRfc::on('default')
    ->call('BAPI_USER_GETLIST')
    ->with([
        'MAX_ROWS' => 100,
        'FILTER'   => [
            ['SIGN' => 'I', 'OPTION' => 'EQ', 'LOW' => 'USER1'],
        ],
    ])
    ->execute();

// Access results
$users = $result->get('USERLIST');
$returnStatus = $result->get('RETURN', []);

// Get all data
$allData = $result->toArray();
```

#### 2. Multi-Step Data Retrieval

```php
// Get customer data from production
$customers = SapRfc::on('production')
    ->call('BAPI_CUSTOMER_GETLIST')
    ->with(['MAXROWS' => 500])
    ->execute()
    ->get('CUSTOMERLIST');

// Process each customer
foreach ($customers as $customer) {
    // Get detailed information
    $details = SapRfc::on('production')
        ->call('BAPI_CUSTOMER_GETDETAIL')
        ->with(['CUSTOMERNO' => $customer['KUNNR']])
        ->execute();

    // Store or process details
}
```

#### 3. Error Handling

```php
use App\Exceptions\SapRfcException;

try {
    $result = SapRfc::on('production')
        ->call('BAPI_PO_CREATE')
        ->with($purchaseOrderData)
        ->execute();

    if ($result->get('RETURN') !== []) {
        // Handle SAP-level errors
        $errors = $result->get('RETURN');
        Log::warning('SAP returned errors', ['errors' => $errors]);
    }
} catch (SapRfcException $e) {
    // Handle connection or execution errors
    Log::error('SAP RFC Error: ' . $e->getMessage());
    // Circuit breaker may be open, retry later
}
```

#### 4. Batch Processing with Retry

```php
// The retry logic is automatic - configure in config/saprfc.php
// By default: 3 attempts with 500ms exponential backoff

$result = SapRfc::on('default')
    ->call('BAPI_PURCHASE_ORDER_CREATE')
    ->with($poData)
    ->execute();  // Automatically retries on transient errors

// For custom retry logic outside the package:
$maxRetries = 5;
$attempt = 0;
do {
    try {
        $result = SapRfc::on('production')
            ->call('BAPI_VENDOR_CREATE')
            ->with($vendorData)
            ->execute();
        break;
    } catch (SapRfcException $e) {
        $attempt++;
        if ($attempt >= $maxRetries) throw $e;
        sleep(2 ** $attempt);  // Exponential backoff
    }
} while ($attempt < $maxRetries);
```

### Advanced Usage

#### 1. Custom Middleware

Create a middleware class:

```php
namespace App\Middleware;

class LoggingMiddleware
{
    public function handle($query, $next)
    {
        Log::info('Executing RFC', [
            'function' => $query->functionName,
            'environment' => $query->environment,
        ]);

        $result = $next($query);

        Log::info('RFC executed', [
            'duration' => $result->executionTimeMs,
            'status' => 'success',
        ]);

        return $result;
    }
}
```

Register in config:

```php
'middleware' => [
    \App\Middleware\LoggingMiddleware::class,
],
```

#### 2. Event Listeners

```php
// Register listeners in EventServiceProvider
protected $listen = [
    \App\Events\SapRfcExecuting::class => [
        \App\Listeners\LogSapRfcExecution::class,
    ],
    \App\Events\SapRfcExecuted::class => [
        \App\Listeners\UpdateMetricsListener::class,
    ],
];
```

Listener example:

```php
namespace App\Listeners;

use App\Events\SapRfcExecuted;

class UpdateMetricsListener
{
    public function handle(SapRfcExecuted $event): void
    {
        // $event->functionName
        // $event->result (SapResult DTO)

        // Update your metrics system
        StatsD::gauge('sap.execution_time', $event->result->executionTimeMs);
    }
}
```

#### 3. Accessing Connection Manager Directly

```php
use App\Contracts\SapConnectionManagerInterface;

class YourService
{
    public function __construct(
        private SapConnectionManagerInterface $connectionManager
    ) {}

    public function customSapLogic()
    {
        // Get connection for specific environment
        $connection = $this->connectionManager->connection('production');

        // Use native SAP connection methods
        // Note: This bypasses the query builder and resilience patterns

        // Always disconnect when done
        $this->connectionManager->disconnect('production');
    }
}
```

---

## 📖 API Reference

### Facade: `SapRfc`

#### `on(string $environment): self`

Select the SAP environment to connect to.

```php
SapRfc::on('production')  // Uses credentials from config['connections']['production']
```

**Parameters:**
- `$environment` - Environment name from `config/saprfc.php`

**Returns:** `SapRfcQuery` (self for chaining)

**Throws:** `SapRfcException` if environment not configured

---

#### `call(string $functionName): self`

Specify the SAP RFC function to execute.

```php
SapRfc::on('default')
    ->call('BAPI_CUSTOMER_GETLIST')
```

**Parameters:**
- `$functionName` - SAP RFC function name (case-insensitive)

**Returns:** `SapRfcQuery` (self for chaining)

---

#### `with(array $parameters): self`

Pass parameters to the RFC function. Can be called multiple times to accumulate parameters.

```php
SapRfc::on('default')
    ->call('BAPI_PO_CREATE')
    ->with(['PURCHASEORDER' => ['PO_NUMBER' => '12345']])
    ->with(['ITEMS' => [...]])
    ->execute()
```

**Parameters:**
- `$parameters` - Associative array of RFC parameters (SAP structure format)

**Returns:** `SapRfcQuery` (self for chaining)

---

#### `execute(): SapResult`

Execute the RFC function call with all configured resilience and observability features.

```php
$result = SapRfc::on('default')
    ->call('BAPI_USER_GETLIST')
    ->with(['MAX_ROWS' => 100])
    ->execute();
```

**Execution Flow:**
1. Middleware pipeline processes the query
2. Circuit breaker check (throws if open)
3. Dispatch `SapRfcExecuting` event
4. Retry loop (configurable attempts)
   - Connection manager gets/creates connection
   - Health check via ping
   - Router failover if needed
   - RFC function call
5. Record metrics
6. Dispatch `SapRfcExecuted` event
7. Return `SapResult`

**Returns:** `SapResult`

**Throws:**
- `SapRfcException` on connection errors or execution failure
- Circuit breaker state exception if circuit is OPEN

---

### DTO: `SapResult`

Data Transfer Object containing RFC execution results.

#### Properties

```php
$result->functionName  // string - RFC function name called
$result->data          // array - Response data from SAP
$result->executionTimeMs  // int - Execution time in milliseconds
```

#### Methods

##### `get(string $key, mixed $default = null): mixed`

Access result data by key with fallback default.

```php
$users = $result->get('USERLIST');
$errors = $result->get('RETURN', []);
$status = $result->get('MESSAGE', 'No message');
```

---

##### `toArray(): array`

Serialize the complete result to array.

```php
$data = $result->toArray();
// [
//     'functionName' => 'BAPI_USER_GETLIST',
//     'data' => [...],
//     'executionTimeMs' => 250,
// ]
```

---

### Events

#### `SapRfcExecuting`

Fired **before** RFC execution begins.

```php
// In listeners
public function handle(SapRfcExecuting $event)
{
    // $event->functionName  - string
    // $event->parameters    - array
}
```

---

#### `SapRfcExecuted`

Fired **after** successful RFC execution.

```php
// In listeners
public function handle(SapRfcExecuted $event)
{
    // $event->functionName  - string
    // $event->result        - SapResult object
}
```

---

### Exception: `SapRfcException`

Base exception for all SAP RFC errors.

```php
try {
    SapRfc::on('default')->call('BAPI_*')->execute();
} catch (SapRfcException $e) {
    // Handle SAP connection or execution errors
    Log::error($e->getMessage());
}
```

---

## 📊 Observability & Monitoring

### Prometheus Metrics

The package automatically exposes Prometheus-compatible metrics at the configured endpoint.

#### Default Endpoint

```
GET /metrics/sap
```

Configure the path in `config/saprfc.php`:

```php
'metrics' => [
    'expose_route' => true,
    'route_path' => '/metrics/sap',
]
```

#### Metric Format

```
# HELP sap_rfc_requests_total Total number of SAP RFC requests
# TYPE sap_rfc_requests_total counter
sap_rfc_requests_total{env="production",function="BAPI_USER_GETLIST",status="success"} 1250
sap_rfc_requests_total{env="production",function="BAPI_USER_GETLIST",status="failure"} 15
sap_rfc_requests_total{env="production",function="BAPI_PO_CREATE",status="success"} 840
sap_rfc_requests_total{env="production",function="BAPI_PO_CREATE",status="failure"} 2
```

### Metric Labels

- `env` - Environment name (e.g., "production", "staging")
- `function` - SAP RFC function name
- `status` - Execution status ("success" or "failure")

### Scraping with Prometheus

Add to `prometheus.yml`:

```yaml
scrape_configs:
  - job_name: 'sap_rfc'
    static_configs:
      - targets: ['localhost:8000']
    metrics_path: '/metrics/sap'
    scrape_interval: 30s
```

### Circuit Breaker State

Monitor circuit breaker state via application logs or custom listeners:

```php
use App\Observability\CircuitBreaker;

// Get circuit breaker instance
$circuitBreaker = app(CircuitBreaker::class);

// Check state
$isOpen = $circuitBreaker->check('production', 'BAPI_USER_GETLIST');
```

Circuit breaker states:
- **CLOSED** - Normal operation, all requests proceed
- **OPEN** - Too many failures, requests blocked with exception
- **HALF_OPEN** - Waiting for timeout, next request acts as test

### Custom Metrics

Extend `MetricsCollector` to track custom metrics:

```php
use App\Observability\MetricsCollector;

class CustomMetricsCollector extends MetricsCollector
{
    public function recordCustomMetric($key, $value)
    {
        // Store in Redis or other backend
        Redis::lpush("custom_metrics:$key", $value);
    }
}
```

---

## 🧪 Testing

### Test Setup

The package includes comprehensive tests using [Pest PHP](https://pestphp.com/).

```bash
# Run all tests
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage

# Run specific test file
./vendor/bin/pest tests/Feature/SapRfcQueryTest.php

# Run specific test
./vendor/bin/pest --filter="test_successful_execution"
```

### Test Configuration

Tests use Orchestra Testbench for Laravel package testing:

```php
// tests/TestCase.php
class TestCase extends Orchestra\Testbench
{
    protected function getPackageProviders($app)
    {
        return [SapRfcServiceProvider::class];
    }
}
```

### Writing Tests

Example test for RFC execution:

```php
use App\Facades\SapRfc;
use App\Exceptions\SapRfcException;

test('executes rfc successfully', function () {
    // Mock SAP connection
    $mockConnection = mock(SapConnection::class)
        ->shouldReceive('call')
        ->with('BAPI_USER_GETLIST', [...])
        ->andReturn(['USERLIST' => [...]])
        ->getMock();

    // Execute
    $result = SapRfc::on('default')
        ->call('BAPI_USER_GETLIST')
        ->with(['MAX_ROWS' => 10])
        ->execute();

    // Assert
    expect($result->functionName)->toBe('BAPI_USER_GETLIST');
    expect($result->get('USERLIST'))->toBeArray();
});
```

### Test Coverage Areas

- ✅ RFC execution with parameters
- ✅ Event dispatching (executing/executed)
- ✅ DTO serialization
- ✅ Retry logic on transient errors
- ✅ Circuit breaker state transitions
- ✅ Metrics collection and recording
- ✅ Prometheus metric export format
- ✅ Middleware pipeline transformation
- ✅ Service container registration
- ✅ Multi-environment support

---

## 🐳 Deployment

### Docker Deployment

#### Docker Compose Setup

The repository includes a complete Docker Compose setup:

```yaml
# docker-compose.yml
services:
  app:
    build: .
    volumes:
      - ./app:/app
    environment:
      CACHE_STORE: redis
      REDIS_HOST: redis
    depends_on:
      - redis

  redis:
    image: redis:7-alpine
    ports:
      - "6380:6379"
```

#### Building and Running

```bash
# Build images
docker-compose build

# Start containers
docker-compose up -d

# Run migrations/setup
docker-compose exec app php artisan migrate

# Run tests
docker-compose exec app ./vendor/bin/pest

# View logs
docker-compose logs -f app
```

#### Dockerfile Details

The `Dockerfile` includes:
- Multi-stage build for SAP NW RFC extension compilation
- PHP 8.2 with required extensions (sapnwrfc, redis, etc.)
- Composer dependencies
- Pre-configured for production use

### Environment Variables for Production

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:xxxxx

# SAP Connections
SAP_ASHOST=prod-sap-server.example.com
SAP_SYSNR=01
SAP_CLIENT=100
SAP_USER=SYSTEM
SAP_PASSWD=secure_password

# Cache & Persistence
CACHE_STORE=redis
REDIS_HOST=redis.example.com
REDIS_PORT=6379
REDIS_PASSWORD=secure_redis_password

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning

# Metrics
SAP_METRICS_ENABLED=true
```

### Kubernetes Deployment

Example Kubernetes manifests:

```yaml
---
apiVersion: v1
kind: ConfigMap
metadata:
  name: saprfc-config
data:
  CACHE_STORE: redis
  SAP_METRICS_ENABLED: "true"

---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: app
spec:
  replicas: 3
  template:
    spec:
      containers:
      - name: app
        image: myregistry/saprfc-manager:latest
        envFrom:
        - configMapRef:
            name: saprfc-config
        livenessProbe:
          httpGet:
            path: /health
            port: 8000
          initialDelaySeconds: 30
          periodSeconds: 10
        resources:
          requests:
            memory: "512Mi"
            cpu: "500m"
          limits:
            memory: "1Gi"
            cpu: "1000m"
```

### Health Checks

Create a health check endpoint:

```php
// routes/web.php
Route::get('/health', function () {
    try {
        // Test SAP connection
        $connection = app(SapConnectionManagerInterface::class)
            ->connection('default');

        return response()->json(['status' => 'healthy']);
    } catch (Exception $e) {
        return response()->json(
            ['status' => 'unhealthy', 'error' => $e->getMessage()],
            503
        );
    }
});
```

### Monitoring & Logging

#### Application Logging

Configure logging in `config/logging.php`:

```php
'channels' => [
    'sap' => [
        'driver' => 'stack',
        'channels' => ['single'],
        'ignore_exceptions' => false,
    ],
],
```

Create a listener to log all SAP executions:

```php
namespace App\Listeners;

use App\Events\SapRfcExecuted;
use Illuminate\Support\Facades\Log;

class LogSapExecution
{
    public function handle(SapRfcExecuted $event): void
    {
        Log::channel('sap')->info('RFC Executed', [
            'function' => $event->functionName,
            'duration_ms' => $event->result->executionTimeMs,
        ]);
    }
}
```

#### Alerting

Set up Prometheus alerts:

```yaml
# prometheus.yml alerts
groups:
- name: sap_alerts
  rules:
  - alert: SapHighErrorRate
    expr: |
      (rate(sap_rfc_requests_total{status="failure"}[5m]) /
       rate(sap_rfc_requests_total[5m])) > 0.1
    for: 5m
    annotations:
      summary: "SAP RFC error rate above 10%"

  - alert: SapCircuitBreakerOpen
    expr: sap_circuit_breaker_state{state="open"} == 1
    for: 1m
    annotations:
      summary: "SAP circuit breaker open"
```

---

## 🔧 Troubleshooting

### Common Issues

#### 1. SAP NW RFC Extension Not Found

**Error:** `Call to undefined function sapnwrfc_*`

**Solution:**
```bash
# Verify extension is installed
php -m | grep sapnwrfc

# Rebuild extension
cd saprfc-sdk/sapnwrfc
make clean && make && sudo make install

# Check php.ini
php -i | grep sapnwrfc
```

#### 2. Connection Timeout / Network Error

**Error:** `Connection timeout` or `Cannot reach SAP server`

**Diagnosis:**
```bash
# Test SAP server connectivity
telnet SAP_HOST SAP_GATEWAY_PORT

# Check SAP router configuration
ping 117.102.101.194  # bisnet router

# Verify credentials
echo "User: $SAP_USER, Client: $SAP_CLIENT"
```

**Solution:**
- Verify SAP credentials in `.env`
- Check network connectivity to SAP servers
- Verify SAP router configuration in `config/saprfc.php`
- Check firewall rules

#### 3. Circuit Breaker Open (Blocking Requests)

**Error:** `Circuit breaker is open` or `Too many failures`

**Diagnosis:**
```php
// Check circuit breaker state
Cache::get('sap_cb_production_BAPI_USER_GETLIST')

// View failure count
Cache::get('sap_failures_production_BAPI_USER_GETLIST')
```

**Solution:**
```php
// Clear circuit breaker state
Cache::forget('sap_cb_production_BAPI_USER_GETLIST');

// Or wait for timeout (default 60 seconds)

// Check logs for underlying errors
Log::channel('sap')->getMonolog()->getHandlers();
```

#### 4. Redis Connection Failed

**Error:** `Redis error` or `Cache store unreachable`

**Diagnosis:**
```bash
# Test Redis connectivity
redis-cli -h REDIS_HOST -p REDIS_PORT ping

# Check Redis configuration
echo "REDIS_HOST: $REDIS_HOST, REDIS_PORT: $REDIS_PORT"
```

**Solution:**
- Verify Redis is running: `redis-cli ping`
- Check Redis credentials
- Update `CACHE_STORE` in `.env` to `file` for testing without Redis
- Restart Redis service

#### 5. High Memory Usage

**Cause:** Unclosed SAP connections or large result sets

**Solution:**
```php
// Always close connections after use
finally {
    app(SapConnectionManagerInterface::class)->disconnect('production');
}

// Paginate large result sets
$page = 0;
while (true) {
    $result = SapRfc::on('production')
        ->call('BAPI_DOCUMENT_LIST')
        ->with(['START_ROW' => $page * 100, 'MAX_ROWS' => 100])
        ->execute();

    if (empty($result->get('DOCUMENTS'))) break;

    // Process page
    $page++;
}
```

#### 6. Metrics Not Appearing

**Check:**
```bash
# Verify metrics endpoint is accessible
curl http://localhost:8000/metrics/sap

# Check if metrics are enabled in config
grep -A 5 "'metrics'" config/saprfc.php

# Verify Redis is storing metrics
redis-cli KEYS "sap_metrics:*"
```

### Debug Mode

Enable verbose logging:

```php
// config/saprfc.php
'debug' => env('APP_DEBUG', false),

// Logs:
Log::channel('sap')->debug('RFC Call', [
    'function' => $functionName,
    'parameters' => $parameters,
]);
```

### Getting Help

For issues or questions:

1. Check test files in `tests/Feature/` for usage examples
2. Review configuration options in `config/saprfc.php`
3. Enable application logging and check logs
4. Monitor Prometheus metrics for patterns
5. Check SAP server logs for error details

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

### Development Setup

```bash
# Clone repository
git clone <repository-url>
cd saprfc-manager

# Setup development environment
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Run tests
docker-compose exec app ./vendor/bin/pest
```

### Code Standards

- Follow PSR-12 coding standard
- Use type hints for all parameters and return types
- Add PHPDoc comments for public methods
- Write tests for new features
- Keep code DRY and maintainable

### Testing Requirements

- All new features must include tests
- Tests must pass locally before submitting PR
- Maintain or improve code coverage

### Submission Process

1. Create a feature branch: `git checkout -b feature/description`
2. Make changes and commit: `git commit -m "Add feature description"`
3. Push to branch: `git push origin feature/description`
4. Submit pull request with description of changes

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

## 📞 Support

For support and questions:
- Open an issue on GitHub
- Check the documentation
- Review test examples
- Enable debug logging for diagnostics

---

## 🎓 Learning Resources

### Understanding SAP RFC

- [SAP RFC Programming Guide](https://help.sap.com/viewer/product/SAP_NW_RFC_LIBRARY)
- [BAPI Documentation](https://help.sap.com/viewer/product/SAP_BAPI)

### Design Patterns Used

- [Circuit Breaker Pattern](https://martinfowler.com/bliki/CircuitBreaker.html)
- [Middleware Pattern](https://en.wikipedia.org/wiki/Middleware)
- [Facade Pattern](https://refactoring.guru/design-patterns/facade)

### Laravel & PHP

- [Laravel Documentation](https://laravel.com/docs)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)

---

## 📈 Roadmap

Future enhancements planned:

- [ ] Support for async RFC calls
- [ ] Built-in result caching layer
- [ ] GraphQL query interface
- [ ] Enhanced error recovery strategies
- [ ] Performance optimization for bulk operations
- [ ] Additional exporter formats (InfluxDB, Datadog)

---

**Built with ❤️ for enterprise SAP integrations**
