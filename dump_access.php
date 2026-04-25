<?php
use App\Services\AccessDatabaseService;
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = app(AccessDatabaseService::class);
$records = $svc->fetchRecords(null);
$p10 = null;
foreach($records as $r) {
    if(($r['machine_no'] ?? $r['Machine No'] ?? '') === 'P10') {
        $p10 = $r;
        break;
    }
}
$output = [
    'first_row_keys' => !empty($records) ? array_keys($records[0]) : [],
    'p10_data' => $p10
];
file_put_contents('_debug_records_access.json', json_encode($output, JSON_PRETTY_PRINT));
echo "DONE\n";
