<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Router;
use App\Utils\Response;
use App\Utils\Logger;
use App\Controllers\InstanceController;
use App\Controllers\MessageController;
use App\Controllers\EventController;
use App\Controllers\WebhookController;
use App\Controllers\AdminController;
use App\Controllers\SendController;
use App\Controllers\MessageActionController;
use App\Controllers\MenuController;
use App\Controllers\ContactsController;
use App\Controllers\GroupsController;
use App\Controllers\CommunitiesController;

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    try {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    } catch (Throwable $e) {
        // Dotenv not available, continue without it
    }
}

// Global exception handler
set_exception_handler(function ($e) {
    Logger::critical('Uncaught exception', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
        Response::serverError($e->getMessage());
    } else {
        Response::serverError('Internal server error');
    }
});

// Error handler
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    Logger::error('PHP Error', [
        'errno' => $errno,
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline,
    ]);
    
    return false; // Let PHP handle it
});

// Load RabbitMQ config globally
$GLOBALS['rabbitmq_config'] = require __DIR__ . '/../config/rabbitmq.php';

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize router
$router = new Router();

// ===== PUBLIC ROUTES =====

// Health check (sem autenticação)
$router->get('/health', function () {
    Response::success([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
});

// ===== AUTH ROUTES =====

use App\Controllers\AuthController;

// Login do superadmin
$router->post('/api/admin/login', [AuthController::class, 'login']);

// Obter dados do admin logado
$router->get('/api/admin/me', [AuthController::class, 'me']);

// Trocar senha
$router->post('/api/admin/change-password', [AuthController::class, 'changePassword']);

// ===== WEBHOOK ROUTES =====

$router->post('/webhook/waha/{id}', function ($id) {
    WebhookController::wahaWebhook($id);
});

$router->post('/webhook/uazapi/{id}', function ($id) {
    WebhookController::uazapiWebhook($id);
});

// Instagram Webhooks
$router->post('/webhook/instagram/{companyId}', function($companyId) {
    \App\Controllers\InstagramWebhookController::handleWebhook($companyId);
});
$router->get('/webhook/instagram/{companyId}', function($companyId) {
    \App\Controllers\InstagramWebhookController::verifyWebhook($companyId);
});

// Facebook Webhooks
$router->post('/webhook/facebook/{companyId}', function($companyId) {
    \App\Controllers\FacebookWebhookController::handleWebhook($companyId);
});
$router->get('/webhook/facebook/{companyId}', function($companyId) {
    \App\Controllers\FacebookWebhookController::verifyWebhook($companyId);
});

// ===== INSTAGRAM ROUTES =====

// Instagram App Management
$router->post('/api/instagram/app', [\App\Controllers\InstagramAppController::class, 'create']);
$router->get('/api/instagram/app', [\App\Controllers\InstagramAppController::class, 'get']);
$router->put('/api/instagram/app', [\App\Controllers\InstagramAppController::class, 'update']);
$router->delete('/api/instagram/app', [\App\Controllers\InstagramAppController::class, 'delete']);
$router->get('/api/instagram/auth-url', [\App\Controllers\InstagramAppController::class, 'getAuthUrl']);

// Instagram OAuth Callback
$router->get('/api/instagram/callback', [\App\Controllers\InstagramAuthController::class, 'callback']);
$router->get('/api/instagram/auth-status', [\App\Controllers\InstagramAuthController::class, 'getStatus']);

// ===== FACEBOOK ROUTES =====

// Facebook App Management
$router->post('/api/facebook/app', [\App\Controllers\FacebookAppController::class, 'create']);
$router->get('/api/facebook/app', [\App\Controllers\FacebookAppController::class, 'get']);
$router->put('/api/facebook/app', [\App\Controllers\FacebookAppController::class, 'update']);
$router->delete('/api/facebook/app', [\App\Controllers\FacebookAppController::class, 'delete']);

// ===== COMPANY ROUTES (Autenticação requerida) =====

// ===== INSTANCE ROUTES (Unificadas) =====
// Rotas de instância unificadas (detecta provider automaticamente)

// Debug: verificar se a classe existe
if (!class_exists('App\\Controllers\\InstanceController')) {
    die('InstanceController class not found');
}

// ===== INSTANCE WEBHOOK ROUTES =====
// Rotas para gerenciar webhooks de instâncias

$router->get('/api/instances/{id}/webhooks', function($id) {
    \App\Controllers\InstanceWebhookController::list($id);
});

$router->post('/api/instances/{id}/webhooks', function($id) {
    \App\Controllers\InstanceWebhookController::create($id);
});

$router->put('/api/instances/{instanceId}/webhooks/{webhookId}', function($instanceId, $webhookId) {
    \App\Controllers\InstanceWebhookController::update($instanceId, $webhookId);
});

$router->delete('/api/instances/{instanceId}/webhooks/{webhookId}', function($instanceId, $webhookId) {
    \App\Controllers\InstanceWebhookController::delete($instanceId, $webhookId);
});

$router->post('/instance/connect', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->connect();
});
$router->post('/instance/disconnect', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->disconnect();
});
$router->get('/instance/status', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->status();
});
$router->get('/instance/qrcode', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->getQRCode();
});
$router->get('/instance/qrcode/image', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->getQRCodeImage();
});
$router->put('/instance/name', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->updateName();
});
$router->delete('/instance', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->delete();
});
$router->get('/instance/privacy', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->getPrivacySettings();
});
$router->put('/instance/privacy', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->updatePrivacySettings();
});
$router->put('/instance/presence', function() {
    $controller = new \App\Controllers\InstanceController();
    $controller->updatePresence();
});

