<?php
declare(strict_types=1);

final class MemberService
{
    public function __construct(private UserRepository $users, private AuthService $auth)
    {
    }

    public function list(): array
    {
        return $this->users->allMembers();
    }

    public function create(string $name, string $email, string $password): void
    {
        $this->auth->createMember($name, $email, $password);
    }
}
