<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$hours = App\Models\LibrarySetting::getValue('operating_hours', []);
$hours['monday']['close'] = '18:00';
$hours['tuesday']['close'] = '18:00';
$hours['wednesday']['close'] = '18:00';
$hours['thursday']['close'] = '18:00';
$hours['friday']['close'] = '18:00';

// Update the database directly
App\Models\LibrarySetting::where('key', 'operating_hours')->update([
    'value' => json_encode($hours),
]);

echo "Updated weekday closing times to 18:00\n";
echo json_encode($hours, JSON_PRETTY_PRINT) . "\n";
