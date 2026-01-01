-- Migration: Add email_logs table for tracking email delivery status
-- This table stores all emails sent and their delivery status from webhooks

CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) NULL COMMENT 'Provider message ID for webhook correlation',
    provider VARCHAR(50) NOT NULL DEFAULT 'smtp2go' COMMENT 'smtp2go, brevo, phpmail',
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NULL,
    status ENUM('queued', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'spam') DEFAULT 'queued',
    error_message TEXT NULL,
    metadata JSON NULL COMMENT 'Additional data like open count, click URLs, etc.',
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id),
    INDEX idx_recipient (recipient),
    INDEX idx_status (status),
    INDEX idx_provider (provider),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
