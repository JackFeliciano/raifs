<?php

declare(strict_types=1);

namespace Raifs\Tests;

use Raifs\Books\AnnualReport;
use Raifs\Books\Entry;
use Raifs\Home\Sample;

return [
    ['the sample company posts sixteen lines across eight periods', static function (): void {
        $sample = new Sample();
        Assert::same(16, $sample->journal()->count());
        Assert::same(8, count($sample->journal()->periods()));
    }],
    ['a counter-entry moves the sample sheet off balance', static function (): void {
        $sample = new Sample();
        $sample->journal()->post(new Entry('2026-05-06', 'Receivables', 320000, Entry::CREDIT, 'Halloran', 'Goods delivered'));
        Assert::same(false, $sample->balance()->balances(), 'the sheet read balanced after the counter-entry landed');
    }],
    ['the sample carries cash and payables on the sheet', static function (): void {
        $sample = new Sample();
        $sheet = $sample->balance();
        $assets = $sheet->byKind('asset');
        Assert::same(1595000, $assets['Cash']);
        Assert::same(180000, $assets['Receivables']);
        Assert::same(150000, $sheet->byKind('liability')['Payables']);
    }],
    ['the sample income statement reads its latest period', static function (): void {
        $sample = new Sample();
        $income = $sample->income();
        Assert::same('2026-08', $income->period());
        Assert::same(0, $income->revenue('2026-08'));
        Assert::same(0, $income->result('2026-08'));
    }],
    ['the sample reads revenue in the months that carried it', static function (): void {
        $sample = new Sample();
        $income = $sample->income();
        Assert::same(480000, $income->revenue('2026-02'));
        Assert::same(320000, $income->revenue('2026-05'));
        Assert::same(800000, $income->revenue('2026-02') + $income->revenue('2026-05'));
    }],
    ['the sample related-party section names three parties for May', static function (): void {
        $sample = new Sample();
        $totals = $sample->parties()->totals();
        Assert::same(['Northwind', 'Halloran', 'Meridian Bank'], array_keys($totals));
        Assert::same(0, $totals['Northwind']);
        Assert::same(640000, $totals['Halloran']);
        Assert::same(0, $totals['Meridian Bank']);
        Assert::same(0, $sample->parties()->unnamed());
    }],
    ['the annual report carries its own sources', static function (): void {
        $sample = new Sample();
        $report = $sample->report();
        Assert::same(['db:sample/demo/entries'], $report->sources());
        Assert::same('Sample company, period 2026-08', $report->title());
    }],
    ['the annual report lists five sections in reading order', static function (): void {
        $sample = new Sample();
        $report = $sample->report();
        Assert::same(['Assets', 'Liabilities', 'Equity', 'Revenue', 'Expenses'], $report->headings());
    }],
    ['an annual report totals a section it holds', static function (): void {
        $report = new AnnualReport('Test', ['Assets' => ['Cash' => 100, 'Bank' => 250]], []);
        Assert::same(350, $report->total('Assets'));
        Assert::same(0, $report->total('Nothing'));
    }],
];
