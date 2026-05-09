# 📦 Product Inventory Management API

A production-ready REST API built with **Laravel 11.x** for managing products, categories, and suppliers. Features full CRUD, filtering, pagination, authentication, caching, and Swagger documentation.

---

## 🚀 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11.x |
| Language | PHP 8.2+ |
| Auth | Laravel Sanctum (Bearer tokens) |
| Database | MySQL 8.0 / SQLite (testing) |
| Cache | Redis |
| API Docs | Swagger / OpenAPI 3.0 (l5-swagger) |
| Containerisation | Docker + Docker Compose |
| CI/CD | GitHub Actions |

---

## 📁 Project Structure

```
inventory-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # AuthController, ProductController, CategoryController, SupplierController
│   │   ├── Middleware/         # CheckRole
│   │   ├── Requests/           # Form Request classes (validation)
│   │   │   ├── Auth/           # LoginRequest, RegisterRequest
│   │   │   └── Product/        # StoreProductRequest, UpdateProductRequest, ProductFilterRequest
│   │   └── Resources/          # API Resources (ProductResource, CategoryResource, SupplierResource, UserResource)
│   └── Models/                 # Product, Category, Supplier, User
├── database/
│   ├── factories/              # Model factories for testing/seeding
│   ├── migrations/             # All database migrations
│   └── seeders/                # DatabaseSeeder + individual seeders
├── docker/                     # Nginx, Supervisor, PHP, MySQL configs
├── routes/
│   └── api.php                 # All API routes (versioned under /api/v1)
├── tests/Feature/              # Feature tests (AuthTest, ProductTest, CategorySupplierTest)
├── .github/workflows/ci.yml   # GitHub Actions CI pipeline
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## ⚡ Quick Start

### Option A — Docker (Recommended)

**Prerequisites:** Docker Desktop ≥ 24, Docker Compose v2

```bash
# 1. Clone the repository
git clone https://github.com/YOUR_USERNAME/inventory-api.git
cd inventory-api

# 2. Copy environment file
cp .env.example .env

# 3. Start containers
docker compose up -d --build

# 4. Install dependencies
docker compose exec app composer install

# 5. Generate application key
docker compose exec app php artisan key:generate

# 6. Run migrations and seed
docker compose exec app php artisan migrate --seed

# 7. Generate Swagger docs
docker compose exec app php artisan l5-swagger:generate
```

API is now live at **http://localhost:8000**
Swagger UI at **http://localhost:8000/api/documentation**

---

### Option B — Local Setup (without Docker)

**Prerequisites:** PHP 8.2+, Composer, MySQL 8+, Redis

```bash
# 1. Clone and enter directory
git clone https://github.com/YOUR_USERNAME/inventory-api.git
cd inventory-api

# 2. Install PHP dependencies
composer install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=inventory_db
# DB_USERNAME=root
# DB_PASSWORD=your_password

# 5. Create the database
mysql -u root -p -e "CREATE DATABASE inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Run migrations + seed
php artisan migrate --seed

# 7. Generate Swagger docs
php artisan l5-swagger:generate

# 8. Start development server
php artisan serve
```

API runs at **http://localhost:8000**

---

## 🔐 Authentication

This API uses **Laravel Sanctum** with Bearer token authentication.

### Default Seeded Users

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Manager | manager@example.com | password |
| Staff | staff@example.com | password |

### Auth Flow

```bash
# Register
POST /api/v1/auth/register

# Login → get token
POST /api/v1/auth/login

# Use token on protected routes
Authorization: Bearer <your-token>

# Logout
POST /api/v1/auth/logout

# Get your profile
GET /api/v1/auth/me
```

---

## 📋 API Endpoints

All endpoints are prefixed with `/api/v1/`. Protected routes require `Authorization: Bearer {token}`.

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/register` | ❌ | Register a new user |
| POST | `/auth/login` | ❌ | Login, receive token |
| POST | `/auth/logout` | ✅ | Revoke current token |
| GET | `/auth/me` | ✅ | Get authenticated user |

