<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Bel-Sistem API Rehberi</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                API temel adresi: <code>{{ url('/api/v1') }}</code>
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                OpenAPI dosyası:
                <a class="text-primary-600 hover:underline dark:text-primary-400" href="{{ url('/docs/openapi.yaml') }}" target="_blank" rel="noreferrer">
                    {{ url('/docs/openapi.yaml') }}
                </a>
            </p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Kimlik Doğrulama</h3>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">POST /api/v1/auth/token
{
  "email": "admin@kirklareli.bel.tr",
  "password": "password",
  "device_name": "postman"
}</pre>
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                Dönen <code>access_token</code> değerini tüm isteklerde <code>Authorization: Bearer ERISIM_BELIRTECI</code> ile gönderin.
            </p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Temel Endpointler</h3>
            <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <li><code>GET /api/v1/me</code></li>
                <li><code>GET /api/v1/tasks</code>, <code>GET /api/v1/tasks/{id}</code>, <code>POST /api/v1/tasks</code>, <code>PATCH /api/v1/tasks/{id}</code></li>
                <li><code>GET /api/v1/departments</code>, <code>GET /api/v1/departments/{id}</code></li>
                <li><code>GET /api/v1/users</code>, <code>GET /api/v1/users/{id}</code></li>
                <li><code>GET /api/v1/reports/dashboard</code> — müdür / başkan yardımcısı özeti</li>
                <li><code>POST /api/v1/uploads/task-photo</code> — görev fotoğrafı (multipart)</li>
                <li><code>GET /api/v1/approval-requests</code>, <code>POST /api/v1/approval-requests/{id}/approve</code>, <code>POST /api/v1/approval-requests/{id}/reject</code></li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
