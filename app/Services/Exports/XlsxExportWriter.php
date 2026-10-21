<?php

namespace App\Services\Exports;

use App\Exports\OrdersExport;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Streams an export to an XLSX file row by row so memory stays flat regardless of row count
 * (PhpSpreadsheet keeps every cell in memory and exhausted 128 MB on the full sell log).
 */
class XlsxExportWriter
{
    private const CHUNK_SIZE = 1000;

    /**
     * @return int Number of data rows written.
     */
    public function write(OrdersExport $export, string $absolutePath): int
    {
        $writer = new Writer();
        $writer->openToFile($absolutePath);
        $rows = 0;

        try {
            $writer->addRow(Row::fromValues($export->headings(), (new Style())->setFontBold()));

            $export->query()
                ->lazyById(self::CHUNK_SIZE, 'orders.id', 'id')
                ->each(function ($order) use ($writer, $export, &$rows) {
                    $writer->addRow(Row::fromValues($export->map($order)));
                    $rows++;
                });
        } finally {
            $writer->close();
        }

        return $rows;
    }
}
