<?php require " vendor/autoload.php\; $app = require_once \bootstrap/app.php\; $app->make(\Illuminate\Contracts\Console\Kernel\)->bootstrap(); $request = Illuminate\Http\Request::create(\/api/users\, \GET\); $response = app()->handle($request); echo \Status: \ . $response->getStatusCode() . \
\; echo \Body: \ . $response->getContent() . \
\;
