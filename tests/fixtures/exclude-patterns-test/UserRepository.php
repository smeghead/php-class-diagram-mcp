<?php

namespace ExcludePatternsTest;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
    public function delete(int $id): bool;
    public function findAll(): array;
}

class UserRepository implements UserRepositoryInterface
{
    private array $users = [];
    private int $nextId = 1;
    
    public function findById(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }
    
    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }
        return null;
    }
    
    public function save(User $user): void
    {
        if ($user->getId() === 0) {
            // New user
            $newUser = new User($this->nextId++, $user->getName(), $user->getEmail());
            $this->users[$newUser->getId()] = $newUser;
        } else {
            // Update existing user
            $this->users[$user->getId()] = $user;
        }
    }
    
    public function delete(int $id): bool
    {
        if (isset($this->users[$id])) {
            unset($this->users[$id]);
            return true;
        }
        return false;
    }
    
    public function findAll(): array
    {
        return array_values($this->users);
    }
}
