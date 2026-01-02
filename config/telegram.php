<?php
/**
 * CrossConnect MY - Telegram Notification Helper
 * Send notifications via Telegram Bot API
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/settings.php';

/**
 * Send a message via Telegram Bot API
 * 
 * @param string $message The message to send (supports Markdown)
 * @param string|null $customChatId Optional custom chat ID (uses saved setting if not provided)
 * @return array Result with success status and message/error
 */
function sendTelegramMessage($message, $customChatId = null)
{
    // Get Telegram settings
    $telegramSettings = getSettingsByGroup('telegram');

    $enabled = ($telegramSettings['telegram_enabled'] ?? '0') === '1';
    $botToken = $telegramSettings['telegram_bot_token'] ?? '';
    $chatId = $customChatId ?? ($telegramSettings['telegram_chat_id'] ?? '');

    // Check if Telegram is enabled and configured
    if (!$enabled) {
        return ['success' => false, 'error' => 'Telegram notifications are disabled'];
    }

    if (empty($botToken) || empty($chatId)) {
        return ['success' => false, 'error' => 'Telegram not configured (missing bot token or chat ID)'];
    }

    // Send message via Telegram Bot API
    $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegramUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("Telegram API curl error: " . $curlError);
        return ['success' => false, 'error' => 'Connection error: ' . $curlError];
    }

    $result = json_decode($response, true);

    if ($httpCode === 200 && isset($result['ok']) && $result['ok']) {
        return ['success' => true, 'message_id' => $result['result']['message_id'] ?? null];
    } else {
        $errorMsg = $result['description'] ?? 'Unknown error';
        error_log("Telegram API error: " . $errorMsg);
        return ['success' => false, 'error' => $errorMsg];
    }
}

/**
 * Send admin notification via Telegram
 * Use this for important admin alerts
 * 
 * @param string $title Notification title
 * @param string $body Notification body
 * @param string $type Type: info, success, warning, error
 * @return array Result
 */
function sendTelegramNotification($title, $body, $type = 'info')
{
    $emoji = match ($type) {
        'success' => '✅',
        'warning' => '⚠️',
        'error' => '🚨',
        default => '🔔'
    };

    $message = "{$emoji} *{$title}*\n\n{$body}\n\n_" . date('Y-m-d H:i:s') . "_";

    return sendTelegramMessage($message);
}

/**
 * Send a notification for new church submission
 * 
 * @param array $church Church data
 * @return array Result
 */
function notifyNewChurch($church)
{
    $title = "New Church Submitted";
    $body = "📍 *{$church['name']}*\n";
    $body .= "📌 {$church['city']}, {$church['state_name']}\n";
    if (!empty($church['submitted_by'])) {
        $body .= "👤 Submitted by: {$church['submitted_by']}";
    }

    return sendTelegramNotification($title, $body, 'info');
}

/**
 * Send a notification for new event submission
 * 
 * @param array $event Event data
 * @return array Result
 */
function notifyNewEvent($event)
{
    $title = "New Event Submitted";
    $body = "📅 *{$event['title']}*\n";
    $body .= "📍 {$event['venue']}\n";
    $body .= "🗓️ {$event['start_date']}";

    return sendTelegramNotification($title, $body, 'info');
}

/**
 * Send a notification for new user registration
 * 
 * @param array $user User data
 * @return array Result
 */
function notifyNewUser($user)
{
    $title = "New User Registration";
    $body = "👤 *{$user['name']}*\n";
    $body .= "📧 {$user['email']}\n";
    $body .= "🏷️ Username: {$user['username']}";

    return sendTelegramNotification($title, $body, 'success');
}
