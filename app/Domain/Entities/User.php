<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\ForbiddenRoleException;
use App\Domain\Exceptions\DomainValidationException;
use DateTimeImmutable;

final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_LIBRARIAN = 'librarian';

    private string $id;
    private string $name;
    private string $username;
    private string $passwordHash;
    private string $role;
    private ?DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $arg2,
        string $arg3,
        string $arg4,
        ?string $arg5 = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;

        if ($arg5 === null) {
            // 4 arguments: (id, username, passwordHash, role)
            $this->name = $arg2;
            $this->username = self::normalizeUsername($arg2);
            $cleanHash = trim($arg3);
            if ($cleanHash === '') {
                throw new DomainValidationException("El hash de contraseña no puede estar vacío.");
            }
            $this->passwordHash = $cleanHash;
            if (!in_array($arg4, [self::ROLE_ADMIN, self::ROLE_LIBRARIAN], true)) {
                throw new ForbiddenRoleException("Rol no válido. Solo se admite 'admin' o 'librarian'.");
            }
            $this->role = $arg4;
        } else {
            // 5 arguments: (id, name, username, passwordHash, role)
            $cleanName = trim($arg2);
            if ($cleanName === '') {
                throw new DomainValidationException("El nombre es obligatorio.");
            }
            $this->name = $cleanName;
            $this->username = self::normalizeUsername($arg3);
            $cleanHash = trim($arg4);
            if ($cleanHash === '') {
                throw new DomainValidationException("El hash de contraseña no puede estar vacío.");
            }
            $this->passwordHash = $cleanHash;
            if (!in_array($arg5, [self::ROLE_ADMIN, self::ROLE_LIBRARIAN], true)) {
                throw new ForbiddenRoleException("Rol no válido. Solo se admite 'admin' o 'librarian'.");
            }
            $this->role = $arg5;
        }
        $this->createdAt = $createdAt;
    }

    public static function normalizeUsername(string $username): string
    {
        $clean = mb_strtolower(trim($username), 'UTF-8');
        if ($clean === '') {
            throw new DomainValidationException("El nombre de usuario es obligatorio.");
        }
        return $clean;
    }

    // Concise methods
    public function id(): string { return $this->id; }
    public function name(): string { return $this->name; }
    public function username(): string { return $this->username; }
    public function passwordHash(): string { return $this->passwordHash; }
    public function role(): string { return $this->role; }
    public function createdAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }
    public function isLibrarian(): bool { return $this->role === self::ROLE_LIBRARIAN; }

    // JavaBean getters & aliases
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
}
