<?php
$services = ['auth_service', 'farming_service', 'gis_service', 'master_service', 'reporting_service', 'user_service'];
foreach ($services as $service) {
    echo strtoupper($service) . "\n";
    $path = __DIR__ . '/services/' . $service . '/database/migrations/';
    if (is_dir($path)) {
        $files = scandir($path);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($path . $file);
                preg_match('/Schema::create\(\'([^\']+)\'/', $content, $tableMatch);
                if ($tableMatch) {
                    echo 'Table: ' . $tableMatch[1] . "\n";
                    preg_match_all('/\$table->([a-zA-Z_]+)\(\'([^\']+)\'/', $content, $colMatches);
                    if ($colMatches) {
                        for ($i = 0; $i < count($colMatches[1]); $i++) {
                            echo '  - ' . $colMatches[2][$i] . ' (' . $colMatches[1][$i] . ')' . "\n";
                        }
                    }
                    if (strpos($content, '$table->id()') !== false) {
                        echo "  - id (id)\n";
                    }
                    if (strpos($content, '$table->timestamps()') !== false) {
                        echo "  - created_at, updated_at (timestamps)\n";
                    }
                }
            }
        }
    }
}
