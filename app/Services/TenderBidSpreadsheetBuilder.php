<?php

namespace App\Services;

use App\Models\Tender;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds a ranked bid-amount summary spreadsheet for a tender, emailed to the Purchase
 * Committee the moment an online tender's bids are opened (see TenderReviewController).
 */
class TenderBidSpreadsheetBuilder
{
    public static function build(Tender $tender): string
    {
        $applications = $tender->applications()
            ->with('vendor')
            ->whereNotNull('bid_amount')
            ->orderBy('bid_amount', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bid Summary');

        $sheet->fromArray(['Rank', 'Vendor', 'Application No.', 'Bid Amount (INR)', 'Status'], null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $row = 2;
        foreach ($applications as $i => $application) {
            $sheet->fromArray([
                $i + 1,
                $application->vendor->company_name ?? 'N/A',
                $application->application_number,
                (float) $application->bid_amount,
                ucfirst($application->status),
            ], null, "A{$row}");
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'bid-summary-' . $tender->tender_number . '-' . now()->format('YmdHis') . '.xlsx';
        $path = storage_path('app/tmp/' . $fileName);
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
