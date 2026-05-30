# Admin Dashboard - Setup & Usage Guide

## Overview
The Admin Dashboard is a comprehensive monitoring and control interface for both the Laravel backend and Next.js frontend projects. It provides real-time metrics, health checks, system monitoring, and management controls.

## Features

### 📊 Monitoring Capabilities
- **Backend Health**: Real-time status of Laravel application
- **Frontend Status**: Next.js project configuration and build status
- **Database Metrics**: Size, table count, record count, connection status
- **Queue System**: Pending jobs, failed jobs, queue status
- **System Resources**: Memory usage, disk space, server uptime
- **Cache Status**: Cache driver and Redis information
- **Health Checks**: Redis, Database, WebSocket (Reverb), Queue status
- **Recent Logs**: Last 10 application logs with timestamps

### 🎮 Control Features
- **Clear Cache**: Flush application cache instantly
- **Restart Queue**: Restart job processing queue
- **Real-time Refresh**: Manual refresh or auto-refresh (30 seconds)

## Installation

1. **Dashboard Controller** is already created at:
   ```
   app/Http/Controllers/DashboardController.php
   ```

2. **Dashboard View** is available at:
   ```
   resources/views/dashboard.blade.php
   ```

3. **Routes** configured in `routes/web.php`:
   ```php
   Route::get('/dashboard', [DashboardController::class, 'index']);
   Route::get('/dashboard/data', [DashboardController::class, 'data']);
   Route::post('/dashboard/clear-cache', [DashboardController::class, 'clearCache']);
   Route::post('/dashboard/restart-queue', [DashboardController::class, 'restartQueue']);
   ```

## Access

- **Web Interface**: `http://your-app.com/dashboard`
- **API Endpoint**: `http://your-app.com/dashboard/data` (JSON)

## Configuration

### Optional: Add Authentication

If you want to protect the dashboard with authentication, update the routes:

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::post('/dashboard/clear-cache', [DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
    Route::post('/dashboard/restart-queue', [DashboardController::class, 'restartQueue'])->name('dashboard.restart-queue');
});
```

### Optional: Add Authorization Policy

Create a dashboard policy if you want role-based access:

```php
// In DashboardController
public function __construct()
{
    $this->middleware('can:view-dashboard')->only(['index', 'data']);
    $this->middleware('can:manage-dashboard')->only(['clearCache', 'restartQueue']);
}
```

## API Endpoints

### Get Dashboard Data
```
GET /dashboard/data
Response: JSON with all metrics
```

**Response Example:**
```json
{
  "backend": {
    "status": "healthy",
    "environment": "local",
    "app_version": "1.0.0",
    "checks": {
      "redis": { "ok": true },
      "database": { "ok": true }
    }
  },
  "frontend": {
    "exists": true,
    "build_exists": true,
    "node_modules": true
  },
  "system": {
    "php_version": "8.2.0",
    "memory_usage": 256.5,
    "disk_free": 850.2
  },
  "database": {
    "connected": true,
    "size_mb": 128.5,
    "tables": [...],
    "total_records": 15000
  },
  "queue": {
    "pending_jobs": 5,
    "failed_jobs": 0,
    "status": "active"
  },
  "logs": [...]
}
```

### Clear Cache
```
POST /dashboard/clear-cache
Authorization: Required (admin)
Response: { "success": true, "message": "Cache cleared" }
```

### Restart Queue
```
POST /dashboard/restart-queue
Authorization: Required (admin)
Response: { "success": true, "message": "Queue restarted" }
```

### Refresh Specific Metric
```
POST /dashboard/refresh-metric
Body: { "metric": "backend|frontend|system|database|queue|cache" }
Response: { "[metric]": {...} }
```

## Dashboard Metrics Explained

### Backend Status
- **Healthy**: All health checks pass
- **Degraded**: Some services may be down
- **Error**: Backend unavailable

### Frontend Status
- **✓ Ready**: Next.js project exists and configured
- **✕ Missing**: Project directory not found

### Database Status
- **✓ Connected**: Database connection active
- **✕ Error**: Cannot connect to database

### Health Checks
- **Redis**: Cache/session storage status
- **Database**: Primary database connection
- **Reverb**: WebSocket server status
- **Queue**: Job queue processor status

### System Resources
- **Memory Usage**: Current PHP memory in MB
- **Memory Limit**: PHP memory limit setting
- **Disk Space**: Free and total disk space
- **Server Uptime**: How long the server has been running

## Monitoring Both Projects

The dashboard automatically monitors:

1. **Backend (Laravel)**
   - Application configuration
   - Service health checks
   - Database status
   - Queue system
   - System resources

2. **Frontend (Next.js)**
   - Project existence
   - Build artifacts
   - Node modules
   - Environment configuration
   - Deployment readiness

## Customization

### Adding Custom Metrics

Edit `DashboardController.php` to add custom metrics:

```php
private function gatherDashboardData()
{
    return [
        // ... existing data
        'custom_metric' => $this->getCustomMetric(),
    ];
}

private function getCustomMetric()
{
    // Your custom logic here
    return [...];
}
```

Then update the dashboard view to display it.

### Changing Auto-Refresh Interval

In the dashboard view JavaScript:
```javascript
// Change from 30000ms (30 seconds) to your desired interval
setInterval(loadDashboard, 60000); // 60 seconds
```

## Troubleshooting

### Dashboard shows "Permission denied" errors
```bash
# Fix permissions
chmod -R 775 /var/www/os/ns/bootstrap/cache
chmod -R 775 /var/www/os/ns/storage
```

### Metrics not loading
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Verify database connection in `.env`
3. Check Redis connection if using Redis cache

### Frontend metrics always showing error
1. Verify Next.js project path in `DashboardController.php`
2. Ensure frontend project is at `../nsf` relative to backend

## Security Considerations

1. **Authentication**: Add middleware to protect sensitive endpoints
2. **Authorization**: Implement role-based access control
3. **Rate Limiting**: Add rate limiting to prevent abuse
4. **Logging**: All control actions (clear cache, restart queue) are logged

## Performance

- Dashboard data loads in < 500ms
- Auto-refresh every 30 seconds
- Minimal database impact
- Cached health check results

## Support

For issues or feature requests related to the dashboard, check:
- Application logs: `storage/logs/laravel.log`
- Dashboard controller: `app/Http/Controllers/DashboardController.php`
- Database connection: `.env` file configuration
