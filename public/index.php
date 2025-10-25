<?php

use App\Database;
use App\PluginUploader;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

session_start();

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$container->set('view', function() {
    return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
});

$container->set('db', function() {
    $db = Database::getInstance();
    $db->initializeSchema();
    return $db->getConnection();
});

$app->add(TwigMiddleware::createFromContainer($app, 'view'));

$app->get('/', function (Request $request, Response $response) use ($container) {
    $view = $container->get('view');
    return $view->render($response, 'index.twig');
});

$app->get('/admin', function (Request $request, Response $response) use ($container) {
    if (!isset($_SESSION['admin_logged_in'])) {
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
    
    $view = $container->get('view');
    $db = $container->get('db');
    
    $productCount = $db->query('SELECT COUNT(*) as count FROM products')->fetch()['count'];
    $orderCount = $db->query('SELECT COUNT(*) as count FROM orders')->fetch()['count'];
    $customerCount = $db->query('SELECT COUNT(*) as count FROM customers')->fetch()['count'];
    
    return $view->render($response, 'admin/dashboard.twig', [
        'productCount' => $productCount,
        'orderCount' => $orderCount,
        'customerCount' => $customerCount
    ]);
});

$app->get('/admin/login', function (Request $request, Response $response) use ($container) {
    $view = $container->get('view');
    return $view->render($response, 'admin/login.twig', [
        'csrf_token' => generateCsrfToken()
    ]);
});

$app->post('/admin/login', function (Request $request, Response $response) use ($container) {
    $data = $request->getParsedBody();
    $view = $container->get('view');
    
    if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
        return $view->render($response, 'admin/login.twig', [
            'error' => 'Invalid CSRF token',
            'csrf_token' => generateCsrfToken()
        ]);
    }
    
    $db = $container->get('db');
    
    $stmt = $db->prepare('SELECT * FROM admin_users WHERE username = ?');
    $stmt->execute([$data['username'] ?? '']);
    $user = $stmt->fetch();
    
    if ($user && password_verify($data['password'] ?? '', $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        
        $stmt = $db->prepare('UPDATE admin_users SET last_login = ? WHERE id = ?');
        $stmt->execute([date('Y-m-d H:i:s'), $user['id']]);
        
        return $response->withHeader('Location', '/admin')->withStatus(302);
    }
    
    return $view->render($response, 'admin/login.twig', [
        'error' => 'Invalid username or password',
        'csrf_token' => generateCsrfToken()
    ]);
});

$app->get('/admin/logout', function (Request $request, Response $response) {
    session_destroy();
    return $response->withHeader('Location', '/admin/login')->withStatus(302);
});

$app->get('/admin/products', function (Request $request, Response $response) use ($container) {
    if (!isset($_SESSION['admin_logged_in'])) {
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
    
    $view = $container->get('view');
    $db = $container->get('db');
    
    $products = $db->query('SELECT * FROM products ORDER BY created_at DESC')->fetchAll();
    
    return $view->render($response, 'admin/products.twig', [
        'products' => $products
    ]);
});

$app->get('/admin/orders', function (Request $request, Response $response) use ($container) {
    if (!isset($_SESSION['admin_logged_in'])) {
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
    
    $view = $container->get('view');
    $db = $container->get('db');
    
    $orders = $db->query('SELECT * FROM orders ORDER BY created_at DESC')->fetchAll();
    
    return $view->render($response, 'admin/orders.twig', [
        'orders' => $orders
    ]);
});

$app->get('/admin/customers', function (Request $request, Response $response) use ($container) {
    if (!isset($_SESSION['admin_logged_in'])) {
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
    
    $view = $container->get('view');
    $db = $container->get('db');
    
    $customers = $db->query('SELECT * FROM customers ORDER BY created_at DESC')->fetchAll();
    
    return $view->render($response, 'admin/customers.twig', [
        'customers' => $customers
    ]);
});

$app->get('/admin/plugins', function (Request $request, Response $response) use ($container) {
    if (!isset($_SESSION['admin_logged_in'])) {
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
    
    $view = $container->get('view');
    return $view->render($response, 'admin/plugins.twig', [
        'plugins' => $_SESSION['installed_plugins'] ?? []
    ]);
});

$app->get('/admin/plugins/upload', function (Request $request, Response $response) use ($container) {
    if (!isset($_SESSION['admin_logged_in'])) {
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
    
    $view = $container->get('view');
    return $view->render($response, 'admin/plugin-upload.twig', [
        'csrf_token' => generateCsrfToken()
    ]);
});

$app->post('/admin/plugins/upload', function (Request $request, Response $response) use ($container) {
    if (!isset($_SESSION['admin_logged_in'])) {
        return $response->withHeader('Location', '/admin/login')->withStatus(302);
    }
    
    $data = $request->getParsedBody();
    $view = $container->get('view');
    
    if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
        return $view->render($response, 'admin/plugin-upload.twig', [
            'error' => 'Invalid CSRF token',
            'csrf_token' => generateCsrfToken()
        ]);
    }
    
    try {
        $uploader = new PluginUploader();
        $files = $request->getUploadedFiles();
        
        if (!isset($files['plugin_zip'])) {
            throw new \Exception('No file uploaded');
        }
        
        $uploadedFile = $files['plugin_zip'];
        $tempPath = '/tmp/' . $uploadedFile->getClientFilename();
        $uploadedFile->moveTo($tempPath);
        
        $extracted = $uploader->extract($tempPath);
        $pluginInfo = $uploader->parsePluginInfo($extracted['path']);
        $menus = $uploader->parseAdminMenus($pluginInfo['main_file']);
        $schemas = $uploader->extractDatabaseSchema($extracted['path']);
        
        if (!isset($_SESSION['installed_plugins'])) {
            $_SESSION['installed_plugins'] = [];
        }
        
        $_SESSION['installed_plugins'][$extracted['slug']] = [
            'info' => $pluginInfo,
            'menus' => $menus,
            'schemas' => $schemas,
            'path' => $extracted['path']
        ];
        
        $db = $container->get('db');
        foreach ($schemas as $schema) {
            try {
                $schema = str_replace('{$wpdb->prefix}', '', $schema);
                $schema = str_replace('wp_', '', $schema);
                $db->exec($schema);
            } catch (\Exception $e) {
                error_log('Schema error: ' . $e->getMessage());
            }
        }
        
        unlink($tempPath);
        
        return $response->withHeader('Location', '/admin/plugins')->withStatus(302);
        
    } catch (\Exception $e) {
        return $view->render($response, 'admin/plugin-upload.twig', [
            'error' => $e->getMessage(),
            'csrf_token' => generateCsrfToken()
        ]);
    }
});

$app->run();
