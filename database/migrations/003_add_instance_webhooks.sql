-- Migration: Add support for multiple webhooks per instance
-- Date: 2025-01-29

-- Create instance_webhooks table
CREATE TABLE instance_webhooks (
    id SERIAL PRIMARY KEY,
    instance_id INTEGER NOT NULL REFERENCES instances(id) ON DELETE CASCADE,
    webhook_url VARCHAR(500) NOT NULL,
    events JSONB NOT NULL DEFAULT '[]',
    is_active BOOLEAN NOT NULL DEFAULT true,
    retry_count INTEGER NOT NULL DEFAULT 0,
    last_retry_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Create indexes
CREATE INDEX idx_instance_webhooks_instance ON instance_webhooks(instance_id);
CREATE INDEX idx_instance_webhooks_active ON instance_webhooks(is_active);
CREATE INDEX idx_instance_webhooks_url ON instance_webhooks(webhook_url);

-- Migrate existing webhook_url from instances table to instance_webhooks
INSERT INTO instance_webhooks (instance_id, webhook_url, events, is_active)
SELECT 
    id as instance_id,
    webhook_url,
    '["message", "message.any", "message.ack", "message.reaction", "message.revoked", "message.edited", "session.status", "presence.update", "group.v2.join", "group.v2.leave", "group.v2.update", "group.v2.participants", "poll.vote", "chat.archive", "call.received", "call.accepted", "call.rejected", "label.upsert", "label.deleted", "label.chat.added", "label.chat.deleted", "event.response", "event.response.failed", "engine.event"]'::jsonb as events,
    CASE WHEN webhook_url IS NOT NULL AND webhook_url != '' THEN true ELSE false END as is_active
FROM instances 
WHERE webhook_url IS NOT NULL AND webhook_url != '';

-- Add comment
COMMENT ON TABLE instance_webhooks IS 'Multiple webhooks per instance for receiving events';
COMMENT ON COLUMN instance_webhooks.events IS 'Array of event types this webhook should receive';
COMMENT ON COLUMN instance_webhooks.retry_count IS 'Number of failed delivery attempts';
COMMENT ON COLUMN instance_webhooks.last_retry_at IS 'Last time a retry was attempted';
