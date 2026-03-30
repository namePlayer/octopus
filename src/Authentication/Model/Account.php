<?php
declare(strict_types=1);

namespace App\Authentication\Model;

use App\Software;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Account
{

    public int $id {
        get {
            return $this->id;
        }
        set {
            $this->id = $value;
        }
    }
    public UuidInterface $uuid {
        get {
            return $this->uuid;
        }
        set {
            $this->uuid = $value;
        }
    }
    public string $email {
        get {
            return $this->email;
        }
        set {
            $this->email = trim($value);
        }
    }
    public string $password {
        get {
            return $this->password;
        }
        set {
            $this->password = trim($value);
        }
    }
    public string $firstname {
        get {
            return $this->firstname;
        }
        set {
            $this->firstname = trim($value);
        }
    }
    public string $lastname {
        get {
            return $this->lastname;
        }
        set {
            $this->lastname = trim($value);
        }
    }
    public \DateTime $registeredAt {
        get {
            return $this->registeredAt;
        }
        set {
            $this->registeredAt = $value;
        }
    }

    public function extract(bool $includeId = true): array
    {
        $array = [];
        if ($includeId) {
            $array['id'] = $this->id;
        }
        $array['uuid'] = $this->uuid->toString();
        $array['email'] = $this->email;
        $array['password'] = $this->password;
        $array['firstname'] = $this->firstname;
        $array['lastname'] = $this->lastname;
        $array['registeredAt'] = $this->registeredAt->format(Software::DB_DATETIME_FORMAT);
        return $array;
    }

    public static function hydrate(array $data): self
    {
        $self = new self();
        $self->id = $data['id'];
        $self->uuid = Uuid::fromString($data['uuid']);
        $self->email = $data['email'];
        $self->password = $data['password'];
        $self->firstname = $data['firstname'];
        $self->lastname = $data['lastname'];
        $self->registeredAt = new \DateTime($data['registeredAt']);
        return $self;
    }

}
