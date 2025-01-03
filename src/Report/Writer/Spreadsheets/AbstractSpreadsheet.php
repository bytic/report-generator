<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer\Spreadsheets;

use ByTIC\ReportGenerator\Report\DataProvider\DataRows\DataRow;
use ByTIC\ReportGenerator\Report\Definition\Columns\Column;
use ByTIC\ReportGenerator\Report\Definition\Header\Header;
use ByTIC\ReportGenerator\Report\Writer\AbstractWriter;
use ByTIC\ReportGenerator\Report\Writer\WriterInterface;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetWriterFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\HttpFoundation\Response;
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
        $this->writer = SpreadsheetWriterFactory
            ::createWriter(
                $this->getSpreadsheet(),
                $this->writerType
            );
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

        $title = $this->report->getDefinition()->getTitle();
        $spreadsheet->getProperties()
            ->setTitle($title)
            ->setSubject($title);

        $this->generateWorksheets($spreadsheet);
        $this->styleWorksheets($spreadsheet);

        return $spreadsheet;
    }

    protected function generateWorksheets(Spreadsheet $spreadsheet)
    {
        $this->generateWorksheetHeaders($spreadsheet);
        $this->generateWorksheetData($spreadsheet);
    }

    protected function styleWorksheets(Spreadsheet $spreadsheet): void
    {
        $sheets = $spreadsheet->getAllSheets();
        foreach ($sheets as $sheet) {
            $this->styleWorksheet($sheet);
        }
    }

    protected function styleWorksheet(Worksheet $spreadsheet): void
    {
        $lastCol = $spreadsheet->getHighestColumn();
        $spreadsheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $spreadsheet->freezePane('A2');

        foreach (range('A', $lastCol) as $col) {
            $spreadsheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function generateWorksheetHeaders(Spreadsheet $spreadsheet)
    {
        $definition = $this->report->getDefinition();
        $headers = $definition->getHeaders();
        foreach ($headers as $key => $header) {
            $sheet = $this->getSheetFromKey($spreadsheet, $key);
            $this->addHeader($sheet, $header);
        }
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
    protected function addHeader(Worksheet $sheet, Header $header): static
    {
        $currentRow = $sheet->getHighestRow();
        foreach ($header as $headerRow) {
            $col = 'A';
            foreach ($headerRow as $column) {
                $sheet->setCellValue($col . $currentRow, $column->getTitle());
                ++$col;
            }
        }

        return $this;
    }

    protected function generateWorksheetData(Spreadsheet $spreadsheet)
    {
        $data = $this->report->getData();

        foreach ($data as $rowData) {
            /** @var DataRow $rowData */
            $chapter = $rowData->getChapter();
            $key = $chapter?->getName();
            $header = $this->report->getHeader($key);
            $sheet = $this->getSheetFromKey($spreadsheet, $key);
            $this->addRow($sheet, $rowData, $header);
        }
    }

    /**
     * Add a row of values to the current worksheet.
     *
     * @return $this
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function addRow(Worksheet $sheet, DataRow $row, Header $header)
    {
        $currentRow = $sheet->getHighestRow() + 1;
        $col = 'A';
        $lastHeaderRow = $header->getLastRow();
        foreach ($lastHeaderRow as $column) {
            $sheet->setCellValue($col . $currentRow, $row->getValue($column->getName()));
            ++$col;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    protected function generateResponseContent($response): Response
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

    /**
     * @param Spreadsheet $spreadsheet
     * @param int|string|null $key
     * @return Worksheet
     */
    protected function getSheetFromKey(Spreadsheet $spreadsheet, int|string|null $key)
    {
        if ($key === null) {
            return $spreadsheet->getActiveSheet();
        }

        $chapter = $this->report->getDefinition()->getOrCreateChapter($key);
        $sheet = $spreadsheet->getSheetByCodeName($chapter->getName());
        if ($sheet) {
            return $sheet;
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setCodeName($chapter->getName());
        $sheet->setTitle($chapter->getLabel());

        return $sheet;
    }

}
