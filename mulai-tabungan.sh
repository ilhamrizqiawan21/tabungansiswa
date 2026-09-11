#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")"
if [[ ! -f public/build/manifest.json ]]; then
    echo 'Aset aplikasi belum dibangun. Jalankan npm run build.' >&2
    exit 1
fi
for attempt in {1..30}; do
    if /usr/bin/php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); try { Illuminate\Support\Facades\DB::connection()->getPdo(); } catch (Throwable $e) { exit(1); }' 2>/dev/null; then
        exec /usr/bin/php artisan serve --host=127.0.0.1 --port=8000 --tries=0 --no-reload --no-interaction
    fi
    sleep 2
done
echo 'Database belum tersedia setelah 60 detik.' >&2
exit 1
