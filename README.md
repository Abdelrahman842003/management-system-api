# Task Management System API

A RESTful API for managing tasks with role-based access control, task dependencies, and filtering capabilities.

> 📊 **Database Schema**: See [ERD Diagram](erd.drawio) for complete database structure and relationships.

## Features

- **Authentication**: Stateless token-based authentication (Laravel Sanctum)
- **Role-Based Access Control**: Manager and User roles with granular permissions
- **Task Management**: Full CRUD operations with validation
- **Task Dependencies**: Manage dependencies with circular dependency prevention
- **Advanced Filtering**: Filter by status, date range, and assigned user
- **Comprehensive Testing**: 41 tests with 190 assertions

## Technology Stack

- PHP 8.2+
- Laravel 12
- MySQL 8.0
- Laravel Sanctum
- Spatie Laravel Permission
- Pest/PHPUnit

## Quick Start

### Prerequisites

- PHP 8.2+ and Composer
- MySQL 8.0
- (Optional) Docker & Docker Compose

### Installation

1. **Clone and install dependencies:**
```bash
composer install
cp .env.example .env
```

2. **Configure database in `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=your_password
```

3. **Setup and run:**
```bash
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

**API Base URL:** `http://localhost:8000/api`

### Using Docker (Optional)

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
```

**API Base URL:** `http://localhost/api`

## Test Users

After seeding, you can use these credentials:

**Managers:**
- `manager1@example.com` / `password`
- `manager2@example.com` / `password`

**Users:**
- `user1@example.com` / `password`
- `user2@example.com` / `password`
- `user3@example.com` / `password`
- `user4@example.com` / `password`
- `user5@example.com` / `password`

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login and get token |
| POST | `/api/auth/logout` | Revoke token |
| GET | `/api/auth/user` | Get authenticated user |

### Tasks

| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | `/api/v1/tasks` | List tasks (with filters) | Auto |
| POST | `/api/v1/tasks` | Create task | `create-task` |
| GET | `/api/v1/tasks/{id}` | Get task details | Auto |
| PUT/PATCH | `/api/v1/tasks/{id}` | Update task | `update-task` |
| DELETE | `/api/v1/tasks/{id}` | Delete task | `update-task` |
| PATCH | `/api/v1/tasks/{id}/status` | Update status | `update-task-status` |

### Task Dependencies

| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| POST | `/api/v1/tasks/{id}/dependencies` | Add dependency | `update-task` |
| DELETE | `/api/v1/tasks/{id}/dependencies/{depId}` | Remove dependency | `update-task` |

## Permissions

### Manager Role
- Can create, update, and delete tasks
- Can assign tasks to users
- Can view all tasks
- Can manage task dependencies

### User Role
- Can view only assigned tasks
- Can update status of assigned tasks only

## API Response Format

### Success Response
```json
{
  "status": 200,
  "message": "Success message",
  "timestamp": "2025-11-14T20:00:00+00:00",
  "data": {
    "id": 1,
    "title": "Task Title",
    "status": "pending",
    ...
  }
}
```

### Error Response
```json
{
  "status": 422,
  "message": "Validation failed",
  "timestamp": "2025-11-14T20:00:00+00:00",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

## Usage Examples

### Register
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Manager",
    "email": "manager@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "manager"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "manager1@example.com",
    "password": "password"
  }'
```

### Create Task
```bash
curl -X POST http://localhost:8000/api/v1/tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Complete Documentation",
    "description": "Write API docs",
    "assigned_to": 3,
    "due_date": "2025-12-31"
  }'
```

### Filter Tasks
```bash
# By status
GET /api/v1/tasks?status=pending

# By assigned user
GET /api/v1/tasks?assigned_to=3

# By date range
GET /api/v1/tasks?due_date_from=2025-01-01&due_date_to=2025-12-31
```

## Business Rules

1. Only managers can create, update, and delete tasks
2. Users can only view tasks assigned to them
3. Users can only update the status of assigned tasks
4. Tasks cannot be completed until all dependencies are completed
5. Circular dependencies are prevented

## Testing

```bash
# Run all tests
php artisan test

# Run specific suite
php artisan test --filter=AuthTest

# With coverage
php artisan test --coverage
```

**Test Results:** 41 tests passing (190 assertions)

## Code Quality

```bash
# Format code
./vendor/bin/pint
```

## Postman Collection

Import `Task-Management-API.postman_collection.json` for ready-to-use API requests.

## Project Structure

```
app/
├── Http/
│   ├── Controllers/Api/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Services/
└── Traits/
database/
├── migrations/
└── seeders/
tests/
└── Feature/Api/
```

## License

MIT License
