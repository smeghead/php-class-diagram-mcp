<?php

namespace JsonlIntegrationTest;

/**
 * User repository class for testing php-class-diagram
 */
class UserRepository
{
    private array $users = [];

    public function save(User $user): bool
    {
        $this->users[$user->getId()] = $user;
        return true;
    }

    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->users);
    }

    public function delete(int $id): bool
    {
        if (isset($this->users[$id])) {
            unset($this->users[$id]);
            return true;
        }
        return false;
    }

    public function count(): int
    {
        return count($this->users);
    }
}
