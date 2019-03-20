<?php
/** @var \ByTIC\ReportGenerator\Report\AbstractReport $report */

use ByTIC\ReportGenerator\Report\Definition\Columns\MultiColumn;

$report = $this->report;

$header = $report->getHeader();
$headerRows = $header->getRows();
?>
<table class="table">
    <thead>
    <?php foreach ($headerRows as $headerRow) { ?>
        <tr>
            <?php foreach ($headerRow as $column) { ?>
                <th colspan="<?php echo $column instanceof MultiColumn ? $column->childrenCount() : 1; ?>"
                    style="text-align:right">
                    <?php echo $column->getTitle(); ?>
                </th>
            <?php } ?>
        </tr>
    <?php } ?>
    </thead>
    <tbody>
    <?php foreach ($report->getData() as $rowData) { ?>
        <tr>
            <?php foreach ($headerRow as $column) { ?>
                <td style="text-align:right">
                    <?php echo $rowData->getValue($column); ?>
                </td>
            <?php } ?>
        </tr>
    <?php } ?>
    </tbody>
</table>