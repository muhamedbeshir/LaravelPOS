protected static array $middlewares = [
    'auth',
    'permission:access-dashboard' => ['only' => ['index']],
]; 