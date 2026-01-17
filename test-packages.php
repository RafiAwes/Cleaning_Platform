<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test the basic query
    $packages = \App\Models\Package::with(['services', 'addons', 'vendor'])
        ->where(function($q) {
            $q->where('packages.title', 'like', '%Premium%');
        })
        ->paginate(12);

    echo "SUCCESS - Query executed\n";
    echo json_encode($packages->toArray(), JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
