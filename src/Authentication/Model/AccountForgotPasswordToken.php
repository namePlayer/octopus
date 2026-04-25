<?php

namespace App\Authentication\Model;

use DateTime;

class AccountForgotPasswordToken
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
    public DateTime $created {
        get {
            return $this->created;
        }
        set {
            $this->created = $value;
        }
    }
    public ?DateTime $used {
        get {
            return $this->used;
        }
        set {
            $this->used = $value;
        }
    }

    public function extract(bool $includeId = true): array
    {
        if($includeId) {
            $self['id'] = $this->id;
        }
        $self['account'] = $this->account;
        $self['token'] = $this->token;
        $self['created'] = $this->created->format('Y-m-d H:i:s');
        $self['used'] = $this->used?->format('Y-m-d H:i:s');
        return $self;
    }

    public static function hydrate(array $values): self
    {
        $self = new self();
        $self->id = $values['id'];
        $self->account = $values['account'];
        $self->token = $values['token'];
        $self->created = new DateTime($values['created']);
        $self->used = $values['used'] === null ? null : new DateTime($values['used']);
        return $self;
    }

}
