<?php

declare(strict_types=1);

namespace Raifs\Connect;

/**
 * The connector layer. A company registers the sources it wants a report built
 * from, and the ledger keys them by name so a report cites the address it read
 * instead of guessing at one.
 */
final class Ledger
{
    /** @var array<string, Source> */
    private array $sources = [];

    public function add(Source $source): void
    {
        $this->sources[$source->label()] = $source;
    }

    public function count(): int
    {
        return count($this->sources);
    }

    /** @return list<string> */
    public function labels(): array
    {
        return array_keys($this->sources);
    }

    /** @return list<array<string, mixed>> */
    public function records(): array
    {
        $all = [];
        foreach ($this->sources as $source) {
            foreach ($source->rows() as $row) {
                $all[] = $row;
            }
        }

        return $all;
    }

    /**
     * Every source has to answer before a report is built. One unreadable table
     * stops the run, and the failure names the source that did not answer.
     */
    public function read(): void
    {
        foreach ($this->sources as $label => $source) {
            $source->rows();
        }
    }
}
