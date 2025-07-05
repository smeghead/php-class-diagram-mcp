<?php

namespace JsonlIntegrationTest;

/**
 * Simple User class for testing php-class-diagram
 */
class User
{
    private int $id;
    private string $name;
    private string $email;
    private UserRepository $repository;

    public function __construct(int $id, string $name, string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setRepository(UserRepository $repository): void
    {
        $this->repository = $repository;
    }

    public function save(): bool
    {
        return $this->repository->save($this);
    }
}
