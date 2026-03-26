<?php
declare(strict_types=1);

namespace App\Authentication\Model;

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
    public string $slug {
        get {
            return $this->slug;
        }
        set {
            $this->slug = $value;
        }
    }
    public string $email {
        get {
            return $this->email;
        }
        set {
            $this->email = $value;
        }
    }
    public string $password {
        get {
            return $this->password;
        }
        set {
            $this->password = $value;
        }
    }
    public string $firstname {
        get {
            return $this->firstname;
        }
        set {
            $this->firstname = $value;
        }
    }
    public string $lastname {
        get {
            return $this->lastname;
        }
        set {
            $this->lastname = $value;
        }
    }
    public \DateTime $registered {
        get {
            return $this->registered;
        }
        set {
            $this->registered = $value;
        }
    }

    public function extract(bool $includeId = true): array
    {
        $array = [];
        if ($includeId) {
            $array['id'] = $this->id;
        }
        $array['slug'] = $this->slug;
        $array['email'] = $this->email;
        $array['firstname'] = $this->firstname;
        $array['lastname'] = $this->lastname;
        $array['registered'] = $this->registered;
        return $array;
    }

    public static function hydrate(array $data): self
    {
        $self = new self();
        $self->id = $data['id'];
        $self->slug = $data['slug'];
        $self->email = $data['email'];
        $self->firstname = $data['firstname'];
        $self->lastname = $data['lastname'];
        $self->registered = $data['registered'];
        return $self;
    }

}
