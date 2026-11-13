<?php

declare(strict_types=1);

namespace Raifs\Books;

/**
 * The balance sheet: what the company owns, what it owes, and the difference.
 * Assets hold a debit balance, liabilities and equity a credit balance, and the
 * sheet reports each account net of the entries that cancel it.
 */
final class BalanceSheet
{
    /** @param array<string, string> $kinds account => asset|liability|equity */
    public function __construct(
        private readonly Journal $journal,
        private readonly array $kinds,
    ) {
    }

    /** @return array<string, int> */
    public function net(): array
    {
        $net = [];
        foreach ($this->kinds as $account => $kind) {
            $net[$account] = 0;
        }
        foreach ($this->journal->entries() as $entry) {
            if (!isset($net[$entry->account])) {
                continue;
            }
            $sign = $entry->side === Entry::DEBIT ? 1 : -1;
            if (($this->kinds[$entry->account]) !== 'asset') {
                $sign = -$sign;
            }
            $net[$entry->account] += $sign * $entry->cents;
        }

        return $net;
    }

    /** @return array<string, int> */
    public function byKind(string $kind): array
    {
        $out = [];
        foreach ($this->net() as $account => $cents) {
            if ($this->kinds[$account] === $kind) {
                $out[$account] = $cents;
            }
        }

        return $out;
    }

    public function total(string $kind): int
    {
        return array_sum($this->byKind($kind));
    }

    /** Assets less liabilities. Equity carries the same figure as a credit. */
    public function equity(): int
    {
        return $this->total('asset') - $this->total('liability');
    }

    /**
     * The sheet balances when the assets equal the liabilities plus the equity
     * the books claim. A sheet that does not balance returns false, and the page
     * says so instead of hiding it.
     */
    public function balances(): bool
    {
        return $this->total('asset') === $this->total('liability') + $this->total('equity');
    }
}
