<?php

namespace Database\Seeders;

use App\Models\PlatformModule;
use Illuminate\Database\Seeder;

class ModuleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('modules.catalog', []) as $module) {
            $payload = collect($module)->except(['family'])->all();
            PlatformModule::query()->updateOrCreate(['key' => $payload['key']], $payload + ['is_visible' => true]);
        }
    }
}
