<?php

/*
 | Titik masuk untuk Vercel (runtime vercel-php).
 | Di Vercel hanya /tmp yang bisa ditulis, jadi storage & cache Laravel dialihkan ke sana.
 */

$storage = '/tmp/storage';

foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $dir) {
    if (! is_dir("$storage/$dir")) {
        mkdir("$storage/$dir", 0755, true);
    }
}

$_ENV['LARAVEL_STORAGE_PATH'] = $_SERVER['LARAVEL_STORAGE_PATH'] = $storage;

require __DIR__.'/../public/index.php';
