<?php

namespace App\Authentication\Model;

use DateTime;

class AccountJwtRefreshToken
{

    public int $id {
        get {
            return $this->id;
        }
        set {
            $this->id = $value;
        }
    }
    public int $account {
        get {
            return $this->account;
        }
        set {
            $this->account = $value;
        }
    }
    public string $token {
        get {
            return $this->token;
        }
        set {
            $this->token = $value;
        }
    }
    public DateTime $issued {
        get {
            return $this->issued;
        }
        set {
            $this->issued = $value;
        }
    }
    public DateTime $expires {
        get {
            return $this->expires;
        }
        set {
            $this->expires = $value;
        }
    }

    public function extract(bool $includeId = true): array
    {
        if($includeId) {
            $self['id'] = $this->id;
        }
        $self['account'] = $this->account;
        $self['token'] = $this->token;
        $self['issued'] = $this->issued->format('Y-m-d H:i:s');
        $self['expires'] = $this->expires->format('Y-m-d H:i:s');
        return $self;
    }

    public static function hydrate(array $data): self
    {
        $self = new self();
        $self->id = $data['id'];
        $self->account = $data['account'];
        $self->token = $data['token'];
        $self->issued = new DateTime($data['issued']);
        $self->expires = new DateTime($data['expires']);
        return $self;
    }

}
