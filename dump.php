<?php
use App\Services\AccessDatabaseService;
$svc = app(AccessDatabaseService::class);
$records = $svc->fetchRecords('2026-04-23');
if (!empty($records)) {
    file_put_contents('_debug_cols.json', json_encode(array_keys($records[0]), JSON_PRETTY_PRINT));
} else {
    file_put_contents('_debug_cols.json', '[]');
}
