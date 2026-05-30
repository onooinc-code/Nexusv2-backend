# Deployment Readiness Report

## Project Path
- Repository root: `/var/www/os/ns`
- Public URL target: `https://os.square-ltd.com`

## Executive Summary
- The current repository is a Laravel 11 application with a Vue 3 / Vite frontend.
- It is not a fully realized Next.js app inside this repo; the frontend codebase is Vue-based under `resources/js/`.

- The project includes real-time broadcast support via Laravel Echo and a `reverb` broadcasting configuration.

- Redis queue support is configured and Horizon is installed, but the repo does not contain a complete supervisor config file.

- To run on your Ubuntu VPS with Webmin, you must install PHP, Composer, Node, Redis, Supervisor, and configure Nginx (or Apache) plus WebSocket proxying.

## Verified Technology Coverage

### Laravel Reverb
- Frontend uses `laravel-echo` and `pusher-js` in `resources/js/bootstrap.js`.
- `broadcasting.php` defines a `reverb` connection.

- `.env` contains Reverb variables: `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`.

- Health check command exists: `app/Console/Commands/MonitorReverbHealth.php`.

- Monitoring and health endpoint support is implemented in `app/Http/Controllers/Monitoring/HealthController.php`.

### Laravel Echo
- `package.json` includes `laravel-echo` and `pusher-js`.

- `resources/js/bootstrap.js` initializes `window.Echo` with `broadcaster: 'reverb'`.
- There are live Echo utilities in `resources/js/composables/useEcho.js`.
- The Vue app imports `./bootstrap` from `resources/js/app.js`.

### WebSockets
- Backend events implement `ShouldBroadcast` / `ShouldBroadcastNow` across many files in `app/Events/`.
- Private channel definitions exist in `routes/channels.php`.
- `config/broadcasting.php` supports both `pusher` and `reverb` drivers.
- The route `broadcasting/auth` is available for private channel authorization.

### Queue (Redis)
- `config/queue.php` includes a Redis queue connection.
- `.env` sets `QUEUE_CONNECTION=redis`.
- `config/database.php` and `.env` cover Redis host/port and client.
- `composer.json` includes `laravel/horizon` and `app/Providers/HorizonServiceProvider.php` is registered.
- `config/horizon.php` is fully configured with supervisors for Redis queues.

### Supervisor / Horizon
- Horizon support is present via `laravel/horizon` and `config/horizon.php`.
- The repository does not include a ready-made Linux Supervisor configuration file, so it must be created during deployment.
- `app/Console/Kernel.php` schedules the `monitor:reverb-health` command every five minutes.

## Gaps and Important Notes
- The current `.env` file sets `BROADCAST_DRIVER=pusher`, while the frontend initialises `reverb`. For a Reverb-based deployment, change `.env` to:
  - `BROADCAST_DRIVER=reverb`

- There is no evidence of a built-in Laravel Reverb server package in this repo. The project expects an external or separate Reverb-compatible WebSocket server to be available at the host/port configured in `.env`.

- The repo has no Next.js app code under `app/`, `pages/`, or a dedicated Next.js directory. The actual frontend files are Vue single-file components under `resources/js/`.

- No `supervisor` config file is present in the repository, so supervisor must be configured manually on the server.

## Ready-to-Run Assessment
- Backend: ready for Redis queue + Horizon and event broadcasting, assuming dependencies are installed and environment variables are correct.

- Frontend: ready for Laravel Echo / Reverb if WebSocket server is running and the browser code is built.
- Missing pieces before production deployment:
  1. Reverb WebSocket server process (internal/external)
  2. Supervisor config for `php artisan horizon` or queue workers
  3. Proper Nginx/Apache proxy config for HTTPS + WebSocket traffic
  4. Correct `.env` production values for `BROADCAST_DRIVER`, Redis, and host names

## Ubuntu / VPS Setup Steps

### 1. Update OS and install base packages
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx git curl unzip software-properties-common
```

### 2. Install PHP 8.2 and required PHP extensions
```bash
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl php8.2-json php8.2-zip php8.2-mysql php8.2-redis php8.2-intl
```

### 3. Install Composer
```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

### 4. Install Node.js and npm
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

### 5. Install Redis
```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server --now
```

### 6. Install Supervisor
```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor --now
```

### 7. Deploy the repository
```bash
cd /var/www/os/ns
sudo chown -R www-data:www-data .
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

### 8. Configure `.env`
- `APP_URL=https://os.square-ltd.com`
- `QUEUE_CONNECTION=redis`
- `BROADCAST_DRIVER=reverb`
- `REDIS_HOST=127.0.0.1`
- `REDIS_PORT=6379`
- set `DB_*` values for MySQL
- set `VITE_REVERB_HOST=os.square-ltd.com`
- set `VITE_REVERB_PORT=443`
- set `VITE_REVERB_SCHEME=https`
- optionally set `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_ID` if using Pusher fallback

### 9. Run database migrations and caching
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan horizon:install
```

### 10. Configure Supervisor for Horizon
Create `/etc/supervisor/conf.d/nexus-horizon.conf` with:
```ini
[program:nexus-horizon]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/os/ns/artisan horizon
directory=/var/www/os/ns
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/os/ns/storage/logs/horizon.log
stopwaitsecs=3600
```
Then reload:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start nexus-horizon:*
```

### 11. Configure Nginx / WebSocket proxy
- Ensure `server_name os.square-ltd.com;`
- Forward PHP to `php8.2-fpm`.
- Proxy WebSocket traffic to your Reverb server if it is not served directly over port 443.
- Example WebSocket proxy block:
```nginx
location /app {
    proxy_pass http://127.0.0.1:6001;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 86400;
}
```
- If Reverb is served over 443 behind Nginx, use a `location /` or dedicated path depending on your WebSocket entrypoint.

### 12. Start and verify services
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart redis-server
sudo supervisorctl status
php artisan horizon:status
php artisan monitor:reverb-health
```

## Quick verification commands
- `php artisan horizon:status`
- `php artisan queue:listen --tries=1`
- `php artisan monitor:reverb-health`
- `curl -I https://os.square-ltd.com`
- `curl -sS https://os.square-ltd.com/api/v1/monitoring/health/reverb`

## Final Recommendation
- The repo is structurally ready for Redis queue + Laravel Echo + Horizon, but it still depends on a working Reverb server and correct production `.env` settings.
- If your goal is to use `Laravel Reverb`, set `BROADCAST_DRIVER=reverb` and validate the Reverb host/port.
- For production, add a Supervisor config and a proper Nginx proxy for WebSocket traffic.
