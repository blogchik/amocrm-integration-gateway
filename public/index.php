<?php

/**
 * AmoCRM Integration Gateway
 * Main Entry Point
 * 
 * Minimal router
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Production uchun 0
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/error.log');

// Session start (OAuth uchun)
session_start();

// Composer autoloader
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Custom autoloader (fallback)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// CORS headers (agar kerak bo'lsa)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

// OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

use App\Middleware\ApiKeyMiddleware;
use App\Controllers\LeadControllerV2;
use App\Controllers\InfoControllerV2;
use App\Controllers\DiagnosticsController;
use App\Controllers\OAuthController;
use App\Helpers\Response;

// Routing
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Trailing slash olib tashlash
$uri = rtrim($uri, '/');

// Routes
try {
    // Health check endpoint (API key kerak emas)
    if ($method === 'GET' && $uri === '/health') {
        Response::success(['status' => 'ok'], 'Gateway is running');
        exit;
    }

    // OAuth endpoints (API key kerak emas)
    if ($method === 'GET' && $uri === '/oauth/authorize') {
        $controller = new OAuthController();
        $controller->authorize();
        exit;
    }

    if ($method === 'GET' && $uri === '/oauth/callback') {
        $controller = new OAuthController();
        $controller->callback();
        exit;
    }

    if ($method === 'GET' && $uri === '/oauth/status') {
        $controller = new OAuthController();
        $controller->status();
        exit;
    }

    // API Key middleware (faqat API endpointlari uchun)
    if (!ApiKeyMiddleware::check()) {
        exit; // Middleware o'zi response yuboradi
    }

    // POST /api/v1/leads/unsorted
    if ($method === 'POST' && $uri === '/api/v1/leads/unsorted') {
        $controller = new LeadControllerV2();
        $controller->createUnsorted();
        exit;
    }

    // GET /api/v1/info/pipelines - Barcha pipelinelar
    if ($method === 'GET' && $uri === '/api/v1/info/pipelines') {
        $controller = new InfoControllerV2();
        $controller->getPipelines();
        exit;
    }

    // GET /api/v1/info/pipelines/{id} - Bitta pipeline
    if ($method === 'GET' && preg_match('#^/api/v1/info/pipelines/(\d+)$#', $uri, $matches)) {
        $controller = new InfoControllerV2();
        $controller->getPipelineById((int)$matches[1]);
        exit;
    }

    // GET /api/v1/info/lead-fields - Lead custom fields
    if ($method === 'GET' && $uri === '/api/v1/info/lead-fields') {
        $controller = new InfoControllerV2();
        $controller->getLeadFields();
        exit;
    }

    // GET /api/v1/info/contact-fields - Contact custom fields
    if ($method === 'GET' && $uri === '/api/v1/info/contact-fields') {
        $controller = new InfoControllerV2();
        $controller->getContactFields();
        exit;
    }

    // GET /api/v1/info/account - Account info
    if ($method === 'GET' && $uri === '/api/v1/info/account') {
        $controller = new InfoControllerV2();
        $controller->getAccount();
        exit;
    }

    // GET /api/v1/diagnostics/token-status - Token diagnostics
    if ($method === 'GET' && $uri === '/api/v1/diagnostics/token-status') {
        $controller = new DiagnosticsController();
        $controller->getTokenStatus();
        exit;
    }

    // GET /api/v1/diagnostics/config - Config diagnostics
    if ($method === 'GET' && $uri === '/api/v1/diagnostics/config') {
        $controller = new DiagnosticsController();
        $controller->getConfig();
        exit;
    }

    // 404 - Route topilmadi
    Response::notFound('Endpoint not found: ' . $uri);
    
} catch (\Throwable $e) {
    // Global error handler
    error_log('Unhandled exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    Response::serverError('An unexpected error occurred');
}
