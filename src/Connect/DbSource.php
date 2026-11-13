<?php

declare(strict_types=1);

namespace Raifs\Connect;

/**
 * Reads a table through a PDO handle at any address a company gives it. RAIFS
 * ships no database of its own: the handle arrives from the caller, and the
 * driver underneath decides how it was opened. The query names the table and
 * the source quotes both back, so a viewer can tell which table it read.
 */
final class DbSource implements Source
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly string $table,
        private readonly string $address,
    ) {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) !== 1) {
            throw new \RuntimeException('a table name carries letters, digits and underscores');
        }
    }

    public function label(): string
    {
        return 'db:' . $this->address . '/' . $this->table;
    }

    /** @return list<array<string, mixed>> */
    public function rows(): array
    {
        $statement = $this->pdo->query('SELECT * FROM ' . $this->table);

        return $statement === false ? [] : $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
