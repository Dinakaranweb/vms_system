<?php

namespace App\Console\Commands;

use App\Mail\TenderPublishedMail;
use App\Models\Tender;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoPublishTenders extends Command
{
    protected $signature = 'tenders:auto-publish
                            {--dry-run : Report what would be published without changing anything}';

    protected $description = 'Publish tenders whose start date has arrived (IST)';

    public function handle(): int
    {
        $nowIST = Carbon::now('Asia/Kolkata');
        $dry    = (bool) $this->option('dry-run');

        // Every run says so, so an empty log file means cron never fired.
        $this->line('[' . $nowIST->format('Y-m-d H:i:s') . ' IST] tenders:auto-publish'
            . ($dry ? ' (dry run)' : ''));

        try {
            $tenders = Tender::where('status', 'draft')
                ->where('auto_publish', true)
                ->whereNotNull('start_date')
                ->whereDate('start_date', '<=', $nowIST->toDateString())
                ->get();
        } catch (\Throwable $e) {
            $this->error('  cannot read tenders: ' . $e->getMessage());
            Log::error('tenders:auto-publish query failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($tenders->isEmpty()) {
            $this->line('  nothing due.');
            return self::SUCCESS;
        }

        $published = 0;
        $failed    = 0;

        foreach ($tenders as $tender) {
            $due = $tender->start_date ? $tender->start_date->toDateString() : '?';

            if ($dry) {
                $this->line("  would publish #{$tender->tender_number} (start date {$due}): {$tender->title}");
                continue;
            }

            try {
                $tender->update([
                    'status'         => 'published',
                    'published_date' => $nowIST,
                    'approved_by'    => $tender->created_by,
                    'approved_at'    => $nowIST,
                ]);

                $this->notifyVendors($tender);

                $published++;
                $this->info("  published #{$tender->tender_number} (start date {$due}): {$tender->title}");
                Log::info("Auto-published tender #{$tender->tender_number} (start date {$due})");
            } catch (\Throwable $e) {
                // One bad tender must not stop the rest of the batch.
                $failed++;
                $this->error("  failed #{$tender->tender_number}: " . $e->getMessage());
                Log::error("tenders:auto-publish failed for tender {$tender->id}: " . $e->getMessage());
            }
        }

        if (!$dry) {
            $this->line("  {$published} published, {$failed} failed, "
                . $tenders->count() . ' due.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function notifyVendors(Tender $tender): void
    {
        if (!$tender->email_notifications_enabled) return;

        $specificIds = array_filter(
            array_map('intval', (array) ($tender->notify_specific_vendors ?? []))
        );

        if (!empty($specificIds)) {
            $vendors = Vendor::whereIn('id', $specificIds)
                ->where('status', 'approved')
                ->whereNotNull('email')
                ->get();
        } else {
            $tender->loadMissing('categories.vendors');
            $seen    = collect();
            $vendors = collect();
            foreach ($tender->categories as $category) {
                foreach ($category->vendors as $vendor) {
                    if (!$seen->contains($vendor->id)) {
                        $seen->push($vendor->id);
                        if ($vendor->email && $vendor->status === 'approved') {
                            $vendors->push($vendor);
                        }
                    }
                }
            }
        }

        foreach ($vendors as $vendor) {
            try {
                Mail::to($vendor->email)->send(new TenderPublishedMail($tender, $vendor));
            } catch (\Exception $e) {
                Log::warning("AutoPublish mail failed for vendor {$vendor->id}: " . $e->getMessage());
            }
        }
    }
}
