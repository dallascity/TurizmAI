<?php

use App\Models\Hotel;
use Illuminate\Support\Facades\Artisan;

// Docker konteyneri açılırken çalışır (docker/php/entrypoint.sh)
Artisan::command('app:setup', function () {
    $this->call('migrate', ['--force' => true]);

    if (Hotel::count() === 0) {
        $this->call('db:seed', ['--force' => true]);
    }
})->purpose('Migration çalıştırır, veritabanı boşsa örnek otelleri yükler');
