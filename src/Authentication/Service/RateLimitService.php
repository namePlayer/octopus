<?php

declare(strict_types=1);

namespace App\Authentication\Service;

use App\Base\Factory\DBFactory;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use DateTime;

class RateLimitService
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function checkRateLimit(string $email): bool
    {
        $db = DBFactory->get();
        $conn = $db->getConnection();
        
        // MAX (count(*) OVER ()) kann nicht verwendet werden, alternative Abfrage:
        $stmt = $conn->prepare('SELECT COUNT(*) as count FROM auth_password_reset_attempts WHERE email = ? AND attempt_time > ?');
        $stmt->execute([$email, new DateTime('-1 hour')]);
        $result = $stmt->fetch();
        
        if ($result && $result['count'] < 3) {
            return true;
        }
        
        return false;
    }

    public function resetLimit(string $email): void
    {
        $db = DBFactory->get();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare('DELETE FROM auth_password_reset_attempts WHERE email = ?');
        $stmt->execute([$email]);
        
        $this->logger->info('Rate-Limit für Email zurückgesetzt: ' . $email);
    }

    /**
     * Registriert eine Rate-Limit-Verletzung
     */
    public function recordAttempt(string $email, ?string $reason = null): void
    {
        try {
            $db = DBFactory->get();
            $conn = $db->getConnection();
            
            // Check ob Tabelle existiert, falls nicht, erstellen
            $this->ensureTableExists($conn);
            
            $stmt = $conn->prepare('INSERT INTO auth_password_reset_attempts (email, attempt_time, reason) VALUES (?, NOW(), ?)');
            $stmt->execute([$email, $reason ?? 'Rate Limit Violation']);
            
            $this->logger->warning('Rate-Limit Versuch aufgezeichnet: ' . $email . ' - ' . ($reason ?? 'unknown'));
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Erfassen Rate-Limit: ' . $e->getMessage());
        }
    }

    /**
     * Checkt Rate-Limit und zeichnet Versuch auf
     */
    public function checkAndRecord(string $email): bool
    {
        // Zuerst versuchen, Rate-Limit zu prüfen
        if ($this->checkRateLimit($email)) {
            $this->recordAttempt($email, 'allowed');
            return true;
        }
        
        // Rate-Limit erreicht, aufzeichne Verletzung
        $this->recordAttempt($email, 'Rate Limit Reached');
        return false;
    }

    /**
     * Stellt sicher, dass die Rate-Limit-Tabelle existiert
     */
    protected function ensureTableExists(\PDOConnection $conn): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS auth_password_reset_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                attempt_time DATETIME NOT NULL,
                reason TEXT,
                UNIQUE KEY unique_key (email, attempt_time)
            )
        ";
        
        try {
            $conn->exec($sql);
        } catch (\PDOException $e) {
            $this->logger->warning('Tabelle existiert bereits oder Fehler beim Erstellen: ' . $e->getMessage());
        }
    }
}
