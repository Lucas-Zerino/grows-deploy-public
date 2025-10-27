<?php

namespace App\Providers;

interface ProviderInterface
{
    /**
     * Criar uma nova instância no provider
     * @param string $instanceName Nome da instância
     * @param string|null $instanceId ID interno da instância (para registro de webhook)
     * @return array ['success' => bool, 'instance_id' => string, 'data' => array]
     */
    public function createInstance(string $instanceName, ?string $instanceId = null): array;
    
    /**
     * Deletar uma instância do provider
     */
    public function deleteInstance(string $externalInstanceId): bool;
    
    /**
     * Enviar uma mensagem de texto
     */
    public function sendTextMessage(string $externalInstanceId, string $phone, string $text): array;
    
    /**
     * Enviar mensagem com mídia
     */
    public function sendMediaMessage(
        string $externalInstanceId,
        string $phone,
        string $mediaType,
        string $mediaUrl,
        string $caption = ''
    ): array;
    
    /**
     * Verificar status da instância
     */
    public function getInstanceStatus(string $externalInstanceId): array;
    
    /**
     * Verificar se provider está saudável
     */
    public function healthCheck(): bool;
    
    /**
     * Obter QR code para conexão
     */
    public function getQRCode(string $externalInstanceId): ?string;

    /**
     * Lista todas as instâncias do provider
     */
    public function listInstances(): array;

    /**
     * Desconecta uma instância no provider
     */
    public function disconnectInstance(string $externalInstanceId): array;
    
    /**
     * Conecta uma instância ao WhatsApp (inicia sessão e obtém QR code)
     */
    public function connect(string $externalInstanceId, ?string $phone = null): array;
    
    /**
     * Desconecta uma instância do WhatsApp
     */
    public function disconnect(string $externalInstanceId): array;
    
    /**
     * Obtém status da instância e QR code se disponível
     */
    public function getStatus(string $externalInstanceId): array;
    
    /**
     * Atualiza o nome da instância
     */
    public function updateInstanceName(string $externalInstanceId, string $newName): array;
    
    /**
     * Obtém configurações de privacidade
     */
    public function getPrivacy(string $externalInstanceId): array;
    
    /**
     * Atualiza configurações de privacidade
     */
    public function updatePrivacy(string $externalInstanceId, array $settings): array;
    
    /**
     * Define presença (online/offline)
     */
    public function setPresence(string $externalInstanceId, string $presence): array;
    
    /**
     * Solicita código de autenticação por telefone
     */
    public function requestAuthCode(string $externalInstanceId, string $phoneNumber): array;
    
    /**
     * Obtém QR code como imagem PNG (base64)
     */
    public function getQRCodeImage(string $externalInstanceId): array;
}

