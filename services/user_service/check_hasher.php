<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$broker = Password::broker();
$repository = $broker->getRepository();

echo "Repository class: " . get_class($repository) . "\n";

$reflector = new ReflectionClass($repository);
if ($reflector->hasProperty('hasher')) {
    $property = $reflector->getProperty('hasher');
    $property->setAccessible(true);
    $hasher = $property->getValue($repository);
    echo "Hasher class: " . get_class($hasher) . "\n";
} else {
    echo "No hasher property found.\n";
}

if ($reflector->hasProperty('hashKey')) {
    $property = $reflector->getProperty('hashKey');
    $property->setAccessible(true);
    echo "HashKey: " . $property->getValue($repository) . "\n";
}
