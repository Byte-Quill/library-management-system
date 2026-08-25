<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/models/LoanRepository.php';

final class LoanService
{
    public function __construct(private LoanRepository $loans, private array $config, private ?ReservationRepository $reservations = null, private ?CopyRepository $copies = null)
    {
    }

    public function activeForMember(int $memberId): array { return $this->loans->activeForMember($memberId); }
    public function availableCopies(): array { return $this->copies?->availableForLoan() ?? []; }
    public function issue(int $memberId, string $copyId): void
    {
        $id = filter_var($copyId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new InvalidArgumentException('A valid copy is required.');
        $this->loans->issue($memberId, $id, $this->config['max_active_loans'], $this->config['loan_days']);
    }
    public function activeForReturn(): array { return $this->loans->activeForReturn(); }
    public function returnLoan(string $loanId): void
    {
        $id = filter_var($loanId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new InvalidArgumentException('A valid loan is required.');
        $this->loans->returnLoan($id, $this->config['fine']);
        // A returned copy may satisfy a queued reservation.
        if ($this->reservations !== null) {
            try { $this->reservations->expireAndPromote(); } catch (Throwable $exception) { error_log($exception->__toString()); }
        }
    }
}