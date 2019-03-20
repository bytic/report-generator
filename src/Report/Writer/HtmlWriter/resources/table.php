<?php
/** @var \ByTIC\ReportGenerator\Report\AbstractReport $report */

use ByTIC\ReportGenerator\Report\Definition\Columns\MultiColumn;

$report = $this->report;

$borderStyles = [
    'border-left: 1px dotted #ddd;',
    'border-left: 1px solid #b7b7b7;',
    'border-left: 1px solid #999;',
];

$header = $report->getHeader();
$headerRows = $header->getRows();
?>
<table class="table" style="border: 1px solid #ddd;">
    <thead>
    <?php
    $level = 1;
    $rows = count($headerRows);
    $borderStyles = array_splice($borderStyles, 0, $rows);
    $borderStyles = array_pad($borderStyles, $rows, $borderStyles[0]);
    $borderStyles = array_reverse($borderStyles);
    $lastHeaderRow = [];
    ?>
    <?php foreach ($headerRows as $headerRow) { ?>
        <?php
        $styles = 'text-align:center;';
        $styles .= 'font-size: ' . (110 - $level * 10) . '%;';
        $styles .= 'font-weight: ' . ($level < 2 ? '800' : '300');
        ?>
        <tr>
            <?php foreach ($headerRow as $column) { ?>
                <?php
                $borderStyle = $borderStyles[$level - 1];
                $parentName = $column->getParam('parentName');
                if ($parentName) {
                    $parentColumn = $lastHeaderRow[$parentName];
                    if ($parentColumn->isFirstChild($column)) {
                        $borderStyle = $parentColumn->getParam('borderStyle');
                    }
                }

                $column->setParam('borderStyle', $borderStyle);
                ?>
                <th colspan="<?php echo $column instanceof MultiColumn ? $column->childrenCount() : 1; ?>"
                    style="<?php echo $styles; ?>;<?php echo $borderStyle; ?>">
                    <?php echo $column->getTitle(); ?>
                </th>
            <?php } ?>
        </tr>
        <?php
        $lastHeaderRow = $headerRow;
        $level++;
        ?>
    <?php } ?>
    </thead>
    <tbody>
    <?php foreach ($report->getData() as $rowData) { ?>
        <tr>
            <?php foreach ($headerRow as $column) { ?>
                <?php
                $styles = 'text-align:right;';
                $styles .= $column->getParam('borderStyle');
//                $styles .= 'font-weight: ' . ($level < 2 ? '800' : '300');
                ?>
                <td style="<?php echo $styles; ?>">
                    <?php echo $rowData->getValue($column); ?>
                </td>
            <?php } ?>
        </tr>
    <?php } ?>
    </tbody>
</table>