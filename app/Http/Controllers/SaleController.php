protected static array $middlewares = [
    'auth',
    'permission:view-sales' => ['only' => ['index', 'show']],
    'permission:create-sales' => ['only' => ['create', 'store']],
    'permission:edit-sales' => ['only' => ['edit', 'update']],
    'permission:delete-sales' => ['only' => ['destroy']],
    'permission:manage-sale-payments' => ['only' => ['payments']],
    'permission:pos' => ['only' => ['pos', 'posStore']],
]; 