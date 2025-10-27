<?php

namespace App\Services;

use App\Providers\ProviderManager;
use App\Models\Instance;
use App\Utils\Logger;

class WahaService
{
    /**
     * Get all contacts
     */
    public function getContacts(string $externalInstanceId): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->getContacts($externalInstanceId);
    }

    /**
     * Create contact
     */
    public function createContact(string $externalInstanceId, string $phone, string $name): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->createContact($externalInstanceId, $phone, $name);
    }

    /**
     * Delete contact
     */
    public function deleteContact(string $externalInstanceId, string $phone): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->deleteContact($externalInstanceId, $phone);
    }

    /**
     * Get contact info
     */
    public function getContactInfo(string $externalInstanceId, string $phone): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->getContactInfo($externalInstanceId, $phone);
    }

    /**
     * Check contacts
     */
    public function checkContacts(string $externalInstanceId, array $phones): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->checkContacts($externalInstanceId, $phones);
    }

    /**
     * Block contact
     */
    public function blockContact(string $externalInstanceId, string $phone): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->blockContact($externalInstanceId, $phone);
    }

    /**
     * Unblock contact
     */
    public function unblockContact(string $externalInstanceId, string $phone): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->unblockContact($externalInstanceId, $phone);
    }

    // ==================== GROUP METHODS ====================

    /**
     * Create group
     */
    public function createGroup(string $externalInstanceId, string $name, array $participants): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->createGroup($externalInstanceId, $name, $participants);
    }

    /**
     * Get group info
     */
    public function getGroupInfo(string $externalInstanceId, string $groupId): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->getGroupInfo($externalInstanceId, $groupId);
    }

    /**
     * Get group join info
     */
    public function getGroupJoinInfo(string $externalInstanceId, string $inviteCode): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->getGroupJoinInfo($externalInstanceId, $inviteCode);
    }

    /**
     * Join group
     */
    public function joinGroup(string $externalInstanceId, string $inviteCode): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->joinGroup($externalInstanceId, $inviteCode);
    }

    /**
     * Get group invite code
     */
    public function getGroupInviteCode(string $externalInstanceId, string $groupId): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->getGroupInviteCode($externalInstanceId, $groupId);
    }

    /**
     * Update group description
     */
    public function updateGroupDescription(string $externalInstanceId, string $groupId, string $description): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->updateGroupDescription($externalInstanceId, $groupId, $description);
    }

    /**
     * Update group subject
     */
    public function updateGroupSubject(string $externalInstanceId, string $groupId, string $subject): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->updateGroupSubject($externalInstanceId, $groupId, $subject);
    }

    /**
     * Update group picture
     */
    public function updateGroupPicture(string $externalInstanceId, string $groupId, string $image): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->updateGroupPicture($externalInstanceId, $groupId, $image);
    }

    /**
     * Add group participants
     */
    public function addGroupParticipants(string $externalInstanceId, string $groupId, array $participants): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->addGroupParticipants($externalInstanceId, $groupId, $participants);
    }

    /**
     * Remove group participants
     */
    public function removeGroupParticipants(string $externalInstanceId, string $groupId, array $participants): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->removeGroupParticipants($externalInstanceId, $groupId, $participants);
    }

    /**
     * Promote group participants
     */
    public function promoteGroupParticipants(string $externalInstanceId, string $groupId, array $participants): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->promoteGroupParticipants($externalInstanceId, $groupId, $participants);
    }

    /**
     * Demote group participants
     */
    public function demoteGroupParticipants(string $externalInstanceId, string $groupId, array $participants): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->demoteGroupParticipants($externalInstanceId, $groupId, $participants);
    }

    /**
     * Leave group
     */
    public function leaveGroup(string $externalInstanceId, string $groupId): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->leaveGroup($externalInstanceId, $groupId);
    }

    // ==================== COMMUNITY METHODS ====================

    /**
     * Create community
     */
    public function createCommunity(string $externalInstanceId, string $name): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->createCommunity($externalInstanceId, $name);
    }

    /**
     * List communities
     */
    public function listCommunities(string $externalInstanceId): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->listCommunities($externalInstanceId);
    }

    /**
     * Get community info
     */
    public function getCommunityInfo(string $externalInstanceId, string $communityId): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->getCommunityInfo($externalInstanceId, $communityId);
    }

    /**
     * Update community description
     */
    public function updateCommunityDescription(string $externalInstanceId, string $communityId, string $description): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->updateCommunityDescription($externalInstanceId, $communityId, $description);
    }

    /**
     * Update community name
     */
    public function updateCommunityName(string $externalInstanceId, string $communityId, string $name): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->updateCommunitySubject($externalInstanceId, $communityId, $name);
    }

    /**
     * Add groups to community
     */
    public function addGroupsToCommunity(string $externalInstanceId, string $communityId, array $groupIds): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->addGroupsToCommunity($externalInstanceId, $communityId, $groupIds);
    }

    /**
     * Remove groups from community
     */
    public function removeGroupsFromCommunity(string $externalInstanceId, string $communityId, array $groupIds): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->removeGroupsFromCommunity($externalInstanceId, $communityId, $groupIds);
    }

    /**
     * Leave community
     */
    public function leaveCommunity(string $externalInstanceId, string $communityId): array
    {
        $instance = Instance::findByExternalId($externalInstanceId);
        if (!$instance) {
            return [
                'success' => false,
                'error' => 'Instance not found',
            ];
        }

        $provider = ProviderManager::getProvider($instance['provider_id']);
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'Provider not found',
            ];
        }

        return $provider->leaveCommunity($externalInstanceId, $communityId);
    }
}
