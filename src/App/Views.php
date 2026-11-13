<?php

declare(strict_types=1);

namespace Raifs\App;

use Raifs\Books\Entry;
use Raifs\Home\Sample;

/**
 * Every page RAIFS prints. The markup is written by hand: no template engine,
 * no escaping library beyond htmlspecialchars, so a reader can follow a figure
 * from the record to the table.
 */
final class Views
{
    public function __construct(private readonly string $base)
    {
    }

    private function url(string $path): string
    {
        $base = '/' . trim($this->base, '/');

        return ($base === '/' ? '' : $base) . $path;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function money(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $whole = intdiv($cents, 100);
        $rest = $cents % 100;
        $units = strrev(implode(',', str_split(strrev((string) $whole), 3)));

        return $sign . $units . '.' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT);
    }

    private function table(array $rows, string $caption = ''): string
    {
        $html = '<table>';
        if ($caption !== '') {
            $html .= '<caption class="stage">' . $this->e($caption) . '</caption>';
        }
        $html .= '<tbody>';
        foreach ($rows as [$label, $value, $class]) {
            $html .= '<tr><th>' . $this->e((string) $label) . '</th>'
                . '<td class="n ' . $this->e((string) $class) . '">' . $this->e((string) $value) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    public function front(Sample $sample): string
    {
        $journal = $sample->journal();
        $balance = $sample->balance();
        $income = $sample->income();
        $period = $income->period();
        $parties = $sample->parties();
        $report = $sample->report();

        $lede = 'RAIFS, the Remote Artificial Intelligence Financial System, is a financial reporting application that a company deploys on its own server.';
        $what = 'RAIFS reads records in the shape that APIX defines. The application connects to a database at any address a company gives it, and to JSON files in the APIX shape. From what it reads, it builds the balance sheet, the income statement, the related-party transactions, and the annual report.';
        $steps = [
            'A company deploys RAIFS on its own server, and the application loads one page.',
            'The company gives RAIFS a database address, or JSON files in the APIX shape.',
            'RAIFS reads the records, builds the four reports, and the company holds the annual report.',
        ];

        $assetRows = [];
        foreach ($balance->byKind('asset') as $account => $cents) {
            $assetRows[] = [$account, $this->money($cents), ''];
        }
        $assetRows[] = ['Total assets', $this->money($balance->total('asset')), 'ok'];

        $liabilityRows = [];
        foreach ($balance->byKind('liability') as $account => $cents) {
            $liabilityRows[] = [$account, $this->money($cents), ''];
        }
        $liabilityRows[] = ['Total liabilities', $this->money($balance->total('liability')), 'ok'];

        $incomeRows = [
            ['Revenue', $this->money($income->revenue($period)), ''],
            ['Expenses', $this->money($income->expenses($period)), ''],
            ['Result', $this->money($income->result($period)), 'ok'],
        ];

        $partyRows = [];
        foreach ($parties->totals() as $party => $cents) {
            $partyRows[] = [$party, $this->money($cents), ''];
        }
        $partyRows[] = ['Lines with no named party', (string) $parties->unnamed(), ''];

        $sourceRows = [];
        foreach ($report->sources() as $label) {
            $sourceRows[] = [$label, 'read', 'ok'];
        }

        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>RAIFS</title>'
            . '<link rel="stylesheet" href="' . $this->e($this->url('/assets/site.css')) . '">'
            . '</head><body><main>';

        $html .= '<h1>RAIFS</h1>';
        $html .= '<p class="stage">' . $this->e($lede) . '</p>';
        $html .= '<p>' . $this->e($what) . '</p>';
        $html .= '<h2>How a company runs it</h2><ol class="steps">';
        foreach ($steps as $step) {
            $html .= '<li>' . $this->e($step) . '</li>';
        }
        $html .= '</ol>';

        $html .= '<h2>The company on this page</h2>';
        $html .= '<p>' . $this->e($report->title()) . '. ' . $journal->count()
            . ' lines across ' . count($journal->periods()) . ' periods. '
            . $report->total('Assets') . ' cents of assets sit on the sheet.</p>';

        $html .= '<h2>Balance sheet</h2>';
        $html .= $this->table($assetRows, 'Assets');
        $html .= $this->table($liabilityRows, 'Liabilities');
        $html .= '<p>Equity reads ' . $this->e($this->money($balance->equity())) . '. ';
        $html .= $balance->balances()
            ? '<span class="ok">The sheet balances.</span>'
            : '<span class="badge off">The sheet does not balance.</span>';
        $html .= '</p>';

        $html .= '<h2>Income statement</h2>';
        $html .= '<p class="stage">Period ' . $this->e($period) . '.</p>';
        $html .= $this->table($incomeRows);

        $html .= '<h2>Related parties</h2>';
        $html .= '<p class="stage">Period 2026-05. The company named these parties, and the '
            . 'section reads the same lines as the statements above.</p>';
        $html .= $this->table($partyRows);

        $html .= '<h2>Sources read</h2>';
        $html .= $this->table($sourceRows);

        $html .= '<h2>The lines behind it</h2>';
        $html .= '<table><thead><tr><th>Day</th><th>Account</th><th>Party</th>'
            . '<th class="n">Debit</th><th class="n">Credit</th></tr></thead><tbody>';
        foreach ($journal->entries() as $entry) {
            $debit = $entry->side === Entry::DEBIT ? $this->money($entry->cents) : '';
            $credit = $entry->side === Entry::CREDIT ? $this->money($entry->cents) : '';
            $html .= '<tr><td>' . $this->e($entry->day) . '</td><td>' . $this->e($entry->account)
                . '</td><td>' . $this->e($entry->counterparty) . '</td>'
                . '<td class="n">' . $this->e($debit) . '</td>'
                . '<td class="n">' . $this->e($credit) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<h2>Sections in the annual report</h2><ul>';
        foreach ($report->headings() as $heading) {
            $html .= '<li>' . $this->e($heading) . ' ' . $this->e($this->money($report->total($heading))) . '</li>';
        }
        $html .= '</ul>';

        $html .= '<p class="stage">Figures on this page belong to the sample company and carry '
            . 'a fixed set of lines.</p>';
        $html .= '</main></body></html>';

        return $html;
    }

    public function notFound(string $path): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<title>Not found</title></head><body><main><h1>Not found</h1>'
            . '<p>' . $this->e($path) . ' is not a page on this site.</p>'
            . '<p><a href="' . $this->e($this->url('/')) . '">Back to the front page</a></p>'
            . '</main></body></html>';
    }
}
