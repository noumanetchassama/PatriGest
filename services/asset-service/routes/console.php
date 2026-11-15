<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Str::of('PatriGest — Gestion du patrimoine public')->title());
})->purpose('Display an inspiring quote');
