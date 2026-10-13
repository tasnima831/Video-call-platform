# Meetly — Video Calling App

Meetly is a Laravel and Vue web app for creating meeting rooms and joining them with a link. It includes account registration and sign-in, a dashboard, meeting history, participant waiting/approval, in-room chat, notes, recordings, plan selection, and transcript summaries. Live audio/video is provided by LiveKit.

## What you need

Install these before starting:

- PHP 8.2 or newer with the extensions required by Laravel (including PDO and the driver for your database)
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) and npm
- MySQL 8+ (or configure another Laravel-supported database)
- A LiveKit server/project for actual audio and video calls

## Clone the project

Use the repository URL supplied by the project owner in place of `<repository-url>`:

```bash
git clone <repository-url>
```

## Install and configure

1. Install PHP dependencies and create your local environment file:

   ```bash
   composer install
   cp .env.example .env
   ```


2. Edit `.env`. The checked-in example is configured for MySQL. Create a database (the default name is `video_call`) and set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match your local MySQL installation.

   To use SQLite instead, set `DB_CONNECTION=sqlite`, set `DB_DATABASE` to the absolute path of a SQLite database file, and create that file before migrating. Ensure PHP's SQLite PDO extension is enabled.

3. Generate the application key and apply database migrations:

   ```bash
   php artisan key:generate
   php artisan migrate
   ```

4. Install frontend dependencies and build the assets:

   ```bash
   npm install
   npm run build
   ```

5. Configure LiveKit in `.env` for real calls:

   ```dotenv
   LIVEKIT_URL=wss://your-livekit-host
   LIVEKIT_API_KEY=your-api-key
   LIVEKIT_API_SECRET=your-api-secret
   ```

   Use the WebSocket URL and credentials from your LiveKit server/project. Without these values, the web app can load, but joining a live video meeting will fail. Keep API secrets private.

## Run locally

Terminal 1 — Laravel backend:

```bash
php artisan serve
```

Terminal 2 — Vite frontend in development mode:

```bash
npm run dev
```

Open the URL:
'''
http://127.0.0.1:8000/login
(http://127.0.0.1:8000/dashboard)
'''
Register an account, then use the dashboard to create a meeting and share its invitation link. Allow camera and microphone access in your browser. For people on separate devices or networks, the app and LiveKit server must both be reachable by those devices; HTTPS is generally required for camera/microphone access outside localhost.

For a production-style frontend build, run `npm run build` and serve the Laravel app with a web server configured for Laravel's `public/` directory.

## Main project areas

```text
app/                 Laravel controllers, models, and services
resources/js/        Vue pages, components, and frontend entry point
resources/views/     Laravel Blade shell
routes/              Web and API route definitions
database/migrations/ Database schema
src/                 Additional video-call Vue/service code
public/              Public web root and built assets
```

Useful pages after signing in include Dashboard, Meetings, Notes, Summaries, and Plans. The welcome page is available at `/`.

## Useful commands

```bash
php artisan route:list       # Show registered application routes
php artisan migrate:status   # Check database migrations
php artisan migrate          # Apply new migrations
npm run dev                  # Start the Vite development server
npm run build                # Build frontend assets
```

## Troubleshooting

- **Database connection error:** Check that the database service is running, the database exists, and the `DB_*` values in `.env` are correct. Run `php artisan config:clear` after changing environment settings.
- **Missing application key:** Run `php artisan key:generate`.
- **Video call cannot connect:** Check all three `LIVEKIT_*` values, confirm the LiveKit server is reachable, and allow camera/microphone access in the browser.
- **Frontend assets are missing:** Run `npm install` and `npm run build`, or keep `npm run dev` running during development.
- **Permission or cached configuration issues:** Run `php artisan optimize:clear` and retry.
