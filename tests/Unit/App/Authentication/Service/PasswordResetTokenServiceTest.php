<?php declare(strict_types=1);

namespace Tests\Unit\App\Authentication\Service;

use App\Authentication\Model\Account;
use App\Authentication\Service\PasswordResetTokenService;
use Doctrine\DBAL\Connection;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @covers \App\Authentication\Service\PasswordResetTokenService
 */
class PasswordResetTokenServiceTest extends TestCase
{
    private MockObject|Connection $mockConnection;
    private MockObject|Logger $mockLogger;
    private PasswordResetTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Dependencies
        $this->mockConnection = $this->createMock(Connection::class);
        $this->mockLogger = $this->createMock(Logger::class);

        // Instantiate the service under test with mocks
        $this->service = new PasswordResetTokenService(
            $this->mockConnection,
            $this->mockLogger
        );
    }

    public function testGenerateTokenSuccess(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getUuid')->willReturn(Uuid::uuid('00000000-0000-0000-0000-000000000001') as UuidInterface);
        $account->id = 1; // Assuming the ID is needed for the WHERE clause

        // Mock DateTime objects for predictable testing
        $dateTime = new \DateTime();
        $expectedExpiry = $dateTime->modify('+60 minutes');

        // Mock DBAL Statement for update preparation/execution
        $mockStatement = $this->createMock(\Doctrine\DBAL\Statement::class);
        $mockStatement->expects($this->once())
            ->method('execute')
            ->with([
                'token' => $this->equalTo('TESTTOKENEXAMPLE'),
                'expiresAt' => $this->equalTo($expectedExpiry->format('Y-m-d H:i:s')),
                'id' => 1,
            ])
            ->willReturn(true);

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with('UPDATE db.Account SET passwordResetToken = :token, passwordResetTokenExpires = :expiresAt WHERE id = :id')
            ->willReturn($mockStatement);

        // Expect no critical/warning logs
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('warning');


        $account->method('getUuid')->willReturn(Uuid::fromString('00000000-0000-0000-0000-000000000001'));
        $result = $this->service->generateToken($account, 60);

        $this->assertInstanceOf(Account::class, $result);
        $this->assertEquals($account, $result);
    }

    public function testGenerateTokenFailureOnDbUpdate(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getUuid')->willReturn(Uuid::uuid('00000000-0000-0000-0000-000000000002') as UuidInterface);
        $account->id = 2;

        // Mock DBAL Statement returning 0 rows affected
        $mockStatement = $this->createMock(\Doctrine\DBAL\Statement::class);
        $mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStatement);

        // Expect a warning log because rowCount was 0
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Token update failed'));

        $result = $this->service->generateToken($account, 60);

        $this->assertNull($result);
    }

    public function testIsValidTokenFound(): void
    {
        $token = 'VALIDTOKEN';
        $account = $this->createMock(Account::class);
        $account->id = 1; // Mocking minimal required data

        // Mock Account hydration result for return type
        $mockAccountData = [1, $token, '2099-12-31 23:59:59'];
        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStatement = $this->createMock(\Doctrine\DBAL\Statement::class));
        $mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $mockStatement->expects($this->once())
            ->method('fetchNumeric')
            ->willReturn($mockAccountData);

        $this->mockLogger->expects($this->never())->method('error');

        $result = $this->service->isValidToken($token, $account);
        $this->assertTrue($result);
    }

    public function testIsValidTokenNotFound(): void
    {
        $token = 'INVALIDTOKEN';
        $account = $this->createMock(Account::class);

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStatement = $this->createMock(\Doctrine\DBAL\Statement::class));
        $mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $mockStatement->expects($this->once())
            ->method('fetchNumeric')
            ->willReturn(false); // No results found

        $this->mockLogger->expects($this->never())->method('error');

        $result = $this->service->isValidToken($token, $account);
        $this->assertFalse($result);
    }

    public function testExpireTokenWithoutAccount(): void
    {
        $token = 'EXPIRETOKEN';
        // Mock statement returning success > 0
        $mockStatement = $this->createMock(\Doctrine\DBAL\Statement::class);
        $mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(1); // Updated 1 row

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with('UPDATE db.Account SET passwordResetToken = :token, passwordResetTokenExpires = NULL WHERE passwordResetToken = :token')
            ->willReturn($mockStatement);

        $this->mockLogger->expects($this->never())->method('error');

        $result = $this->service->expireToken($token, null);
        $this->assertTrue($result);
    }

    public function testExpireTokenWithAccount(): void
    {
        $account = $this->createMock(Account::class);
        $account->id = 1;

        // Mock statement returning success > 0
        $mockStatement = $this->createMock(\Doctrine\DBAL\Statement::class);
        $mockStatement->expects($this->once())
            ->method('execute')
            ->willReturn(1); // Updated 1 row

        $this->mockConnection->expects($this->once())
            ->method('prepare')
            ->with('UPDATE db.Account SET passwordResetToken = NULL, passwordResetTokenExpires = NULL WHERE id = :id')
            ->willReturn($mockStatement);

        $this->mockLogger->expects($this->never())->method('error');

        $result = $this->service->expireToken('TOKEN123', $account);
        $this->assertTrue($result);
    }
    
    public function testValidateAndExpireSuccess(): void
    {
        $token = 'VALIDTOKEN';
        $account = $this->createMock(Account::class);
        $account->id = 1;

        // Setup mock for validation (success)
        $mockStatementGet = $this->createMock(\Doctrine\DBAL\Statement::class);
        $mockStatementGet->expects($this->once())->method('fetchNumeric')->willReturn([1, $token, '2099-12-31 23:59:59']);
        
        $this->mockConnection->expects($this->at(0)) // First call: validation check
            ->method('prepare')
            ->willReturn($mockStatementGet);
            
        // Setup mock for expiration (must be called after validation)
        $mockStatementExpire = $this->createMock(\Doctrine\DBAL\Statement::class);
        $mockStatementExpire->expects($this->once())->method('execute')->willReturn(1);

        $this->mockConnection->expects($this->at(1)) // Second call: expiration update
            ->method('prepare')
            ->willReturn($mockStatementExpire);

        // Expect no errors
        $this->mockLogger->expects($this->never())->method('error');

        $result = $this->service->validateAndExpire($token, $account);
        $this->assertInstanceOf(Account::class, $result);
    }
}