# RAIFS

A company deploys RAIFS, the Remote Artificial Intelligence Financial System, on its own server and gets a clear view of what it owes and what it is owed.

RAIFS tracks what a company owes and what it is owed, issues invoices, records the payments that settle them, and holds payables and receivables, each with an ageing view. It follows cash flow in and out over any period the company asks for. It reads records from a database at any address, or from JSON files in the APIX shape.

## How a company runs it

1. A company deploys RAIFS on its own server, and the application loads one page.
2. The company gives RAIFS a database address, or JSON files in the APIX shape.
3. RAIFS reads the records, builds the four reports, and the company holds the annual report.

## What ships in the repository

- `src/Connect` holds the connector layer: one database driver that reads a table through a
  PDO handle, and one JSON reader that takes a list of records in the APIX shape.
- `src/Books` holds the journal and the four reports: the balance sheet, the income
  statement, the related-party section, and the annual report.
- `showroom` holds the front controller and the stylesheet. `RAIFS_BASE` sets the path
  prefix nginx serves the site under.
- `tests` holds the suite. It runs on PHP alone, with no Composer and no vendor directory.

## How to run it

```
export RAIFS_BASE=/raifs/
php -S 127.0.0.1:8120 -t showroom showroom/index.php
```

Then open `http://127.0.0.1:8120/`. The status route answers at `/raifs/status` with the
line count, the latest period and the asset total of the sample company.

## Tests

```
php tests/run.php
```

The suite needs PHP 8.2 or later and the pdo_sqlite extension. It builds its own fixtures in
`tests/fixtures` and reads nothing outside the repository.

## Licence

MIT. See `LICENSE`.