### Products

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/products` | ✅ | List products (filter + paginate) |
| POST | `/products` | ✅ | Create a product |
| GET | `/products/{id}` | ✅ | Get single product |
| PUT | `/products/{id}` | ✅ | Update product |
| DELETE | `/products/{id}` | ✅ | Soft delete product |
| GET | `/products/trashed` | ✅ | List soft-deleted products |
| POST | `/products/{id}/restore` | ✅ | Restore soft-deleted product |
| DELETE | `/products/{id}/force` | ✅ | Permanently delete product |

### Categories

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/categories` | ✅ | List all categories |
| POST | `/categories` | ✅ | Create category |
| GET | `/categories/{id}` | ✅ | Get single category |
| PUT | `/categories/{id}` | ✅ | Update category |
| DELETE | `/categories/{id}` | ✅ | Delete category |

### Suppliers

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/suppliers` | ✅ | List all suppliers |
| POST | `/suppliers` | ✅ | Create supplier |
| GET | `/suppliers/{id}` | ✅ | Get single supplier |
| PUT | `/suppliers/{id}` | ✅ | Update supplier |
| DELETE | `/suppliers/{id}` | ✅ | Delete supplier |

---

## 🔍 Product Filtering & Query Parameters

`GET /api/v1/products` supports the following query parameters:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `category_id` | integer | Filter by category | `?category_id=3` |
| `category_ids[]` | array | Filter by multiple categories | `?category_ids[]=1&category_ids[]=2` |
| `supplier_id` | integer | Filter by supplier | `?supplier_id=2` |
| `min_price` | float | Minimum price | `?min_price=10.00` |
| `max_price` | float | Maximum price | `?max_price=500.00` |
| `stock_status` | string | `in_stock`, `low_stock`, `out_of_stock` | `?stock_status=low_stock` |
| `search` | string | Search name, SKU, barcode | `?search=widget` |
| `sort_by` | string | `name`, `price`, `stock_quantity`, `created_at` | `?sort_by=price` |
| `sort_direction` | string | `asc` or `desc` | `?sort_direction=asc` |
| `per_page` | integer | Items per page (1–100, default 15) | `?per_page=25` |

**Example: Combined filter**
```
GET /api/v1/products?category_id=1&min_price=10&max_price=200&stock_status=in_stock&sort_by=price&sort_direction=asc&per_page=10
```

---

## 📦 Example Requests & Responses

### Create Product

**Request:**
```json
POST /api/v1/products
Authorization: Bearer <token>
Content-Type: application/json

{
  "category_id": 1,
  "name": "Wireless Keyboard",
  "sku": "WK-2024-001",
  "price": 79.99,
  "cost_price": 45.00,
  "stock_quantity": 150,
  "low_stock_threshold": 20,
  "unit": "piece",
  "is_active": true,
  "supplier_pivot": [
    {
      "supplier_id": 1,
      "supply_price": 45.00,
      "lead_time_days": 7,
      "is_preferred": true
    }
  ]
}
```

**Response (201):**
```json
{
  "message": "Product created successfully.",
  "data": {
    "id": 42,
    "name": "Wireless Keyboard",
    "slug": "wireless-keyboard",
    "sku": "WK-2024-001",
    "price": 79.99,
    "cost_price": 45.00,
    "profit_margin": 43.75,
    "stock_quantity": 150,
    "stock_status": "in_stock",
    "is_active": true,
    "category": { "id": 1, "name": "Electronics" },
    "suppliers": [
      {
        "id": 1,
        "name": "Global Tech Distributors",
        "pivot": { "supply_price": 45.00, "lead_time_days": 7, "is_preferred": true }
      }
    ],
    "created_at": "2024-01-15T09:30:00.000000Z"
  }
}
```

### Paginated List Response

```json
{
  "data": [ ...products ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 68,
    "last_page": 5,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://localhost:8000/api/v1/products?page=1",
    "last": "http://localhost:8000/api/v1/products?page=5",
    "prev": null,
    "next": "http://localhost:8000/api/v1/products?page=2"
  }
}
```

---

## 🗄️ Data Models & Relationships

```
Category  ──< Product >──── product_supplier ────< Supplier
               │
               └── SoftDeletes
