<?php

declare(strict_types=1);

namespace Raifs\Books;

/**
 * One ledger line. The amount sits in whole cents so nothing drifts in a float,
 * and the side is debit or credit.
 */
final class Entry
{
    public const DEBIT = 'debit';
    public const CREDIT = 'credit';

    public function __construct(
        public readonly string $day,
        public readonly string $account,
        public readonly int $cents,
        public readonly string $side,
        public readonly string $counterparty = '',
        public readonly string $note = '',
    ) {
        if ($cents < 0) {
            throw new \RuntimeException('an entry carries a positive amount, the side says which way');
        }
        if (!in_array($side, [self::DEBIT, self::CREDIT], true)) {
            throw new \RuntimeException('an entry is a debit or a credit');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) !== 1) {
            throw new \RuntimeException('an entry carries a day as YYYY-MM-DD');
        }
    }

    public function period(): string
    {
        return substr($this->day, 0, 7);
    }
}
