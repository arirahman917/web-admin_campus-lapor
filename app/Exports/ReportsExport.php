<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportsExport implements WithMultipleSheets
{
    public function __construct(
        private readonly string $title,
        private readonly array $rows,
        private readonly array $stats
    ) {
    }

    public function sheets(): array
    {
        return [
            new DataLaporanSheet($this->title, $this->rows, $this->stats),
            new StatistikSheet($this->stats),
        ];
    }
}

class DataLaporanSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    private const TABLE_START_ROW = 7;

    public function __construct(
        private readonly string $title,
        private readonly array $rows,
        private readonly array $stats
    ) {
    }

    public function title(): string
    {
        return 'Data Laporan';
    }

    public function array(): array
    {
        $data = [
            ['Campus Lapor', '', '', '', '', '', '', ''],
            [$this->title, '', '', '', '', '', '', ''],
            ['Total Laporan', $this->stats['total_laporan'], 'Barang Hilang', $this->stats['total_barang_hilang'], 'Fasilitas Rusak', $this->stats['total_fasilitas_rusak'], '', ''],
            ['Total Selesai', $this->stats['total_selesai'], 'Total Pending', $this->stats['total_pending'], 'Tanggal Export', $this->stats['printed_at'], '', ''],
            ['', '', '', '', '', '', '', ''],
            ['ID', 'Nama Pelapor', 'Role', 'Jenis Laporan', 'Judul', 'Lokasi', 'Status', 'Tanggal'],
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                $row['id'],
                $row['nama_pelapor'],
                $row['role'],
                $row['jenis_laporan'],
                $row['judul'],
                $row['lokasi'],
                $row['status'],
                $row['tanggal'],
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '7C3AED']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '334155']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            self::TABLE_START_ROW - 1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $tableHeaderRow = self::TABLE_START_ROW - 1;

                $sheet->freezePane('A'.self::TABLE_START_ROW);
                $sheet->setAutoFilter("A{$tableHeaderRow}:{$highestColumn}{$highestRow}");

                $sheet->getStyle("A3:H4")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F3FF']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDD6FE']]],
                ]);

                $sheet->getStyle("A{$tableHeaderRow}:{$highestColumn}{$highestRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                for ($row = self::TABLE_START_ROW; $row <= $highestRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setRGB('F5F3FF');
                    }
                }
            },
        ];
    }
}

class StatistikSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private readonly array $stats)
    {
    }

    public function title(): string
    {
        return 'Statistik';
    }

    public function array(): array
    {
        return [
            ['Campus Lapor - Statistik Export'],
            [''],
            ['Metrik', 'Jumlah'],
            ['Total laporan', $this->stats['total_laporan']],
            ['Total barang hilang', $this->stats['total_barang_hilang']],
            ['Total fasilitas rusak', $this->stats['total_fasilitas_rusak']],
            ['Total selesai', $this->stats['total_selesai']],
            ['Total pending', $this->stats['total_pending']],
            ['Tanggal export', $this->stats['printed_at']],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '7C3AED']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            ],
            'A3:B9' => [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ],
        ];
    }
}