```

- **Category** `hasMany` Products
- **Product** `belongsTo` Category
- **Product** `belongsToMany` Suppliers (via `product_supplier` pivot with `supply_price`, `lead_time_days`, `is_preferred`)
- **Supplier** `belongsToMany` Products

### Product Model Features

| Feature | Details |
|---------|---------|
| **Soft Deletes** | `deleted_at` column; restore/force-delete supported |
| **Scopes** | `active()`, `lowStock()`, `outOfStock()`, `priceRange()`, `inCategory()`, `search()` |
| **Accessors** | `stock_status` (in_stock / low_stock / out_of_stock), `profit_margin` (%) |
| **Mutators** | `setNameAttribute` auto-generates slug; `setSkuAttribute` auto-generates SKU |

---

## 🧪 Running Tests

```bash
# Run all tests (uses SQLite in-memory — no DB setup needed)
php artisan test

# With coverage report
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/ProductTest.php

# Run in parallel (faster)
php artisan test --parallel

# With Docker
docker compose exec app php artisan test
```

### Test Coverage

| Test File | Tests | Coverage |
|-----------|-------|----------|
| `AuthTest` | 8 tests | Register, login, logout, guards |
| `ProductTest` | 16 tests | CRUD, filters, scopes, accessors, soft deletes |
| `CategorySupplierTest` | 11 tests | Category + Supplier CRUD, accessors |

---

## 📖 API Documentation (Swagger)

```bash
# Generate docs
php artisan l5-swagger:generate

# Access Swagger UI
http://localhost:8000/api/documentation

# Raw OpenAPI JSON
http://localhost:8000/api/documentation.json
```

To authenticate in Swagger UI:
1. Login via `POST /api/v1/auth/login`
2. Copy the `token` from the response
3. Click **Authorize** in Swagger UI
4. Enter: `Bearer <your-token>`

---

## ⚡ Caching

Products list responses are cached using Redis (configurable via `CACHE_TTL` env var, default 300 seconds).

Cache is automatically invalidated on any `create`, `update`, `delete`, or `restore` operation.

```bash
# Clear all cache manually
php artisan cache:clear

# Or via Artisan in Docker
docker compose exec app php artisan cache:clear
```

To use tagged cache in production (requires Redis), update `clearProductCache()` in `ProductController`:
```php
Cache::tags(['products'])->flush();
```

---

## 🚦 Rate Limiting

API routes are throttled using Laravel's built-in rate limiter:

- Default: **60 requests per minute** per user/IP
- Configure via `API_RATE_LIMIT` and `API_RATE_LIMIT_DECAY` in `.env`

Custom limits can be defined in `bootstrap/app.php` using `RateLimiter::for()`.

---

## 🛠️ Artisan Commands Reference

```bash
# Fresh migration with seeding
php artisan migrate:fresh --seed

# Generate a specific seeder
php artisan db:seed --class=ProductSeeder

# Create new API resource
php artisan make:resource MyResource

# Create new form request
php artisan make:request StoreMyModelRequest

# Prune expired Sanctum tokens
php artisan sanctum:prune-expired --hours=24

# Generate Swagger docs
php artisan l5-swagger:generate
```

---

## 🐳 Docker Services

| Service | Port | Description |
|---------|------|-------------|
| `app` | 8000 | Laravel application (Nginx + PHP-FPM) |
| `db` | 3306 | MySQL 8.0 database |
| `redis` | 6379 | Redis cache |
| `phpmyadmin` | 8080 | phpMyAdmin UI (run with `--profile tools`) |

```bash
# Start with phpMyAdmin
docker compose --profile tools up -d

# View logs
docker compose logs -f app

# Enter app container shell
docker compose exec app bash

# Stop all services
docker compose down

# Full reset (including volumes)
docker compose down -v
```

---

## 🔒 Security Considerations

- All tokens are hashed with SHA-256 in the database
- Passwords hashed with Bcrypt (12 rounds)
- SQL injection prevented via Eloquent query builder
- CORS handled by Laravel's built-in CORS middleware
- `X-Frame-Options`, `X-Content-Type-Options` headers set by Nginx
- Rate limiting protects against brute-force attacks
- Soft deletes prevent accidental data loss

---

## 📄 License

This project is open-sourced under the [MIT license](LICENSE).