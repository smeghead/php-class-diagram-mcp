<?php

namespace TestProject;

abstract class BaseService
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    abstract public function process(): void;
}

class UserService extends BaseService
{
    private EmailValidator $emailValidator;
    
    public function __construct(UserRepositoryInterface $userRepository, EmailValidator $emailValidator)
    {
        parent::__construct($userRepository);
        $this->emailValidator = $emailValidator;
    }
    
    public function createUser(string $name, string $email): User
    {
        if (!$this->emailValidator->isValid($email)) {
            throw new \InvalidArgumentException('Invalid email address');
        }
        
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            throw new \RuntimeException('User with this email already exists');
        }
        
        $user = new User(0, $name, $email);
        $this->userRepository->save($user);
        
        return $user;
    }
    
    public function process(): void
    {
        // Implementation for abstract method
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $this->emailValidator->isValid($user->getEmail());
        }
    }
}

class EmailValidator
{
    public function isValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
