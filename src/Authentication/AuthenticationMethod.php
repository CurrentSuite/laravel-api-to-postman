<?php

namespace AndreasElia\PostmanGenerator\Authentication;

use Illuminate\Contracts\Support\Arrayable;

abstract class AuthenticationMethod implements Arrayable
{
    protected ?string $token = null;

    public function __construct(?string $token = null)
    {
        $this->token = $token;
        //
    }

    public function toArray(): array
    {
        return [
            'key' => 'Authorization',
            'value' => sprintf('%s %s', $this->prefix(), $this->token ?? '{{token}}'),
        ];
    }

    public function getToken(): string
    {
        return $this->token;
    }

    abstract public function prefix(): string;
}
