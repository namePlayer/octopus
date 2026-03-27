<?php
declare(strict_types=1);

namespace App\Authentication\Service;

use App\Authentication\DTO\CreateAccountDTO;
use App\Authentication\Exception\AccountCreationFailedException;
use App\Authentication\Exception\AccountInsertIntoDatabaseFailedException;
use App\Authentication\Model\Account;
use App\Authentication\Table\AccountTable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class AccountService
{

    public function __construct(
        private readonly PasswordService $passwordService,
        private readonly AccountTable $accountTable,
    )
    {
    }

    public function create(CreateAccountDTO $createAccountDTO): UuidInterface
    {
        $account = new Account();
        do {
            $account->uuid = Uuid::uuid4();
        } while ($this->getUserByUuid($account->uuid) instanceof Account);
        $account->email = $createAccountDTO->email;
        $account->password = $this->passwordService->hashPassword($createAccountDTO->password);
        $account->firstname = '';
        $account->lastname = '';
        $account->registeredAt = new \DateTime();

        if($this->accountTable->insert($account) === false)
        {
            throw new AccountInsertIntoDatabaseFailedException();
        }
        return $account->uuid;
    }

    public function getUserByUuid(UuidInterface $uuid): ?Account
    {
        return $this->accountTable->findByUuid($uuid->toString());
    }

    public function getUserByEmail(string $email): ?Account
    {
        return $this->accountTable->findByEmail($email);
    }

}
