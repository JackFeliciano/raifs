<?php

declare(strict_types=1);

namespace Raifs\Tests;

use Raifs\Connect\DbSource;
use Raifs\Connect\JsonSource;
use Raifs\Connect\Ledger;

$dir = __DIR__ . '/fixtures';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$good = $dir . '/rows.json';
file_put_contents($good, json_encode([
    ['account' => 'Cash', 'cents' => 100],
    ['account' => 'Sales', 'cents' => 200],
]));
$bad = $dir . '/loose.json';
file_put_contents($bad, json_encode(['account' => 'Cash']));

$pdo = static function (): \PDO {
    $handle = new \PDO('sqlite::memory:');
    $handle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $handle->exec('CREATE TABLE entries (account TEXT, cents INTEGER)');
    $handle->exec("INSERT INTO entries VALUES ('Cash', 300), ('Freight', 40)");

    return $handle;
};

return [
    ['a json source reads a list of records', static function () use ($good): void {
        $source = new JsonSource($good);
        Assert::same(2, count($source->rows()));
        Assert::same('json:rows.json', $source->label());
    }],
    ['a json source refuses a file that holds one object', static function () use ($bad): void {
        try {
            new JsonSource($bad);
            Assert::true(false, 'a loose object opened');
        } catch (\RuntimeException $e) {
            Assert::contains('list of objects', $e->getMessage());
        }
    }],
    ['a json source refuses a path with no file behind it', static function (): void {
        try {
            new JsonSource(__DIR__ . '/fixtures/absent.json');
            Assert::true(false, 'an absent file opened');
        } catch (\RuntimeException $e) {
            Assert::contains('cannot read', $e->getMessage());
        }
    }],
    ['a db source reads the table it was given', static function () use ($pdo): void {
        $source = new DbSource($pdo(), 'entries', 'sqlite::memory:');
        Assert::same(2, count($source->rows()));
        Assert::same('db:sqlite::memory:/entries', $source->label());
    }],
    ['a db source refuses a table name that carries punctuation', static function () use ($pdo): void {
        try {
            new DbSource($pdo(), 'entries; drop table entries', 'sqlite::memory:');
            Assert::true(false, 'a table name with a semicolon opened');
        } catch (\RuntimeException $e) {
            Assert::contains('underscores', $e->getMessage());
        }
    }],
    ['a ledger keys each source by its own label', static function () use ($good, $pdo): void {
        $ledger = new Ledger();
        $ledger->add(new JsonSource($good));
        $ledger->add(new DbSource($pdo(), 'entries', 'sqlite::memory:'));
        Assert::same(2, $ledger->count());
        Assert::same(['json:rows.json', 'db:sqlite::memory:/entries'], $ledger->labels());
    }],
    ['a ledger returns the records of every source in one list', static function () use ($good, $pdo): void {
        $ledger = new Ledger();
        $ledger->add(new JsonSource($good));
        $ledger->add(new DbSource($pdo(), 'entries', 'sqlite::memory:'));
        Assert::same(4, count($ledger->records()));
    }],
    ['a ledger read reaches every source', static function () use ($good): void {
        $ledger = new Ledger();
        $ledger->add(new JsonSource($good));
        $ledger->read();
        Assert::same(1, $ledger->count());
    }],
    ['a ledger with the same source twice still holds two labels', static function () use ($good): void {
        $ledger = new Ledger();
        $ledger->add(new JsonSource($good));
        $ledger->add(new JsonSource($good));
        Assert::same(1, $ledger->count());
    }],
];
