<?php

declare(strict_types=1);

namespace Raifs\Books;

/**
 * The annual report: the statements, the parties, and the sources the run read.
 * It gathers rather than computes, so every figure it carries comes from a
 * statement that already answered.
 */
final class AnnualReport
{
    /**
     * @param array<string, array<string, int>> $sections
     * @param list<string> $sources
     */
    public function __construct(
        private readonly string $title,
        private readonly array $sections,
        private readonly array $sources,
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    /** @return array<string, array<string, int>> */
    public function sections(): array
    {
        return $this->sections;
    }

    /** @return list<string> */
    public function sources(): array
    {
        return $this->sources;
    }

    /** @return list<string> section titles in reading order */
    public function headings(): array
    {
        return array_keys($this->sections);
    }

    public function total(string $section): int
    {
        return array_sum($this->sections[$section] ?? []);
    }
}
