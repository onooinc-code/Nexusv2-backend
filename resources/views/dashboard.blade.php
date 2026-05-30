<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-healthy { @apply bg-green-100 text-green-800; }
        .status-degraded { @apply bg-yellow-100 text-yellow-800; }
        .status-error { @apply bg-red-100 text-red-800; }
        .status-offline { @apply bg-gray-100 text-gray-800; }

        .metric-card {
            transition: all 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .loading-skeleton {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                        <p class="text-gray-500 text-sm mt-1">{{ config('app.name') }} - System Monitoring & Control</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p id="current-time" class="text-sm font-medium text-gray-900"></p>
                            <p id="current-date" class="text-xs text-gray-500"></p>
                        </div>
                        <button onclick="refreshDashboard()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                            🔄 Refresh
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="metric-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Backend Status</p>
                            <div id="backend-status" class="text-2xl font-bold text-gray-900 mt-2">
                                <span class="loading-skeleton w-20 h-8 inline-block rounded"></span>
                            </div>
                        </div>
                        <div class="text-3xl">⚙️</div>
                    </div>
                </div>

                <div class="metric-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Frontend Status</p>
                            <div id="frontend-status" class="text-2xl font-bold text-gray-900 mt-2">
                                <span class="loading-skeleton w-20 h-8 inline-block rounded"></span>
                            </div>
                        </div>
                        <div class="text-3xl">⚛️</div>
                    </div>
                </div>

                <div class="metric-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Database Status</p>
                            <div id="db-status" class="text-2xl font-bold text-gray-900 mt-2">
                                <span class="loading-skeleton w-20 h-8 inline-block rounded"></span>
                            </div>
                        </div>
                        <div class="text-3xl">💾</div>
                    </div>
                </div>

                <div class="metric-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Pending Jobs</p>
                            <div id="queue-status" class="text-2xl font-bold text-gray-900 mt-2">
                                <span class="loading-skeleton w-20 h-8 inline-block rounded"></span>
                            </div>
                        </div>
                        <div class="text-3xl">📋</div>
                    </div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Backend Section -->
                <div class="lg:col-span-2 space-y-6">
                    <section class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Backend Metrics</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">Environment</span>
                                <span id="env-type" class="font-medium text-gray-900">
                                    <span class="loading-skeleton w-16 h-6 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">Laravel Version</span>
                                <span id="laravel-version" class="font-medium text-gray-900">
                                    <span class="loading-skeleton w-16 h-6 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">PHP Version</span>
                                <span id="php-version" class="font-medium text-gray-900">
                                    <span class="loading-skeleton w-16 h-6 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">Debug Mode</span>
                                <span id="debug-mode" class="font-medium text-gray-900">
                                    <span class="loading-skeleton w-12 h-6 inline-block rounded"></span>
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- Health Checks -->
                    <section class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Health Checks</h2>
                        <div id="health-checks" class="space-y-2">
                            <div class="loading-skeleton h-6 mb-2 rounded"></div>
                            <div class="loading-skeleton h-6 mb-2 rounded"></div>
                            <div class="loading-skeleton h-6 mb-2 rounded"></div>
                        </div>
                    </section>

                    <!-- System Resources -->
                    <section class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">System Resources</h2>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Memory Usage</span>
                                    <span id="memory-usage" class="text-sm font-medium">
                                        <span class="loading-skeleton w-20 h-5 inline-block rounded"></span>
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div id="memory-bar" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Disk Space</span>
                                    <span id="disk-usage" class="text-sm font-medium">
                                        <span class="loading-skeleton w-20 h-5 inline-block rounded"></span>
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div id="disk-bar" class="bg-green-600 h-2 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Frontend Status -->
                    <section class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Frontend (Next.js)</h2>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                <span class="text-gray-600 text-sm">Project</span>
                                <span id="frontend-exists" class="text-xs font-medium">
                                    <span class="loading-skeleton w-12 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                <span class="text-gray-600 text-sm">Build</span>
                                <span id="frontend-build" class="text-xs font-medium">
                                    <span class="loading-skeleton w-12 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                <span class="text-gray-600 text-sm">Node Modules</span>
                                <span id="frontend-modules" class="text-xs font-medium">
                                    <span class="loading-skeleton w-12 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between pb-2 border-b border-gray-200">
                                <span class="text-gray-600 text-sm">Env Config</span>
                                <span id="frontend-env" class="text-xs font-medium">
                                    <span class="loading-skeleton w-12 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- Database Stats -->
                    <section class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Database</h2>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm pb-2 border-b border-gray-200">
                                <span class="text-gray-600">Size</span>
                                <span id="db-size" class="font-medium">
                                    <span class="loading-skeleton w-16 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex justify-between text-sm pb-2 border-b border-gray-200">
                                <span class="text-gray-600">Tables</span>
                                <span id="db-tables" class="font-medium">
                                    <span class="loading-skeleton w-8 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex justify-between text-sm pb-2 border-b border-gray-200">
                                <span class="text-gray-600">Records</span>
                                <span id="db-records" class="font-medium">
                                    <span class="loading-skeleton w-12 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- Queue Stats -->
                    <section class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Queue Status</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm pb-2 border-b border-gray-200">
                                <span class="text-gray-600">Pending</span>
                                <span id="queue-pending" class="font-medium">
                                    <span class="loading-skeleton w-8 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                            <div class="flex justify-between text-sm pb-2 border-b border-gray-200">
                                <span class="text-gray-600">Failed</span>
                                <span id="queue-failed" class="font-medium">
                                    <span class="loading-skeleton w-8 h-5 inline-block rounded"></span>
                                </span>
                            </div>
                            <button onclick="restartQueue()" class="w-full mt-4 px-3 py-2 bg-orange-600 text-white rounded text-sm font-medium hover:bg-orange-700 transition">
                                ⚡ Restart Queue
                            </button>
                        </div>
                    </section>

                    <!-- Controls -->
                    <section class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Controls</h2>
                        <div class="space-y-2">
                            <button onclick="clearCache()" class="w-full px-3 py-2 bg-red-600 text-white rounded text-sm font-medium hover:bg-red-700 transition">
                                🗑️ Clear Cache
                            </button>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Recent Logs -->
            <section class="bg-white rounded-lg shadow p-6 mt-8">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Recent Logs</h2>
                <div id="recent-logs" class="space-y-2 max-h-96 overflow-y-auto font-mono text-xs">
                    <div class="loading-skeleton h-4 mb-2 rounded"></div>
                    <div class="loading-skeleton h-4 mb-2 rounded"></div>
                    <div class="loading-skeleton h-4 mb-2 rounded"></div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString();
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Status badge helper
        function getStatusBadge(status) {
            const statusMap = {
                'healthy': 'status-healthy',
                'active': 'status-healthy',
                'connected': 'status-healthy',
                'idle': 'status-healthy',
                'degraded': 'status-degraded',
                'error': 'status-error',
                'offline': 'status-offline',
                'unknown': 'status-offline',
            };
            return statusMap[status?.toLowerCase()] || 'status-offline';
        }

        function getStatusText(status) {
            const map = {
                'healthy': '✓ Healthy',
                'active': '✓ Active',
                'connected': '✓ Connected',
                'idle': '⊙ Idle',
                'degraded': '⚠ Degraded',
                'error': '✕ Error',
                'offline': '✕ Offline',
                'unknown': '? Unknown',
            };
            return map[status?.toLowerCase()] || '? Unknown';
        }

        function getCheckmark(value) {
            return value ? '✓' : '✕';
        }

        function getCheckStatus(value) {
            return value ? 'text-green-600' : 'text-red-600';
        }

        // Load dashboard data
        async function loadDashboard() {
            try {
                const response = await axios.get('/dashboard/data');
                const data = response.data;

                // Backend
                const backendStatus = data.backend?.status || 'unknown';
                document.getElementById('backend-status').innerHTML =
                    `<span class="status-badge ${getStatusBadge(backendStatus)}">${getStatusText(backendStatus)}</span>`;
                document.getElementById('env-type').textContent = data.backend?.environment || 'unknown';
                document.getElementById('laravel-version').textContent = data.backend?.app_version || 'N/A';
                document.getElementById('php-version').textContent = data.system?.php_version || 'N/A';
                document.getElementById('debug-mode').innerHTML = `<span class="${data.backend?.debug ? 'text-red-600' : 'text-green-600'}">${data.backend?.debug ? 'ON (⚠️)' : 'OFF'}</span>`;

                // Health Checks
                let healthHtml = '';
                if (data.backend?.checks) {
                    Object.entries(data.backend.checks).forEach(([key, check]) => {
                        healthHtml += `
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded">
                                <span class="text-gray-600 capitalize">${key}</span>
                                <span class="status-badge ${getStatusBadge(check.ok ? 'healthy' : 'error')}">${check.ok ? '✓ OK' : '✕ Failed'}</span>
                            </div>
                        `;
                    });
                }
                document.getElementById('health-checks').innerHTML = healthHtml || '<p class="text-gray-500">No health data</p>';

                // System
                const memoryPercent = Math.min(100, (data.system?.memory_usage / 1024) * 100);
                const diskPercent = Math.min(100, ((data.system?.disk_total - data.system?.disk_free) / data.system?.disk_total) * 100 || 0);
                document.getElementById('memory-usage').textContent = `${(data.system?.memory_usage || 0).toFixed(2)} MB`;
                document.getElementById('memory-bar').style.width = `${memoryPercent}%`;
                document.getElementById('disk-usage').textContent = `${diskPercent.toFixed(1)}% of ${(data.system?.disk_total || 0).toFixed(1)} GB`;
                document.getElementById('disk-bar').style.width = `${diskPercent}%`;

                // Frontend
                const frontendExists = data.frontend?.exists;
                document.getElementById('frontend-status').innerHTML =
                    `<span class="status-badge ${getStatusBadge(frontendExists ? 'healthy' : 'offline')}">${frontendExists ? '✓ Ready' : '✕ Missing'}</span>`;
                document.getElementById('frontend-exists').innerHTML = `<span class="${getCheckStatus(frontendExists)}">${getCheckmark(frontendExists)}</span>`;
                document.getElementById('frontend-build').innerHTML = `<span class="${getCheckStatus(data.frontend?.build_exists)}">${getCheckmark(data.frontend?.build_exists)}</span>`;
                document.getElementById('frontend-modules').innerHTML = `<span class="${getCheckStatus(data.frontend?.node_modules)}">${getCheckmark(data.frontend?.node_modules)}</span>`;
                document.getElementById('frontend-env').innerHTML = `<span class="${getCheckStatus(data.frontend?.env_file)}">${getCheckmark(data.frontend?.env_file)}</span>`;

                // Database
                const dbConnected = data.database?.connected;
                document.getElementById('db-status').innerHTML =
                    `<span class="status-badge ${getStatusBadge(dbConnected ? 'healthy' : 'error')}">${dbConnected ? '✓ Connected' : '✕ Error'}</span>`;
                document.getElementById('db-size').textContent = (data.database?.size_mb || 0) + ' MB';
                document.getElementById('db-tables').textContent = data.database?.tables?.length || 0;
                document.getElementById('db-records').textContent = (data.database?.total_records || 0).toLocaleString();

                // Queue
                document.getElementById('queue-status').innerHTML =
                    `<span class="status-badge ${getStatusBadge(data.queue?.status || 'unknown')}">${(data.queue?.pending_jobs || 0)}</span>`;
                document.getElementById('queue-pending').textContent = data.queue?.pending_jobs || 0;
                document.getElementById('queue-failed').textContent = data.queue?.failed_jobs || 0;

                // Logs
                let logsHtml = '';
                if (data.logs && data.logs.length > 0) {
                    data.logs.forEach(log => {
                        logsHtml += `
                            <div class="p-2 border-b border-gray-200">
                                <div class="text-gray-600">[${log.timestamp}] <span class="font-semibold text-blue-600">${log.level}</span></div>
                                <div class="text-gray-700 mt-1 break-words">${log.message.substring(0, 200)}</div>
                            </div>
                        `;
                    });
                }
                document.getElementById('recent-logs').innerHTML = logsHtml || '<p class="text-gray-500">No logs available</p>';

            } catch (error) {
                console.error('Error loading dashboard:', error);
                alert('Failed to load dashboard data');
            }
        }

        // Refresh dashboard
        function refreshDashboard() {
            document.querySelectorAll('.loading-skeleton').forEach(el => {
                el.style.display = 'inline-block';
            });
            loadDashboard();
        }

        // Clear cache
        async function clearCache() {
            if (!confirm('Are you sure you want to clear the cache?')) return;
            try {
                const response = await axios.post('/dashboard/clear-cache');
                alert(response.data.message || 'Cache cleared successfully');
                loadDashboard();
            } catch (error) {
                alert('Failed to clear cache: ' + (error.response?.data?.error || error.message));
            }
        }

        // Restart queue
        async function restartQueue() {
            if (!confirm('Are you sure you want to restart the queue?')) return;
            try {
                const response = await axios.post('/dashboard/restart-queue');
                alert(response.data.message || 'Queue restarted successfully');
                loadDashboard();
            } catch (error) {
                alert('Failed to restart queue: ' + (error.response?.data?.error || error.message));
            }
        }

        // Initial load
        loadDashboard();

        // Auto-refresh every 30 seconds
        setInterval(loadDashboard, 30000);
    </script>
</body>
</html>
