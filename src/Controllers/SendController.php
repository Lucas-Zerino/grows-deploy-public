<?php

namespace App\Controllers;

use App\Utils\Response;
use App\Utils\Logger;
use App\Utils\Validator;
use App\Models\Instance;
use App\Providers\ProviderManager;

class SendController
{
    /**
     * Enviar mensagem de texto
     */
    public function sendText()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            if (!isset($data['to']) || !is_string($data['to']) || empty(trim($data['to']))) {
                return Response::error('To is required and must be a non-empty string', 400);
            }
            if (!isset($data['message']) || !is_string($data['message']) || empty(trim($data['message']))) {
                return Response::error('Message is required and must be a non-empty string', 400);
            }
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e enviar mensagem
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->sendTextMessage($instance['external_id'], $data['to'], $data['message']);
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Send text failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Enviar mensagem de mídia
     */
    public function sendMedia()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            if (!isset($data['to']) || !is_string($data['to']) || empty(trim($data['to']))) {
                return Response::error('To is required and must be a non-empty string', 400);
            }
            if (!isset($data['media']) || !is_string($data['media']) || empty(trim($data['media']))) {
                return Response::error('Media is required and must be a non-empty string', 400);
            }
            if (!isset($data['type']) || !is_string($data['type']) || empty(trim($data['type']))) {
                return Response::error('Type is required and must be a non-empty string', 400);
            }
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e enviar mídia
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->sendMediaMessage(
                $instance['external_id'], 
                $data['to'], 
                $data['type'], 
                $data['media'], 
                $data['caption'] ?? ''
            );
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Send media failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Enviar contato
     */
    public function sendContact()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            if (!isset($data['to']) || !is_string($data['to']) || empty(trim($data['to']))) {
                return Response::error('To is required and must be a non-empty string', 400);
            }
            if (!isset($data['contact']) || !is_array($data['contact'])) {
                return Response::error('Contact is required and must be an array', 400);
            }
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e enviar contato
            $provider = ProviderManager::getProvider($instance['provider_id']);
            
            // Contact sending not implemented in provider interface
            return Response::error('Envio de contatos não implementado', 501);
            
        } catch (\Exception $e) {
            Logger::error('Send contact failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
    
    /**
     * Enviar localização
     */
    public function sendLocation()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar dados básicos
            if (!isset($data['to']) || !is_string($data['to']) || empty(trim($data['to']))) {
                return Response::error('To is required and must be a non-empty string', 400);
            }
            if (!isset($data['latitude']) || !is_numeric($data['latitude'])) {
                return Response::error('Latitude is required and must be numeric', 400);
            }
            if (!isset($data['longitude']) || !is_numeric($data['longitude'])) {
                return Response::error('Longitude is required and must be numeric', 400);
            }
            
            // Buscar instância pelo token
            $instance = Instance::getByToken($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Detectar provider e enviar localização
            $provider = ProviderManager::getProvider($instance['provider_id']);
            
            // Location sending not implemented in provider interface
            return Response::error('Envio de localização não implementado', 501);
            
        } catch (\Exception $e) {
            Logger::error('Send location failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
}