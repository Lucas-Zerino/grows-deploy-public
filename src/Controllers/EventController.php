<?php

namespace App\Controllers;

use App\Models\Event;
use App\Models\Instance;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Router;

class EventController
{
    public static function list(): void
    {
        $company = AuthMiddleware::authenticate();
        if (!$company) return;
        
        $params = Router::getQueryParams();
        $limit = min((int) ($params['limit'] ?? 50), 100);
        $offset = (int) ($params['offset'] ?? 0);
        $instanceId = $params['instance_id'] ?? null;
        
        if ($instanceId) {
            // Verificar se instância pertence à empresa
            $instance = Instance::findByIdAndCompany((int) $instanceId, $company['id']);
            if (!$instance) {
                Response::notFound('Instance not found');
                return;
            }
            
            $events = Event::findByInstance((int) $instanceId, $limit, $offset);
        } else {
            $events = Event::findByCompany($company['id'], $limit, $offset);
        }
        
        Response::success($events);
    }
    
    public static function get(string $id): void
    {
        $company = AuthMiddleware::authenticate();
        if (!$company) return;
        
        $event = Event::findById((int) $id);
        
        if (!$event || $event['company_id'] != $company['id']) {
            Response::notFound('Event not found');
            return;
        }
        
        Response::success($event);
    }
}

