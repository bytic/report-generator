## Installation

Use [composer](https://getcomposer.org) to install PhpSpreadsheet into your project:

```sh
composer require bytic/report-generator
```

## Concepts

#### Perspectives

Used to do different filters to run the same report with different data.

#### ReportChapters

Used to create different sections in the report.
Each chapter can have different Headers.

## Simple Usage

This would be the simplest way to generate a report:

##### Report.php
```php
<?php

namespace MyApp\Reports\CustomReport;

use ByTIC\ReportGenerator\Report\AbstractReport;
use ByTIC\ReportGenerator\Report\ReportInterface;

/**
 * Class Report
 * @package MyApp\Reports\CustomReport
 */
class Report extends AbstractReport implements ReportInterface
{
    /**
     * @throws \Exception
     */
    protected function define()
    {
        $this->getDefinition()
            ->setTitle('Test Report')
            ->setFileName('Test Report');

        $this->getDefinition()
            ->addColumnSimple('first_name')
            ->addColumnSimple('last_name')
            ->addColumnSimple('status', translator()->trans('status'));
    }


    /**
     * @return Event
     * @throws \Exception
     */
    protected function getEvent()
    {
        $event = $this->params['event'];
        if ($event instanceof Event) {
            return $event;
        }
        throw new \Exception("Invalid param event. Event must be a valid Event model");
    }
}
```

##### DataProvider.php
```php
<?php

namespace MyApp\Reports\CustomReport;

use ByTIC\ReportGenerator\Report\DataProvider\AbstractDataProvider;

/**
 * Class DataProvider
 * @package MyApp\Reports\CustomReport
 */
class DataProvider extends AbstractDataProvider
{
        /**
         * @inheritdoc
         * @throws \Exception
         */
        protected function generateData()
        {
            $dataRecords = $this->getEvent()->findRecords();
    
            foreach ($dataRecords as $record) {
                $return = [
                    'first_name' => $record->getFirstName(),
                    'last_name' => $record->getLastName(),
                    'status' => $record->getStatus()->getName(),
                ];
    
                yield $return;
            }
        }
    
        /**
         * @return Event
         * @throws \Exception
         */
        protected function getEvent()
        {
            $event = $this->params['event'];
            if ($event instanceof Event) {
                return $event;
            }
            throw new \Exception("Invalid param event. Event must be a valid Event model");
        }
}
```

##### Controller.php
```php
<?php

$report = new Report();
$report->setParam('event', $event);
$report->render();
```