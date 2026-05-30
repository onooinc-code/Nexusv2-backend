<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$token = Laravel\Sanctum\PersonalAccessToken::findToken('14|sX4zlxYNM3FqCiAfNdOa2plhrtNrAydIdj0rPzNG171fb001');
if (!$token) {
    echo 'NO_TOKEN';
    exit(1);
}
$user = $token->tokenable;
echo json_encode([
    'user_id' => $user->getAuthIdentifier(),
    'name' => isset($user->name) ? $user->name : null,
    'email' => isset($user->email) ? $user->email : null,
]);
