<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    /**
     * Return a collection of unique customers.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Order::select('buyer_phone', 'buyer_name')
            ->groupBy('buyer_phone', 'buyer_name') // Ensure uniqueness
            ->get();
    }

    /**
     * Define the headings for the Excel file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Buyer Phone',
            'Buyer Name',
        ];
    }

    /**
     * Map the data to include formatting.
     *
     * @param $order
     * @return array
     */
    public function map($order): array
    {
        return [
            " {$order->buyer_phone}", // Add a single quote to force Excel to treat it as text
            $order->buyer_name,
        ];
    }

    /**
     * Apply styles to the worksheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Bold the header row
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
