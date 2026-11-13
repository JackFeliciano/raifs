<?php

declare(strict_types=1);

namespace Raifs\Books;

/**
 * The income statement for one period: the revenue credited, the expenses
 * debited, and what is left. The statement reads one period at a time so the
 * figure it prints carries the window it measured.
 */
final class IncomeStatement
{
    /** @param array<string, string> $kinds account => revenue|expense */
    public function __construct(
        private readonly Journal $journal,
        private readonly array $kinds,
    ) {
    }

    public function period(): string
    {
        $periods = $this->journal->periods();

        return $periods === [] ? '' : (string) end($periods);
    }

    public function revenue(string $period): int
    {
        return $this->sum($period, 'revenue');
    }

    public function expenses(string $period): int
    {
        return $this->sum($period, 'expense');
    }

    public function result(string $period): int
    {
        return $this->revenue($period) - $this->expenses($period);
    }

    private function sum(string $period, string $kind): int
    {
        $total = 0;
        foreach ($this->journal->in($period) as $entry) {
            if (($this->kinds[$entry->account] ?? '') !== $kind) {
                continue;
            }
            $total += $kind === 'revenue' ? $entry->cents : $entry->cents;
        }

        return $total;
    }

    /** @return list<array{account: string, kind: string, cents: int}> */
    public function lines(string $period): array
    {
        $lines = [];
        foreach ($this->kinds as $account => $kind) {
            $cents = 0;
            foreach ($this->journal->in($period) as $entry) {
                if ($entry->account === $account) {
                    $cents += $entry->cents;
                }
            }
            $lines[] = ['account' => $account, 'kind' => $kind, 'cents' => $cents];
        }

        return $lines;
    }
}
