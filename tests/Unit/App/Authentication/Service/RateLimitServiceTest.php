<?php

declare(strict_types=1);

use App\Authentication\Service\RateLimitService;
use App\Base\Factory\DBFactory; // Wird im Test für Mocking-Zwecke ignoriert, da wir Connection mocken
use League\Container\ContainerInterface; // Platzhalter für Context
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class RateLimitServiceTest extends TestCase
{
    private Logger $mockLogger;
    private Connection $mockConnection;
    private RateLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockLogger = $this->createMock(Logger::class);
        $this->mockConnection = $this->createMock(Connection::class);
        
        // Service mit den manuell mockierten Abhängigkeiten erstellen
        $this->service = new RateLimitService($this->mockLogger, $this->mockConnection);
    }

    /**
     * Testet erfolgreiche Prüfung, wenn die Limit-Grenze nicht überschritten ist.
     * Wir erwarten, dass kein INSERT/DELETE ausgeführt wird, wenn wir das Mocking
     * auf der Selektion stoppen, aber wir testen hier den Erfolgsfall.
     */
    public function testCheckRateLimitWithinLimit(): void
    {
        $email = 'test@example.com';
        
        // 1. Mocking Mocking der SELECT-Abfrage (SELECT COUNT(*) ...)
        $mockStatement = $this->createMock(Statement::class);
        // Simuliert einen Erfolg: Count < 3
        $mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        // Simuliert, dass die Abfrage durchgeführt wurde
        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT COUNT(*)'))
            ->willReturn($mockStatement);
        
        // 2. Mocking des INSERT/DELETE für recordAttempt (sollte ausgeführt werden)
        $this->mockConnection->expects($this->atLeastOnce())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO auth_password_reset_attempts'))
            ->willReturn($this->createMock(Statement::class));

        // Wir erwarten, dass die Limit-Überprüfung erfolgreich ist und ein Attempt aufgezeichnet wird
        $recordMock = $this->createMock(Statement::class);
        $recordMock->expects($this->once())->method('execute');
        $this->mockConnection->expects($this->atLeastOnce())->method('prepare')->willReturn($recordMock);

        $result = $this->service->checkRateLimit($email);
        
        $this->assertTrue($result, "Der Check sollte true zurückgeben, wenn das Limit nicht erreicht ist.");
    }

    /**
     * Testet das Verhalten, wenn Rate Limits überschritten wurden.
     * Wir erwarten, dass checkRateLimit false gibt und recordAttempt ausgeführt wird.
     */
    public function testRateLimitExceeded(): void
    {
        $email = 'test@example.com';
        
        // 1. Mocking Mocking der SELECT-Abfrage: Simuliert, dass Count >= 3
        $mockStatement = $this->createMock(Statement::class);
        $mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        
        // Simuliert einen Fehler, wenn Limit überschritten ist (gibt Count = 3 oder mehr zurück)
        $mockStatement->method('fetch')->willReturn(['count' => 3]); 
        
        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT COUNT(*)'))
            ->willReturn($mockStatement);
        
        // 2. Mocking der Aufzeichnung eines Versuchs (MUSS ausgeführt werden)
        $recordMock = $this->createMock(Statement::class);
        $recordMock->expects($this->once())->method('execute');
        $this->mockConnection->expects($this->once())->method('prepare')->with($this->stringContains('INSERT INTO auth_password_reset_attempts'))->willReturn($recordMock);

        // checkAndRecord sollte false zurückgeben
        $result = $this->service->checkAndRecord($email);
        
        $this->assertFalse($result, "Der Vorgang sollte false zurückgeben, da das Limit überschritten wurde.");
    }

    /**
     * Testet das manuelle Zurücksetzen des Rate-Limits.
     */
    public function testResetLimit(): void
    {
        $email = 'reset@example.com';
        
        // Wir erwarten, dass 'DELETE FROM...' ausgeführt wird
        $mockStatement = $this->createMock(Statement::class);
        $mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(1); // 1 Zeile gelöscht
        
        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM auth_password_reset_attempts'))
            ->willReturn($mockStatement);

        // Testet das Zurücksetzen
        $this->service->resetLimit($email);
        
        // Logik wird über LoggerInterface gemockt, Überprüfung der Log-Aufrufe nicht möglich, aber der DB-Teil ist geprüft.
    }
    
    /**
     * Testet das Aufzeichnen eines Attempts ohne Angabe von Gründen.
     */
    public function testRecordAttemptNoReason(): void
    {
        $email = 'no_reason@example.com';
        
        // Wir erwarten, dass der INSERT ausgeführt wird und der Grund 'Rate Limit Violation' ist
        $mockStatement = $this->createMock(Statement::class);
        $mockStatement->expects($this->once())
            ->method('execute')
            ->with([$this->equalTo($email), $this->isType('string')]);

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO auth_password_reset_attempts'))
            ->willReturn($mockStatement);
        
        $this->service->recordAttempt($email);
    }
    
    /**
     * Testet die Tabelle-Existenzprüfung. Sollte bei fehlgeschlagenem CREATE KEINEN Fehler werfen.
     * Wir mocking die exec Methode, um den Erfolg bei 'Tabelle existiert bereits' zu simulieren.
     */
    public function testEnsureTableExists(): void
    {
        $this->mockConnection->expects($this->once())
            ->method('exec')
            ->willThrowException(new \PDOException('Table already exists')); // Simuliert das catch'te PDOException
        
        // Die Methode sollte keine Exception werfen
        $this->service->ensureTableExists();
    }
}