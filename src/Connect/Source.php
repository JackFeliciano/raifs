<?php

declare(strict_types=1);

namespace Raifs\Connect;

/**
 * One thing a company points RAIFS at. A source opens, hands back rows, and
 * says what it is so the page can print the address it read.
 */
interface Source
{
    public function label(): string;

    /** @return list<array<string, mixed>> */
    public function rows(): array;
}
