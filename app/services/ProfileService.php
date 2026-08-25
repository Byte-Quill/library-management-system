<?php
declare(strict_types=1);

final class ProfileService
{
    public function __construct(private UserRepository $users, private ?AuditService $audits = null) {}

    public function update(int $id, array $input): array
    {
        $user = $this->users->find($id);
        $name = trim((string) ($input['name'] ?? '')); $email = strtolower(trim((string) ($input['email'] ?? '')));
        if (!$user || $name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Please provide a valid name and email.');
        if ($this->users->emailTakenByOther($id, $email)) throw new InvalidArgumentException('That email is already in use by another account.');
        $passwordHash = null; $currentPassword = (string) ($input['current_password'] ?? ''); $newPassword = (string) ($input['new_password'] ?? '');
        if ($newPassword !== '') {
            if (!password_verify($currentPassword, $user['password_hash']) || strlen($newPassword) < 8 || strlen($newPassword) > 4096) throw new InvalidArgumentException('Current password or new password is invalid.');
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        if (!$this->users->updateProfile($id, $name, $email, $passwordHash)) throw new InvalidArgumentException('Your account could not be updated.');
        $this->audits?->record($id, 'profile_updated', 'user', $id, ['password_changed' => $passwordHash !== null]);
        return ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $user['role'] ?? 'member'];
    }
}