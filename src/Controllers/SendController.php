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
            if (!isset($data['to']) && !isset($data['number'])) {
                return Response::error('To/number is required', 400);
            }
            $to = $data['to'] ?? $data['number'];
            
            // Aceita tanto 'message' quanto 'text' (compatibilidade UAZAPI)
            $message = $data['message'] ?? $data['text'] ?? '';
            if (empty(trim($message))) {
                return Response::error('Message/text is required and must be a non-empty string', 400);
            }
            
            // Buscar instância pelo token (remover "Bearer " se presente)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Validar e normalizar número brasileiro (com/sem dígito 9)
            $validation = \App\Utils\PhoneValidator::validateBrazilianPhone($instance['id'], $to);
            if (!$validation['is_valid']) {
                $errorMessage = $validation['error'] ?? 'Número de telefone inválido no WhatsApp';
                return Response::error($errorMessage, 400);
            }
            
            // Usar número validado
            $validatedTo = $validation['validated_number'];
            
            // Detectar provider e enviar mensagem
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->sendTextMessage($instance['external_instance_id'], $validatedTo, $message);
            
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
            if (!isset($data['to']) && !isset($data['number'])) {
                return Response::error('To/number is required', 400);
            }
            $to = $data['to'] ?? $data['number'];
            
            // Aceita tanto 'media' quanto 'file' (compatibilidade UAZAPI)
            $media = $data['media'] ?? $data['file'] ?? null;
            if (!$media || !is_string($media) || empty(trim($media))) {
                return Response::error('Media/file is required and must be a non-empty string', 400);
            }
            
            // Tipo da mídia (image, video, audio, document)
            $type = $data['type'] ?? 'image';
            
            // Buscar instância pelo token (remover "Bearer " se presente)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Validar e normalizar número brasileiro (com/sem dígito 9)
            $validation = \App\Utils\PhoneValidator::validateBrazilianPhone($instance['id'], $to);
            if (!$validation['is_valid']) {
                $errorMessage = $validation['error'] ?? 'Número de telefone inválido no WhatsApp';
                return Response::error($errorMessage, 400);
            }
            
            // Usar número validado
            $validatedTo = $validation['validated_number'];
            
            // Detectar provider e enviar mídia
            $provider = ProviderManager::getProvider($instance['provider_id']);
            $result = $provider->sendMediaMessage(
                $instance['external_instance_id'], 
                $validatedTo, 
                $type, 
                $media, 
                $data['caption'] ?? $data['text'] ?? ''
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
            if (!isset($data['to']) && !isset($data['number'])) {
                return Response::error('To/number is required', 400);
            }
            $to = $data['to'] ?? $data['number'];
            
            // Aceita tanto 'contact' quanto os campos separados (compatibilidade UAZAPI)
            $contact = $data['contact'] ?? null;
            if (!$contact && isset($data['fullName'])) {
                $contact = [
                    'name' => $data['fullName'] ?? '',
                    'fullName' => $data['fullName'] ?? '',
                    'phone' => $data['phoneNumber'] ?? $data['phone'] ?? '',
                    'phoneNumber' => $data['phoneNumber'] ?? $data['phone'] ?? '',
                    'organization' => $data['organization'] ?? '',
                    'email' => $data['email'] ?? '',
                    'url' => $data['url'] ?? ''
                ];
            }
            
            if (!$contact || !is_array($contact)) {
                return Response::error('Contact is required and must be an array', 400);
            }
            
            // Buscar instância pelo token (remover "Bearer " se presente)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Validar e normalizar número brasileiro (com/sem dígito 9)
            $validation = \App\Utils\PhoneValidator::validateBrazilianPhone($instance['id'], $to);
            if (!$validation['is_valid']) {
                $errorMessage = $validation['error'] ?? 'Número de telefone inválido no WhatsApp';
                return Response::error($errorMessage, 400);
            }
            
            // Usar número validado
            $validatedTo = $validation['validated_number'];
            
            // Detectar provider e enviar contato
            $provider = ProviderManager::getProvider($instance['provider_id']);
            
            // Verificar se o provider suporta envio de contato (WAHA tem, UAZAPI pode não ter)
            if (!method_exists($provider, 'sendContact')) {
                return Response::error('Provider não suporta envio de contatos', 501);
            }
            
            $result = call_user_func([$provider, 'sendContact'], $instance['external_instance_id'], $validatedTo, $contact);
            
            return Response::success($result);
            
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
            if (!isset($data['to']) && !isset($data['number'])) {
                return Response::error('To/number is required', 400);
            }
            $to = $data['to'] ?? $data['number'];
            
            if (!isset($data['latitude']) || !is_numeric($data['latitude'])) {
                return Response::error('Latitude is required and must be numeric', 400);
            }
            if (!isset($data['longitude']) || !is_numeric($data['longitude'])) {
                return Response::error('Longitude is required and must be numeric', 400);
            }
            
            // Buscar instância pelo token (remover "Bearer " se presente)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $token = str_replace('Bearer ', '', $authHeader);
            $instance = Instance::getByToken($token);
            if (!$instance) {
                return Response::notFound('Instância não encontrada');
            }
            
            // Validar e normalizar número brasileiro (com/sem dígito 9)
            $validation = \App\Utils\PhoneValidator::validateBrazilianPhone($instance['id'], $to);
            if (!$validation['is_valid']) {
                $errorMessage = $validation['error'] ?? 'Número de telefone inválido no WhatsApp';
                return Response::error($errorMessage, 400);
            }
            
            // Usar número validado
            $validatedTo = $validation['validated_number'];
            
            // Detectar provider e enviar localização
            $provider = ProviderManager::getProvider($instance['provider_id']);
            
            // Verificar se o provider suporta envio de localização
            if (!method_exists($provider, 'sendLocation')) {
                return Response::error('Provider não suporta envio de localização', 501);
            }
            
            $latitude = floatval($data['latitude']);
            $longitude = floatval($data['longitude']);
            $name = $data['name'] ?? null;
            $address = $data['address'] ?? null;
            
            $result = call_user_func([$provider, 'sendLocation'], $instance['external_instance_id'], $validatedTo, $latitude, $longitude, $name, $address);
            
            return Response::success($result);
            
        } catch (\Exception $e) {
            Logger::error('Send location failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::serverError('Erro interno do servidor');
        }
    }
}