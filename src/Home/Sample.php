<?php

declare(strict_types=1);

namespace Raifs\Home;

use Raifs\Books\AnnualReport;
use Raifs\Books\BalanceSheet;
use Raifs\Books\Entry;
use Raifs\Books\IncomeStatement;
use Raifs\Books\Journal;
use Raifs\Books\RelatedParties;
use Raifs\Connect\Ledger;

/**
 * A small company with two periods of books, so the page shows a balance sheet,
 * an income statement and a related-party section that all come from the same
 * lines. The figures are invented and they belong to this demo only.
 */
final class Sample
{
    private Journal $journal;
    /** @var array<string, string> */
    private array $kinds;

    public function __construct()
    {
        $this->journal = new Journal();
        $this->kinds = [
            'Cash' => 'asset',
            'Receivables' => 'asset',
            'Payables' => 'liability',
            'Loan' => 'liability',
            'Capital' => 'equity',
            'Sales' => 'revenue',
            'Wages' => 'expense',
            'Freight' => 'expense',
        ];
        $lines = [
            ['2026-01-08', 'Cash', 900000, Entry::DEBIT, 'Northwind', 'Opening capital'],
            ['2026-01-08', 'Capital', 900000, Entry::CREDIT, 'Northwind', 'Opening capital'],
            ['2026-02-14', 'Cash', 480000, Entry::DEBIT, 'Northwind', 'Two invoices settled'],
            ['2026-02-14', 'Sales', 480000, Entry::CREDIT, 'Northwind', 'Two invoices settled'],
            ['2026-03-03', 'Payables', 150000, Entry::CREDIT, 'Halloran', 'Stock bought on terms'],
            ['2026-03-03', 'Freight', 150000, Entry::DEBIT, 'Halloran', 'Freight on the stock'],
            ['2026-04-21', 'Wages', 210000, Entry::DEBIT, '', 'Monthly payroll'],
            ['2026-04-21', 'Cash', 210000, Entry::CREDIT, '', 'Monthly payroll'],
            ['2026-05-06', 'Receivables', 320000, Entry::DEBIT, 'Halloran', 'Goods delivered'],
            ['2026-05-06', 'Sales', 320000, Entry::CREDIT, 'Halloran', 'Goods delivered'],
            ['2026-06-18', 'Loan', 500000, Entry::CREDIT, 'Meridian Bank', 'Term loan drawn'],
            ['2026-06-18', 'Cash', 500000, Entry::DEBIT, 'Meridian Bank', 'Term loan drawn'],
            ['2026-07-02', 'Wages', 215000, Entry::DEBIT, '', 'Monthly payroll'],
            ['2026-07-02', 'Cash', 215000, Entry::CREDIT, '', 'Monthly payroll'],
            ['2026-08-19', 'Cash', 140000, Entry::DEBIT, 'Northwind', 'Part payment'],
            ['2026-08-19', 'Receivables', 140000, Entry::CREDIT, 'Northwind', 'Part payment'],
        ];
        foreach ($lines as [$day, $account, $cents, $side, $party, $note]) {
            $this->journal->post(new Entry($day, $account, $cents, $side, $party, $note));
        }
    }

    public function journal(): Journal
    {
        return $this->journal;
    }

    public function balance(): BalanceSheet
    {
        return new BalanceSheet($this->journal, $this->kinds);
    }

    public function income(): IncomeStatement
    {
        return new IncomeStatement($this->journal, $this->kinds);
    }

    public function parties(): RelatedParties
    {
        return new RelatedParties($this->journal, ['Northwind', 'Halloran', 'Meridian Bank'], '2026-05');
    }

    public function report(): AnnualReport
    {
        $balance = $this->balance();
        $income = $this->income();
        $period = $income->period();

        return new AnnualReport(
            'Sample company, period ' . $period,
            [
                'Assets' => $balance->byKind('asset'),
                'Liabilities' => $balance->byKind('liability'),
                'Equity' => ['Equity' => $balance->equity()],
                'Revenue' => ['Revenue' => $income->revenue($period)],
                'Expenses' => ['Expenses' => $income->expenses($period)],
            ],
            ['db:sample/demo/entries'],
        );
    }

    public function ledger(): Ledger
    {
        return new Ledger();
    }
}