// ===== SEND MESSAGE ROUTES (Unificadas) =====

// Envio de mensagens unificadas (detecta provider automaticamente)
$router->post('/send/text', [\App\Controllers\SendController::class, 'sendText']);
$router->post('/send/media', [\App\Controllers\SendController::class, 'sendMedia']);
$router->post('/send/contact', [\App\Controllers\SendController::class, 'sendContact']);
$router->post('/send/location', [\App\Controllers\SendController::class, 'sendLocation']);

// Menus interativos
$router->post('/send/menu', [MenuController::class, 'sendMenu']);
$router->post('/send/carousel', [MenuController::class, 'sendCarousel']);
$router->post('/send/location-button', [MenuController::class, 'requestLocation']);
$router->post('/send/status', [MenuController::class, 'sendStatus']);

// Ações em mensagens
$router->post('/message/presence', [MessageActionController::class, 'updatePresence']);
$router->post('/message/react', [MessageActionController::class, 'sendReaction']);
$router->post('/message/markread', [MessageActionController::class, 'markAsRead']);
$router->post('/message/download', [MessageActionController::class, 'downloadFile']);
$router->post('/message/forward', [MessageActionController::class, 'forwardMessage']);

// Instances - API antiga (manter por compatibilidade)
$router->get('/api/instances', [InstanceController::class, 'list']);
$router->post('/api/instances', [InstanceController::class, 'create']);
$router->get('/api/instances/{id}', function ($id) {
    InstanceController::get($id);
});
$router->delete('/api/instances/{id}', function ($id) {
    InstanceController::deleteById($id);
});
$router->get('/api/instances/{id}/qrcode', function ($id) {
    $controller = new InstanceController();
    $controller->getQRCode($id);
});

// Messages
$router->get('/api/messages', [MessageController::class, 'list']);
$router->post('/api/messages/send', [MessageController::class, 'send']);
$router->get('/api/messages/{id}', function ($id) {
    MessageController::get($id);
});

// Events
$router->get('/api/events', [EventController::class, 'list']);
$router->get('/api/events/{id}', function ($id) {
    EventController::get($id);
});

