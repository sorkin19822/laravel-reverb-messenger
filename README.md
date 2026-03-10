# Laravel Reverb Messenger

Real-time messenger built with Laravel 11, WebSockets via Laravel Reverb, Redis broadcasting, and MySQL. Users can register, log in, and exchange messages that are delivered instantly without page reload.

## Stack

| Component | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.4 |
| WebSocket server | Laravel Reverb |
| Broadcasting | Redis |
| Database | MySQL 8 |
| Frontend | Blade, Pusher.js, Vite + Tailwind CSS 4 |
| Infrastructure | Docker, Docker Compose, Nginx |

## Requirements

- [Docker](https://docs.docker.com/get-docker/) 24+
- [Docker Compose](https://docs.docker.com/compose/install/) v2+
- Ports **8080** (HTTP), **8081** (WebSocket), **3306** (MySQL), **6379** (Redis) must be free

## Quick Start

### Linux / macOS

```bash
# 1. Clone the repository
git clone <repo-url>
cd laravel-reverb-messenger

# 2. Copy environment file
cp .env.example .env

# 3. Build images, start containers, run migrations, build assets
make setup

# 4. Open in browser
open http://localhost:8080
```

`make setup` automatically:
- Starts all 6 Docker containers
- Waits for MySQL to be ready (via healthcheck)
- Generates `APP_KEY`
- Runs migrations
- Creates `storage` symlink
- Installs npm dependencies and builds assets

### Windows (PowerShell)

`make` is not available in PowerShell — run the steps manually:

```powershell
# 1. Clone the repository
git clone <repo-url>
cd laravel-reverb-messenger

# 2. Copy environment file
cp .env.example .env

# 3. Start containers
docker compose up -d

# 4. Wait ~15 seconds for MySQL to be ready, then run setup
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker run --rm -v ${PWD}:/app -w /app node:20-alpine npm install
docker run --rm -v ${PWD}:/app -w /app node:20-alpine npm run build

# 5. Open in browser
start http://localhost:8080
```

## Manual Setup (step by step)

```bash
# Build Docker images
make build

# Start containers
make up

# Generate application key
make key

# Run migrations
make migrate

# (Optional) seed test users: alice@example.com, bob@example.com, charlie@example.com
docker compose exec app php artisan db:seed

# Install npm dependencies and build assets
make npm-install
make npm-build
```

## Services

| Container | URL | Description |
|---|---|---|
| `messenger_nginx` | http://localhost:8080 | Web application |
| `messenger_reverb` | ws://localhost:8081 | WebSocket server |
| `messenger_mysql` | localhost:3306 | MySQL database |
| `messenger_redis` | localhost:6379 | Redis |
| `messenger_app` | — | PHP-FPM |
| `messenger_queue` | — | Queue worker |

## Environment Variables

Key variables in `.env`:

```dotenv
# Application
APP_URL=http://localhost:8080
APP_DEBUG=false               # set true only in local development

# Database
DB_HOST=mysql                 # Docker service name
DB_DATABASE=messenger
DB_USERNAME=messenger
DB_PASSWORD=your-db-password  # change before deploying

# Redis
REDIS_HOST=redis              # Docker service name

# Broadcasting
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=redis

# Reverb — browser client (host-machine facing)
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http

# Reverb — server side (Docker-internal)
REVERB_SERVER_HOST=reverb     # Docker service name
REVERB_SERVER_PORT=8080
```

> **Note:** `REVERB_HOST/PORT` are used by the browser to connect via WebSocket.
> `REVERB_SERVER_HOST/PORT` are used by the PHP app container to publish events to Reverb over the Docker internal network.

## Makefile Commands

```bash
make build        # Build Docker images (no cache)
make up           # Start all containers
make down         # Stop all containers
make setup        # Full first-time setup (build + up + migrate + assets)
make migrate      # Run database migrations
make fresh        # Drop all tables, re-migrate and seed
make shell        # Open bash shell inside app container
make logs         # Tail logs from all containers
make cache-clear  # Clear Laravel caches (config, route, view, cache)
make npm-install  # Install npm dependencies via Node container
make npm-build    # Build frontend assets via Node container
```

## Running Tests

```bash
docker compose exec app php artisan test
```

```
Tests:    34 passed (83 assertions)
Duration: ~1s
```

Test coverage:
- `Auth/RegistrationTest` — registration form validation (7 tests)
- `Auth/LoginTest` — login, logout, redirects (5 tests)
- `UserListTest` — user list, unread badge (4 tests)
- `MessageTest` — chat, send, broadcast, self-chat guard, isolation (12 tests)
- `MessageModelTest` — model relations and casts (4 tests)

## Architecture

```
Browser A                                        Browser B
    │                                                │
    │ POST /chat/{B}/messages                        │
    ▼                                                │
[Nginx :8080]                                        │
    │                                                │
    ▼                                                │
[PHP-FPM app]                                        │
MessageController::store()                           │
    ├── Message::save() ──────────────► [MySQL]      │
    └── broadcast(MessageSent) ──────► [Redis Queue] │
                                            │        │
                                     [Queue Worker]  │
                                            │        │
                                       [Reverb :8081]│
                                            └───────►│
                                         WebSocket   │
                                    private-chat.B.id│
                                                     ▼
                                         appendMessage() — no reload
```

**Key design decisions:**
- `ShouldBroadcast` (not `ShouldBroadcastNow`) — event goes through Redis queue, HTTP response is not blocked
- `PrivateChannel('chat.{receiver_id}')` — only the recipient can subscribe, authorization enforced in `routes/channels.php`
- `toOthers()` — sender receives their own message via JSON response, not via WebSocket (prevents duplicates)
- `DB::transaction` wraps fetch + mark-as-read — prevents race condition with concurrent messages
- Missed messages tracked via `is_read` flag — shown as badge on user list on next login

## Project Structure

```
app/
├── Events/
│   └── MessageSent.php          # ShouldBroadcast event → PrivateChannel
├── Http/Controllers/
│   ├── Auth/
│   │   ├── LoginController.php  # login, logout
│   │   └── RegisterController.php
│   ├── MessageController.php    # chat view, send message
│   └── UserController.php       # user list with unread counts
└── Models/
    ├── Message.php               # sender/receiver relations, conversation(), markAsRead()
    └── User.php                  # sentMessages/receivedMessages relations

database/
├── factories/MessageFactory.php
├── migrations/
│   └── ..._create_messages_table.php  # sender_id, receiver_id, body, is_read
└── seeders/DatabaseSeeder.php   # alice, bob, charlie (password: password)

docker/
├── nginx/default.conf           # reverse proxy + security headers + CSP
└── php/Dockerfile               # PHP 8.4-fpm + pdo_mysql + redis + sockets

resources/views/
├── layouts/app.blade.php
├── auth/{login,register}.blade.php
├── users/index.blade.php        # user list + unread badge
└── messages/chat.blade.php      # real-time chat + Pusher.js WebSocket

routes/
├── web.php                      # HTTP routes with throttle middleware
└── channels.php                 # private channel authorization
```

## Test Credentials (after `db:seed`)

| Name | Email | Password |
|---|---|---|
| Alice | alice@example.com | password |
| Bob | bob@example.com | password |
| Charlie | charlie@example.com | password |

## Troubleshooting

**WebSocket not connecting (`Connecting...` status)**
```bash
# Check Reverb is running
docker compose logs reverb --tail=20

# Verify REVERB_HOST/PORT in .env match your browser access URL
# Default: localhost:8081
```

**Messages not delivered (stuck in queue)**
```bash
# Check queue worker
docker compose logs queue --tail=20

# Verify REVERB_SERVER_HOST=reverb (Docker service name, not localhost)
docker compose exec app php artisan queue:work redis --once
```

**500 error on first start**
```bash
# Generate app key if missing
make key

# Clear config cache
make cache-clear
```

**Assets not loading**
```bash
make npm-install && make npm-build
```

**Reset everything**
```bash
make down
docker volume rm laravel-reverb-messenger_mysql_data
make setup
```
