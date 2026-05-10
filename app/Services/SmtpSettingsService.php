<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Manages SMTP configuration stored in system_settings.
 *
 * Passwords are stored encrypted using Laravel's Crypt facade with an
 * "enc:" prefix so plain-text legacy values are still handled gracefully.
 */
class SmtpSettingsService
{
    private const ENC_PREFIX = 'enc:';

    public function __construct(private readonly SettingService $settings) {}

    // ── Read ──────────────────────────────────────────────────────

    /**
     * Get all SMTP settings as a config array (password decrypted).
     */
    public function getConfig(): array
    {
        return [
            'enabled'      => (bool) $this->settings->get('mail_enabled', false),
            'host'         => $this->settings->get('smtp_host', ''),
            'port'         => (int) $this->settings->get('smtp_port', 587),
            'username'     => $this->settings->get('smtp_username', ''),
            'password'     => $this->getDecryptedPassword(),
            'encryption'   => $this->settings->get('smtp_encryption', 'tls'),
            'from_name'    => $this->settings->get('mail_from_name', config('app.name')),
            'from_address' => $this->settings->get('mail_from_address', ''),
        ];
    }

    /**
     * Decrypt the stored SMTP password. Returns empty string if not set.
     */
    public function getDecryptedPassword(): string
    {
        $raw = $this->settings->get('smtp_password', '');
        if (! $raw || $raw === 'null') return '';

        if (str_starts_with($raw, self::ENC_PREFIX)) {
            try {
                return Crypt::decryptString(substr($raw, strlen(self::ENC_PREFIX)));
            } catch (\Throwable) {
                return ''; // Decryption failed — treat as empty
            }
        }

        // Legacy plain-text value — return as-is (will be re-encrypted on next save)
        return $raw;
    }

    /**
     * Check if SMTP is fully configured (host + username/password set).
     */
    public function isConfigured(): bool
    {
        $cfg = $this->getConfig();
        return ! empty($cfg['host']) && ! empty($cfg['from_address']);
    }

    /**
     * Check if mail is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return $this->isConfigured() && (bool) $this->settings->get('mail_enabled', false);
    }

    // ── Write ─────────────────────────────────────────────────────

    /**
     * Save SMTP settings. Password is encrypted before storage.
     * If $password is empty or the sentinel placeholder, existing password is kept.
     */
    public function save(array $data): void
    {
        $toSave = [
            'mail_enabled'     => isset($data['mail_enabled']) ? (bool) $data['mail_enabled'] : false,
            'smtp_host'        => trim($data['smtp_host'] ?? ''),
            'smtp_port'        => (int) ($data['smtp_port'] ?? 587),
            'smtp_username'    => trim($data['smtp_username'] ?? ''),
            'smtp_encryption'  => $data['smtp_encryption'] ?? 'tls',
            'mail_from_name'   => trim($data['mail_from_name'] ?? ''),
            'mail_from_address'=> trim($data['mail_from_address'] ?? ''),
        ];

        // Handle password: encrypt if a new one is provided
        $newPwd = $data['smtp_password'] ?? '';
        if ($newPwd && $newPwd !== '••••••••') {
            $toSave['smtp_password'] = self::ENC_PREFIX . Crypt::encryptString($newPwd);
        }
        // If blank/placeholder → don't include in setMany (keeps existing)

        $this->settings->setMany($toSave, 'smtp');
    }

    /**
     * Toggle mail_enabled on/off.
     */
    public function toggleEnabled(bool $enabled): void
    {
        $this->settings->set('mail_enabled', $enabled, 'Enable Mail', null, 'smtp', 'boolean');
    }

    // ── Runtime application ───────────────────────────────────────

