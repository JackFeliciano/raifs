<?php

declare(strict_types=1);

namespace Raifs\Tests;

use Raifs\Books\BalanceSheet;
use Raifs\Books\Entry;
use Raifs\Books\IncomeStatement;
use Raifs\Books\Journal;
use Raifs\Books\RelatedParties;

$book = static function (): Journal {
    $journal = new Journal();
    $journal->post(new Entry('2026-01-05', 'Cash', 100000, Entry::DEBIT, 'Northwind', 'Capital'));
    $journal->post(new Entry('2026-01-05', 'Capital', 100000, Entry::CREDIT, 'Northwind', 'Capital'));
    $journal->post(new Entry('2026-02-02', 'Sales', 60000, Entry::CREDIT, 'Halloran', 'Invoice'));
    $journal->post(new Entry('2026-03-11', 'Wages', 25000, Entry::DEBIT, '', 'Payroll'));

    return $journal;
};

$kinds = [
    'Cash' => 'asset',
    'Capital' => 'equity',
    'Sales' => 'revenue',
    'Wages' => 'expense',
];

return [
    ['an entry keeps its amount in whole cents', static function (): void {
        $entry = new Entry('2026-01-05', 'Cash', 1234, Entry::DEBIT);
        Assert::same(1234, $entry->cents);
        Assert::same('2026-01', $entry->period());
    }],
    ['an entry refuses a negative amount', static function (): void {
        try {
            new Entry('2026-01-05', 'Cash', -5, Entry::DEBIT);
            Assert::true(false, 'a negative amount landed');
        } catch (\RuntimeException $e) {
            Assert::contains('positive', $e->getMessage());
        }
    }],
    ['an entry refuses a side it does not know', static function (): void {
        try {
            new Entry('2026-01-05', 'Cash', 5, 'neither');
            Assert::true(false, 'an unknown side landed');
        } catch (\RuntimeException $e) {
            Assert::contains('debit or a credit', $e->getMessage());
        }
    }],
    ['an entry refuses a day outside the year-month-day shape', static function (): void {
        try {
            new Entry('05/01/2026', 'Cash', 5, Entry::DEBIT);
            Assert::true(false, 'a loose day landed');
        } catch (\RuntimeException $e) {
            Assert::contains('YYYY-MM-DD', $e->getMessage());
        }
    }],
    ['the journal lists its periods in order', static function () use ($book): void {
        Assert::same(['2026-01', '2026-02', '2026-03'], $book()->periods());
    }],
    ['a period holds only its own lines', static function () use ($book): void {
        Assert::same(2, count($book()->in('2026-01')));
        Assert::same(0, count($book()->in('2025-12')));
    }],
    ['an account holds only its own lines', static function () use ($book): void {
        Assert::same(1, count($book()->forAccount('Wages')));
    }],
    ['a balance sheet nets a debit against a credit on the same account', static function () use ($book, $kinds): void {
        $journal = $book();
        $journal->post(new Entry('2026-04-01', 'Cash', 40000, Entry::DEBIT, '', 'Receipt'));
        $journal->post(new Entry('2026-04-01', 'Cash', 15000, Entry::CREDIT, '', 'Payment'));
        $sheet = new BalanceSheet($journal, $kinds);
        Assert::same(125000, $sheet->byKind('asset')['Cash']);
    }],
    ['equity reads the difference between assets and liabilities', static function () use ($book, $kinds): void {
        $sheet = new BalanceSheet($book(), $kinds);
        Assert::same(100000, $sheet->total('asset'));
        Assert::same(0, $sheet->total('liability'));
        Assert::same(100000, $sheet->equity());
    }],
    ['a sheet balances when assets equal liabilities plus equity', static function () use ($book, $kinds): void {
        Assert::true((new BalanceSheet($book(), $kinds))->balances(), 'a balanced sheet reads unbalanced');
    }],
    ['a sheet reads unbalanced when a line carries no counter-entry', static function () use ($book, $kinds): void {
        $journal = $book();
        $journal->post(new Entry('2026-05-01', 'Cash', 7000, Entry::DEBIT));
        Assert::same(false, (new BalanceSheet($journal, $kinds))->balances());
    }],
    ['an income statement reads revenue and expenses for its period', static function () use ($book, $kinds): void {
        $income = new IncomeStatement($book(), $kinds);
        Assert::same('2026-03', $income->period());
        Assert::same(0, $income->revenue('2026-03'));
        Assert::same(25000, $income->expenses('2026-03'));
        Assert::same(-25000, $income->result('2026-03'));
    }],
    ['an income statement reads an earlier period the same way', static function () use ($book, $kinds): void {
        $income = new IncomeStatement($book(), $kinds);
        Assert::same(60000, $income->revenue('2026-02'));
        Assert::same(60000, $income->result('2026-02'));
    }],
    ['a related-party section totals what the books moved with each party', static function () use ($book): void {
        $parties = new RelatedParties($book(), ['Northwind', 'Halloran'], '2026-02');
        $totals = $parties->totals();
        Assert::same(0, $totals['Northwind']);
        Assert::same(60000, $totals['Halloran']);
        Assert::same(0, $parties->unnamed());
    }],
    ['a related-party section counts the lines with no named party', static function () use ($book): void {
        $parties = new RelatedParties($book(), ['Northwind'], '2026-03');
        Assert::same(1, $parties->unnamed());
    }],
];
