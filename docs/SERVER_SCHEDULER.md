# Auto-publish on the server

A tender with **Auto-publish on the Start Date** ticked is saved as a `draft` with
`auto_publish = 1`. Nothing in a web request publishes it. It is published by a command:

```
php artisan tenders:auto-publish
```

That command only runs if something calls it. On the server that something is **cron**, and
if the cron entry is missing the tender stays a draft for ever — which is exactly what
"auto-publish on a future date does not work on the server" looks like. The application code
is not involved.

## 1. Check whether it is the cron

Open cPanel → **Terminal** (or SSH) and run, from the application directory:

```bash
/usr/local/bin/ea-php82 artisan tenders:auto-publish --dry-run
```

`--dry-run` changes nothing and sends no mail. It lists every tender that *should* already
have been published.

| What it prints | What it means |
|---|---|
| `would publish #TDR-…` | The tender is due and the code finds it. Cron is the problem → step 2 |
| `nothing due.` | No draft has `auto_publish = 1` with a start date on or before today → step 3 |
| `cannot read tenders: … Unknown column 'auto_publish'` | Migrations have not been run on the server → `php artisan migrate` |

Then check the log:

```bash
tail -20 storage/logs/auto-publish.log
```

Every run appends a timestamped line, including the runs that find nothing. **An empty or
missing file means the command has never run on this server** — confirming the cron.

## 2. Add the cron entry

cPanel → **Cron Jobs** → Add New Cron Job, set to **Once Per Minute** (`* * * * *`):

```
* * * * * /usr/local/bin/ea-php82 /home/CPANEL_USER/APP_PATH/artisan schedule:run >/dev/null 2>&1
```

Replace `/home/CPANEL_USER/APP_PATH` with the real path — run `pwd` in Terminal from the
application directory to get it.

Two things that quietly break this line:

- **The PHP binary.** A bare `php` on cPanel often resolves to an old version. Laravel 12
  needs 8.2+, and on an older binary cron fails every minute with nothing to show for it.
  Name `ea-php82` explicitly, and check it with `/usr/local/bin/ea-php82 -v`.
- **`proc_open` disabled.** `schedule:run` starts each task as a sub-process, so a host that
  blocks `proc_open` runs the cron, finds the task due, and then silently fails to start it.
  Check with:

  ```bash
  /usr/local/bin/ea-php82 -r "var_dump(function_exists('proc_open'));"
  ```

  If that is `false`, skip the scheduler and call the command straight from cron instead —
  one entry, every ten minutes:

  ```
  */10 * * * * /usr/local/bin/ea-php82 /home/CPANEL_USER/APP_PATH/artisan tenders:auto-publish >> /home/CPANEL_USER/APP_PATH/storage/logs/auto-publish.log 2>&1
  ```

  Running it twice cannot publish a tender twice — once the status is `published` the query
  no longer matches it.

Give it a few minutes, then `tail storage/logs/auto-publish.log` again. Lines appearing every
ten minutes means it is working.

## 3. If nothing is due but a tender looks scheduled

All four of these have to hold, or the tender is skipped:

| Condition | Where it comes from |
|---|---|
| `status = 'draft'` | A tender already published, closed or cancelled is never touched again |
| `auto_publish = 1` | The **Auto-publish on the Start Date** checkbox at creation, or on an edit **while still a draft** — editing a published tender cannot set it |
| `start_date` is set | Required on the form |
| `start_date <= today` in IST | The date is compared in `Asia/Kolkata`, not UTC |

Check one tender directly:

```bash
/usr/local/bin/ea-php82 artisan tinker --execute="\$t=App\Models\Tender::where('tender_number','TDR-2026-0001')->first(); echo \$t->status.' auto_publish='.(int)\$t->auto_publish.' start='.\$t->start_date;"
```

## What publishing does

`status` becomes `published`, `published_date` and `approved_at` are set to the moment of the
run, and `approved_by` is set to whoever created the tender — an auto-published tender
approves itself. If the tender has email notifications on, the vendors are mailed: the named
vendors if specific ones were chosen, otherwise every approved vendor in the tender's
categories. A mail failure is logged per vendor and does not stop the publish.

## Locally (XAMPP on Windows)

`run-scheduler-hidden.vbs` in the project root runs `schedule:run` once, hidden. Point a
Windows Task Scheduler task at it and repeat it every minute. The script sets its working
directory from its own location, so **Start in** does not need to be filled in.