    /**
     * Apply saved SMTP settings to Laravel's runtime mail configuration.
     * Call this in AppServiceProvider::boot() so every request uses DB config.
     */
    public function applyToRuntime(): void
    {
        $cfg = $this->getConfig();

        if (empty($cfg['host'])) return; // Nothing configured — use .env defaults

        config([
            'mail.default'                  => 'smtp',
            'mail.mailers.smtp.host'        => $cfg['host'],
            'mail.mailers.smtp.port'        => $cfg['port'],
            'mail.mailers.smtp.username'    => $cfg['username'],
            'mail.mailers.smtp.password'    => $cfg['password'],
            'mail.mailers.smtp.encryption'  => $cfg['encryption'] ?: null,
            'mail.from.name'                => $cfg['from_name'],
            'mail.from.address'             => $cfg['from_address'] ?: config('mail.from.address'),
        ]);

        // If mail is disabled, redirect all mail to log driver so nothing is sent
        if (! $cfg['enabled']) {
            config(['mail.default' => 'log']);
        }
    }

    // ── Test ─────────────────────────────────────────────────────

    /**
     * Send a test email using the current (runtime-applied) SMTP configuration.
     * Returns ['success' => bool, 'message' => string, 'detail' => string]
     */
    public function testConnection(string $recipientEmail, string $senderName): array
    {
        // Apply the latest saved settings to runtime before testing
        $this->applyToRuntime();

        // Temporarily enable mail even if globally disabled (this is a test)
        config(['mail.default' => 'smtp']);

        $cfg = $this->getConfig();

        if (empty($cfg['host'])) {
            return ['success' => false, 'message' => 'SMTP host is not configured.', 'detail' => ''];
        }
        if (empty($cfg['from_address'])) {
            return ['success' => false, 'message' => 'Sender email address is not set.', 'detail' => ''];
        }

        try {
            Mail::raw(
                "Hello!\n\n"
                . "This is a test email sent from {$senderName} HRMS to verify your SMTP configuration.\n\n"
                . "If you received this, your mail settings are working correctly.\n\n"
                . "Connection details:\n"
                . "  Host: {$cfg['host']}\n"
                . "  Port: {$cfg['port']}\n"
                . "  Encryption: " . (strtoupper($cfg['encryption']) ?: 'None') . "\n"
                . "  Username: {$cfg['username']}",
                function ($msg) use ($recipientEmail, $senderName, $cfg) {
                    $msg->to($recipientEmail)
                        ->subject("✅ SMTP Test — {$senderName} HRMS")
                        ->from($cfg['from_address'], $cfg['from_name']);
                }
            );

            return [
                'success' => true,
                'message' => "Test email sent successfully to {$recipientEmail}.",
                'detail'  => "Via {$cfg['host']}:{$cfg['port']} ({$cfg['encryption']})",
            ];
        } catch (\Throwable $e) {
            Log::warning('SMTP test failed', ['error' => $e->getMessage()]);

            // Parse the exception message for a clean user-facing message
            $raw     = $e->getMessage();
            $detail  = $this->parseSmtpError($raw);

            return [
                'success' => false,
                'message' => 'SMTP connection failed.',
                'detail'  => $detail,
            ];
        }
    }

    // ── Private helpers ───────────────────────────────────────────

    private function parseSmtpError(string $message): string
    {
        // Common SMTP errors → friendly messages
        $patterns = [
            '/Connection refused/i'                    => 'Connection refused — check host and port.',
            '/getaddrinfo.*failed/i'                   => 'Host not found — verify the SMTP hostname.',
            '/Authentication failed/i'                 => 'Authentication failed — check username and password.',
            '/535/'                                    => 'Authentication rejected (535) — invalid credentials.',
            '/534/'                                    => 'Authentication not allowed — may need an App Password.',
            '/Connection timed out/i'                  => 'Connection timed out — firewall or wrong port.',
            '/SSL.*failed/i'                           => 'SSL handshake failed — try a different encryption.',
            '/stream_socket_enable_crypto/i'           => 'Encryption negotiation failed — check encryption type.',
            '/Could not connect to SMTP host/i'        => 'Could not connect — verify host and port.',
            '/Expected response code 250/i'            => 'Unexpected server response — SMTP command failed.',
        ];

        foreach ($patterns as $pattern => $friendly) {
            if (preg_match($pattern, $message)) {
                return $friendly;
            }
        }

        // Return first 200 chars of the raw message as fallback
        return strlen($message) > 200 ? substr($message, 0, 200) . '…' : $message;
    }
}
