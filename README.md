# CourseHub — Online Course Platform

A full-stack course selling platform with video streaming, admin panel, payments, and progress tracking.

## Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 13, Sanctum, Filament 4 |
| Frontend | React 19, TypeScript, Vite, Tailwind CSS 4 |
| Video | FFmpeg HLS transcoding, Nginx streaming |
| Payments | Stripe Checkout |
| Infrastructure | Docker Compose (Nginx, PHP-FPM, MySQL, Redis) |

## Architecture

```
┌─────────────┐     ┌─────────────┐     ┌──────────────┐
│  React SPA  │────▶│    Nginx    │────▶│   PHP-FPM    │
│  (Vite)     │     │  :80        │     │   Laravel    │
└─────────────┘     └──────┬──────┘     └──────┬───────┘
                           │                    │
                    HLS stream              ┌────┴────┐
                           │              │         │
                    ┌──────▼──────┐  ┌────▼───┐ ┌───▼────┐
                    │ Video Store │  │ MySQL  │ │ Redis  │
                    │ (volume)    │  │        │ │ Queue  │
                    └─────────────┘  └────────┘ └────────┘
```

### Key flows

1. **Admin uploads video** → Filament panel (`/admin`) or API → raw file stored on `videos` disk → `ProcessVideoJob` transcodes to HLS via FFmpeg → status becomes `ready`
2. **Student watches lesson** → API returns signed stream URL (2h expiry) → React player uses hls.js for adaptive playback
3. **Purchase** → Stripe Checkout session → webhook/verify marks order `paid` → lesson content unlocked via policies

## Project structure

```
project-root/
├── backend/          # Laravel API + Filament admin
├── frontend/         # React SPA
├── docker/
│   ├── nginx/
│   └── php/
└── docker-compose.yml
```

## Quick start

### 1. Environment

```bash
cp backend/.env.example backend/.env
```

Add your Stripe keys to `backend/.env`.

### 2. Start Docker

```bash
docker compose up -d --build
```

### 3. Install & migrate backend

```bash
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
docker compose exec php php artisan filament:assets
docker compose exec php php artisan migrate --seed
docker compose exec php php artisan storage:link
```

### 4. Frontend (development)

The `frontend` service runs Vite on **http://localhost:5173**.

For production, build and serve via Nginx:

```bash
cd frontend && npm run build
```

## Default accounts (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@courses.test | password |
| Student | student@courses.test | password |

- **Admin panel**: http://localhost:8080/admin
- **API**: http://localhost:8080/api/v1
- **Frontend dev**: http://localhost:5173

Or run everything with one command:

```bash
make setup
```

## API endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/courses` | Public course catalog |
| GET | `/api/v1/courses/{slug}` | Course detail + curriculum |
| POST | `/api/v1/register` | Register |
| POST | `/api/v1/login` | Login (returns Sanctum token) |
| GET | `/api/v1/my-courses` | Purchased courses (auth) |
| GET | `/api/v1/lessons/{id}` | Lesson detail (auth + policy) |
| GET | `/api/v1/videos/{id}/signed-url` | Signed HLS stream URL |
| POST | `/api/v1/checkout/{slug}` | Create Stripe session |
| POST | `/api/v1/admin/videos/upload` | Upload video (admin) |

## Video processing

Videos are transcoded to HLS (720p) with FFmpeg in a queued job. The `queue` container runs `php artisan queue:work`. Monitor processing status in the Filament Videos resource.

## Switching to S3

Update `config/filesystems.php` — the `videos` disk can be changed to `s3`. Use `Storage::temporaryUrl()` for signed URLs in production.

## License

MIT
