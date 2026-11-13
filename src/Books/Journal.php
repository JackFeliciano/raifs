<?php

declare(strict_types=1);

namespace Raifs\Books;

/**
 * The lines a company posts, held in the order they arrive. Every report reads
 * this book and nothing else, so a figure on the page can be traced back to the
 * lines that produced it.
 */
final class Journal
{
    /** @var list<Entry> */
    private array $entries = [];

    public function post(Entry $entry): void
    {
        $this->entries[] = $entry;
    }

    /** @return list<Entry> */
    public function entries(): array
    {
        return $this->entries;
    }

    public function count(): int
    {
        return count($this->entries);
    }

    /** @return list<string> */
    public function periods(): array
    {
        $seen = [];
        foreach ($this->entries as $entry) {
            $seen[$entry->period()] = true;
        }
        $periods = array_keys($seen);
        sort($periods);

        return $periods;
    }

    /** @return list<Entry> */
    public function in(string $period): array
    {
        $out = [];
        foreach ($this->entries as $entry) {
            if ($entry->period() === $period) {
                $out[] = $entry;
            }
        }

        return $out;
    }

    /** @return list<Entry> */
    public function forAccount(string $account): array
    {
        $out = [];
        foreach ($this->entries as $entry) {
            if ($entry->account === $account) {
                $out[] = $entry;
            }
        }

        return $out;
    }
}
