<?php

namespace App\Console\Commands;

use App\Mail\TenderPublishedMail;
use App\Models\Tender;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AutoPublishTenders extends Command
{
    protected $signature = 'tenders:auto-publish';
    protected $description = 'Publish tenders whose start date has arrived (IST)';

    public function handle(): int
    {
        $nowIST = Carbon::now('Asia/Kolkata');

        $tenders = Tender::where('status', 'draft')
            ->where('auto_publish', true)
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $nowIST->toDateString())
            ->get();

        foreach ($tenders as $tender) {
            $tender->update([
                'status'         => 'published',
                'published_date' => $nowIST,
                'approved_by'    => $tender->created_by,
                'approved_at'    => $nowIST,
            ]);

            $this->notifyVendors($tender);

            $this->info("Published tender #{$tender->tender_number}: {$tender->title}");
        }

        if ($tenders->isEmpty()) {
            $this->info('No tenders to auto-publish.');
        }

        return self::SUCCESS;
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
                \Log::warning("AutoPublish mail failed for vendor {$vendor->id}: " . $e->getMessage());
            }
        }
    }
}
