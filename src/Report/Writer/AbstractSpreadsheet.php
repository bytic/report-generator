<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;
use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Definition\Header\Header;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetWriterFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class AbstractSpreadsheet.
 */
abstract class AbstractSpreadsheet extends AbstractWriter implements WriterInterface
{
    /**
     * @var IWriter
     */
    protected $writer = null;

    protected $writerType;

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
     *
     * @param string $name
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function save($name)
    {
        $writer = $this->getWriter();
        $writer->save($name);
    }

    /**
     * @return IWriter|null
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    protected function getWriter()
    {
        if (null === $this->writer) {
            $this->initWriter();
        }

        return $this->writer;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    protected function initWriter()
    {
        $this->writer = SpreadsheetWriterFactory::createWriter($this->getSpreadsheet(), $this->writerType);
    }

    /**
     * Get the populated Spreadsheet instance.
     *
     * @return Spreadsheet
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function getSpreadsheet(): ?Spreadsheet
    {
        if (!$this->spreadsheet) {
            $this->spreadsheet = $this->generateSpreadsheet();
        }

        return $this->spreadsheet;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function generateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $data = $this->report->getData();

        $spreadsheet->getProperties()
            ->setTitle($this->report->getDefinition()->getTitle())
            ->setSubject($this->report->getDefinition()->getTitle());

        $header = $this->report->getHeader();
        $this->addHeader($spreadsheet, $this->report->getHeader());

        foreach ($data as $rowData) {
            $this->addRow($spreadsheet, $rowData, $header);
        }

        $lastCol = $spreadsheet->getActiveSheet()->getHighestColumn();
        $spreadsheet->getActiveSheet()->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->freezePane('A2');

        foreach (range('A', $lastCol) as $col) {
            $spreadsheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Add a row of values to the current worksheet.
     *
     * @param Column[] $header
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function addHeader(Spreadsheet $spreadsheet, Header $header)
    {
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($header as $headerRow) {
            $col = 'A';
            foreach ($headerRow as $column) {
                $sheet->setCellValue($col . $this->currentRow, $column->getTitle());
                ++$col;
            }
            ++$this->currentRow;
        }

        return $this;
    }

    /**
     * Add a row of values to the current worksheet.
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function addRow(Spreadsheet $spreadsheet, DataRow $row, Header $header)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $col = 'A';
        $lastHeaderRow = $header->getLastRow();
        foreach ($lastHeaderRow as $column) {
            $sheet->setCellValue($col . $this->currentRow, $row->getValue($column->getName()));
            ++$col;
        }
        ++$this->currentRow;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    protected function generateResponseContent($response)
    {
        $writer = $this->getWriter();

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', $this->getResponseContentHeader());
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $this->getFileName());
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    abstract protected function getResponseContentHeader(): string;

    abstract protected function getFileExtension(): string;
}
