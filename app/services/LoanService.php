<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/models/LoanRepository.php';

final class LoanService
{
    public function __construct(private LoanRepository $loans, private array $config)
    {
    }

    public function activeForMember(int $memberId): array { return $this->loans->activeForMember($memberId); }
    public function issue(int $memberId, string $copyId): void
    {
        $id = filter_var($copyId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new InvalidArgumentException('A valid copy is required.');
        $dueAt = (new DateTimeImmutable('now'))->modify('+' . $this->config['loan_days'] . ' days')->format('Y-m-d H:i:s');
        $this->loans->issue($memberId, $id, $this->config['max_active_loans'], $dueAt);
    }
    public function overdueForReturn(): array { return $this->loans->overdueForReturn(); }
    public function returnLoan(string $loanId): void
    {
        $id = filter_var($loanId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) throw new InvalidArgumentException('A valid loan is required.');
        $this->loans->returnLoan($id, $this->config['fine']);
    }
}