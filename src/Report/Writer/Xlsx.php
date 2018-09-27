<?php

namespace ByTIC\ReportGenerator\Report\Writer;

use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetWriterFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class Xlsx
 * @package ByTIC\ReportGenerator\Report\Writer
 */
class Xlsx extends AbstractWriter implements WriterInterface
{
    /**
     * @var IWriter
     */
    protected $writer = null;

    /**
     * @var Spreadsheet
     */
    protected $spreadsheet = null;

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
        $writer = $this->getWriter();
        $writer->save($name);
    }

    /**
     * @return null|IWriter
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    protected function getWriter()
    {
        if ($this->writer === null) {
            $this->initWriter();
        }
        return $this->writer;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    protected function initWriter()
    {
        $this->writer = SpreadsheetWriterFactory::createWriter($this->getSpreadsheet(), 'Xlsx');
    }

    /**
     * Get the populated Spreadsheet instance.
     *
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function getSpreadsheet()
    {
        if (!$this->writer) {
            $spreadsheet = new Spreadsheet();

            $spreadsheet->getProperties()
                ->setTitle($this->report->getDefinition()->getTitle())
                ->setSubject($this->report->getDefinition()->getTitle());

            $header = $this->report->getHeader();
            $this->addHeader($spreadsheet, $this->report->getHeader());

            foreach ($this->report->getData() as $rowData) {
                $this->addRow($spreadsheet, $rowData, $header);
            }

            $lastCol = $spreadsheet->getActiveSheet()->getHighestColumn();
            $spreadsheet->getActiveSheet()->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->freezePane('A2');

            foreach (range('A', $lastCol) as $col) {
                $spreadsheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
            }
            $this->writer = $spreadsheet;
        }
        return $this->writer;
    }

    /**
     * Add a row of values to the current worksheet.
     *
     * @param Spreadsheet $spreadsheet
     * @param Column[] $header
     *
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function addHeader(Spreadsheet $spreadsheet, array $header)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $col = 'A';
        foreach ($header as $column) {
            $sheet->setCellValue($col . $this->currentRow, $column->getTitle());
            $col++;
        }
        $this->currentRow++;
        return $this;
    }

    /**
     * Add a row of values to the current worksheet.
     *
     * @param Spreadsheet $spreadsheet
     * @param array $row
     * @param Column[] $header
     *
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function addRow(Spreadsheet $spreadsheet, array $row, array $header)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $col = 'A';
        foreach ($header as $column) {
            if (isset($row[$column->getName()])) {
                $sheet->setCellValue($col . $this->currentRow, $row[$column->getName()]);
            }
            $col++;
        }
        $this->currentRow++;
        return $this;
    }

    /**
     * @inheritdoc
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    protected function generateResponseContent($response)
    {
        $writer = $this->getWriter();

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8'
        );
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    /**
     * @return string
     */
    protected function getFileExtension()
    {
        return '.xlsx';
    }
}
