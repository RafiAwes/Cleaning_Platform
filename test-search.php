<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test with the search parameter
    $search = "Premium";
    $query = \App\Models\Package::with(['services', 'addons', 'vendor'])
        ->latest('id');

    if (!empty($search)) {
        $query->where(function($q) use ($search) {
            $q->where('packages.title', 'like', "%{$search}%")
              ->orWhere('packages.description', 'like', "%{$search}%")
              ->orWhereHas('services', function($subQ) use ($search) {
                  $subQ->where('title', 'like', "%{$search}%");
              })
              ->orWhereHas('vendor', function($subQ) use ($search) {
                  $subQ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
              });
        });
    }

    $packages = $query->paginate(12);

    echo "SUCCESS - Query executed\n";
    echo json_encode($packages->toArray(), JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
