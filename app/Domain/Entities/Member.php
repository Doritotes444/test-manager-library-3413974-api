<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\DomainValidationException;
use DateTimeImmutable;

final class Member
{
    private string $id;
    private string $name;
    private string $documentType;
    private string $documentNumber;
    private string $email;
    private string $phone;
    private ?DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $name,
        string $documentType = 'CC',
        string $documentNumber = '',
        string $email = '',
        string $phone = '',
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;

        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new DomainValidationException("El nombre del socio es obligatorio.");
        }
        $this->name = $cleanName;

        $docType = strtoupper(trim($documentType));
        if ($docType === '' || !in_array($docType, ['CC', 'TI', 'CE', 'PAS'], true)) {
            $docType = 'CC';
        }
        $this->documentType = $docType;

        $cleanDoc = trim($documentNumber);
        if ($cleanDoc === '') {
            throw new DomainValidationException("El documento del socio es obligatorio.");
        }
        $this->documentNumber = $cleanDoc;

        $cleanEmail = trim($email);
        if ($cleanEmail !== '' && !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            throw new DomainValidationException("El correo electrónico del socio no es válido.");
        }
        $this->email = $cleanEmail;

        $this->phone = trim($phone);
        $this->createdAt = $createdAt;
    }

    // Concise methods
    public function id(): string { return $this->id; }
    public function name(): string { return $this->name; }
    public function fullName(): string { return $this->name; }
    public function documentType(): string { return $this->documentType; }
    public function documentNumber(): string { return $this->documentNumber; }
    public function email(): string { return $this->email; }
    public function phone(): string { return $this->phone; }
    public function createdAt(): ?DateTimeImmutable { return $this->createdAt; }

    // JavaBean getters & aliases
    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDocumentType(): string { return $this->documentType; }
    public function getDocumentNumber(): string { return $this->documentNumber; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }

    public function updateContact(string $name, string $email = '', string $phone = ''): void
    {
        $cleanName = trim($name);
        if ($cleanName === '') {
            throw new DomainValidationException("El nombre del socio no puede estar vacío.");
        }
        $this->name = $cleanName;

        $cleanEmail = trim($email);
        if ($cleanEmail !== '' && !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            throw new DomainValidationException("El correo electrónico del socio no es válido.");
        }
        $this->email = $cleanEmail;
        $this->phone = trim($phone);
    }
}
