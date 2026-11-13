<?php

declare(strict_types=1);

namespace Raifs\Connect;

/**
 * Reads a JSON file in the APIX shape. The file holds a list of records, and
 * each record is a flat object. A file that is not a list of objects is refused
 * at open, so a bad file fails at the first call instead of halfway through a
 * report.
 */
final class JsonSource implements Source
{
    /** @var list<array<string, mixed>> */
    private array $rows = [];

    public function __construct(private readonly string $path)
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('cannot read ' . $path);
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException($path . ' does not hold JSON');
        }
        foreach ($data as $record) {
            if (!is_array($record)) {
                throw new \RuntimeException($path . ' holds a list of objects only');
            }
            $this->rows[] = $record;
        }
    }

    public function label(): string
    {
        return 'json:' . basename($this->path);
    }

    /** @return list<array<string, mixed>> */
    public function rows(): array
    {
        return $this->rows;
    }
}
