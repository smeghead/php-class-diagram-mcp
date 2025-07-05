<?php

namespace ExcludePatternsTest\Events;

interface EventInterface
{
    public function getName(): string;
    public function getPayload(): array;
    public function getTimestamp(): \DateTimeInterface;
}

class UserCreatedEvent implements EventInterface
{
    private string $name = 'user.created';
    private array $payload;
    private \DateTimeInterface $timestamp;
    
    public function __construct(int $userId, string $userName, string $userEmail)
    {
        $this->payload = [
            'user_id' => $userId,
            'user_name' => $userName,
            'user_email' => $userEmail
        ];
        $this->timestamp = new \DateTimeImmutable();
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    public function getTimestamp(): \DateTimeInterface
    {
        return $this->timestamp;
    }
}

trait EventDispatcherTrait
{
    private array $listeners = [];
    
    public function addListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        $this->listeners[$eventName][] = $listener;
    }
    
    public function dispatch(EventInterface $event): void
    {
        $eventName = $event->getName();
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                $listener($event);
            }
        }
    }
}
