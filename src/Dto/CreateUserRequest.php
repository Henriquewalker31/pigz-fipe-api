<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank, Assert\Length(max: 120)] public readonly string $name = '',
        #[Assert\NotBlank, Assert\Email] public readonly string $email = '',
        #[Assert\NotBlank, Assert\Length(min: 8)] public readonly string $password = '',
        public readonly bool $admin = false,
    ) {}
}