<?php

declare(strict_types=1);

namespace Raifs\Books;

/**
 * The related-party section. A party is related when the company names it, and
 * the section totals what the books moved with each one over the period. A line
 * with a counterparty nobody named stays out, and the section counts those lines
 * so a reader knows the list is complete or is not.
 */
final class RelatedParties
{
    /** @param list<string> $parties */
    public function __construct(
        private readonly Journal $journal,
        private readonly array $parties,
        private readonly string $period,
    ) {
    }

    /** @return list<string> */
    public function parties(): array
    {
        return $this->parties;
    }

    /** @return array<string, int> party => cents moved */
    public function totals(): array
    {
        $totals = array_fill_keys($this->parties, 0);
        foreach ($this->journal->in($this->period) as $entry) {
            if ($entry->counterparty === '' || !isset($totals[$entry->counterparty])) {
                continue;
            }
            $totals[$entry->counterparty] += $entry->cents;
        }

        return $totals;
    }

    public function unnamed(): int
    {
        $count = 0;
        foreach ($this->journal->in($this->period) as $entry) {
            if ($entry->counterparty === '' || !in_array($entry->counterparty, $this->parties, true)) {
                $count++;
            }
        }

        return $count;
    }
}
