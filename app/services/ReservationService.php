<?php
declare(strict_types=1);

final class ReservationService
{
    public function __construct(private ReservationRepository $reservations)
    {
    }

    public function refresh(): void { $this->reservations->expireAndPromote(); }
    public function forMember(int $memberId): array { return $this->reservations->forMember($memberId); }
    public function allActive(): array { return $this->reservations->allActive(); }
    public function create(int $memberId, string $bookId): void
    {
        $id = filter_var($bookId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new InvalidArgumentException('A valid book is required.');
        $this->reservations->create($memberId, $id);
    }
    public function cancel(int $memberId, string $reservationId): void
    {
        $id = filter_var($reservationId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || !$this->reservations->cancel($id, $memberId)) throw new InvalidArgumentException('This reservation cannot be cancelled.');
    }
}