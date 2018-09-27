<?php

namespace ByTIC\ReportGenerator\Report\Writer;

use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetWriterFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Class Xlsx
 * @package ByTIC\ReportGenerator\Report\Writer
 */
class Xlsx extends AbstractWriter implements WriterInterface
{
    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;
    /**
     * Current row number in report.
     *
     * @var int
     */
    protected $currentRow = 1;

    /**
     * Save to file.
     * @param string $name
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function save($name)
    {
        $writer = SpreadsheetWriterFactory::createWriter($this->getSpreadsheet(), 'Xlsx');
        $writer->save($name);
    }

    /**
     * Get the populated Spreadsheet instance.
     *
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function getSpreadsheet()
    {
        if (!$this->spreadsheet) {
            $spreadsheet = new Spreadsheet();

            $spreadsheet->getProperties()
                ->setTitle($this->report->getDefinition()->getTitle())
                ->setSubject($this->report->getDefinition()->getTitle());

            $this->addRow($spreadsheet, $this->report->getColumnDisplayNames());

            foreach ($this->report->getAllRows() as $rowData) {
                $this->addRow($spreadsheet, $rowData);
            }

            $lastCol = $spreadsheet->getActiveSheet()->getHighestColumn();
            $spreadsheet->getActiveSheet()->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

            foreach (range('A', $lastCol) as $col) {
                $spreadsheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
            }
            $this->spreadsheet = $spreadsheet;
        }
        return $this->spreadsheet;
    }

    /**
     * Add a row of values to the current worksheet.
     *
     * @param Spreadsheet $spreadsheet
     * @param array $row
     *
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function addRow(Spreadsheet $spreadsheet, array $row)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $col = 'A';
        foreach ($row as $value) {
            $sheet->setCellValue($col . $this->currentRow, $value);
            $col++;
        }
        $this->currentRow++;
        return $this;
    }
}