// ===== ADMIN ROUTES (Superadmin only) =====

use App\Controllers\CompanyController;

// Companies
$router->get('/api/admin/companies', [CompanyController::class, 'index']);
$router->post('/api/admin/companies', [CompanyController::class, 'create']);
$router->get('/api/admin/companies/{id}', function ($id) {
    CompanyController::show(['id' => $id]);
});
$router->put('/api/admin/companies/{id}', function ($id) {
    CompanyController::update(['id' => $id]);
});
$router->delete('/api/admin/companies/{id}', function ($id) {
    CompanyController::delete(['id' => $id]);
});

// Providers
$router->get('/api/admin/providers', [AdminController::class, 'listProviders']);
$router->post('/api/admin/providers', [AdminController::class, 'createProvider']);
$router->get('/api/admin/providers/{id}', function ($id) {
    AdminController::getProvider($id);
});
$router->put('/api/admin/providers/{id}', function ($id) {
    AdminController::updateProvider($id);
});
$router->delete('/api/admin/providers/{id}', function ($id) {
    AdminController::deleteProvider($id);
});

// Health & Monitoring
$router->get('/api/admin/health', [AdminController::class, 'health']);

// Instance Management (Superadmin)
$router->get('/api/admin/instances', [AdminController::class, 'listAllInstances']);
$router->get('/api/admin/providers/{id}/instances', function ($id) {
    AdminController::listProviderInstances($id);
});
$router->post('/api/admin/instances/{providerId}/{externalInstanceId}/disconnect', function ($providerId, $externalInstanceId) {
    AdminController::disconnectInstance($providerId, $externalInstanceId);
});
$router->delete('/api/admin/instances/{providerId}/{externalInstanceId}', function ($providerId, $externalInstanceId) {
    AdminController::deleteInstance($providerId, $externalInstanceId);
});

// Contacts Management (UAZAPI Format)
$router->get('/contacts', [ContactsController::class, 'getAllContacts']);
$router->post('/contact/add', [ContactsController::class, 'addContact']);
$router->post('/contact/remove', [ContactsController::class, 'removeContact']);
$router->post('/chat/details', [ContactsController::class, 'getChatDetails']);
$router->post('/chat/check', [ContactsController::class, 'checkNumbers']);
$router->post('/contact/block', [ContactsController::class, 'blockContact']);
$router->post('/contact/unblock', [ContactsController::class, 'unblockContact']);

// Groups Management (UAZAPI Format)
$router->post('/group/create', [GroupsController::class, 'createGroup']);
$router->post('/group/info', [GroupsController::class, 'getGroupInfo']);
$router->post('/group/inviteInfo', [GroupsController::class, 'getInviteInfo']);
$router->post('/group/join', [GroupsController::class, 'joinGroup']);
$router->get('/group/invitelink/{groupJID}', [GroupsController::class, 'getInviteLink']);
$router->post('/group/updateDescription', [GroupsController::class, 'updateDescription']);
$router->post('/group/updateName', [GroupsController::class, 'updateName']);
$router->post('/group/updateImage', [GroupsController::class, 'updateImage']);
$router->post('/group/updateParticipants', [GroupsController::class, 'updateParticipants']);
$router->post('/group/leave', [GroupsController::class, 'leaveGroup']);

// Communities Management (UAZAPI Format)
$router->post('/community/create', [CommunitiesController::class, 'createCommunity']);
$router->post('/community/editgroups', [CommunitiesController::class, 'editGroups']);
$router->post('/community/info', [CommunitiesController::class, 'getCommunityInfo']);
$router->post('/community/leave', [CommunitiesController::class, 'leaveCommunity']);
$router->post('/community/updateDescription', [CommunitiesController::class, 'updateDescription']);
$router->post('/community/updateName', [CommunitiesController::class, 'updateName']);
$router->get('/community/list', [CommunitiesController::class, 'listCommunities']);

// Dispatch router
$router->dispatch();

