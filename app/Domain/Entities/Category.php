<?php

declare(strict_types=1);

namespace App\Domain\Entities;

final class Category
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly ?string $description = null
    ) {}

    public function id(): string { return $this->id; }
    public function name(): string { return $this->name; }
    public function description(): ?string { return $this->description; }

    public function getId(): string { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
}
