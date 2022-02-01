<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer;

/**
 * Class Html.
 */
class Html extends AbstractWriter implements WriterInterface
{
    /**
     * Save to file or stream.
     *
     * @param string $name
     */
    public function getContent()
    {
        $response = $this->generateResponse();

        return $response->getContent();
    }

    /**
     * Save to file or stream.
     *
     * @param string $name
     */
    public function save($name)
    {
    }

    /**
     * @return string
     */
    protected function getFileExtension(): string
    {
        return '.html';
    }

    /**
     * {@inheritDoc}
     */
    protected function generateResponseContent($response)
    {
        $response->setContent(
            $this->generateHtml()
        );

        return $response;
    }

    /**
     * @return false|string
     */
    protected function generateHtml()
    {
        ob_start();
        require __DIR__ . '/HtmlWriter/resources/table.php';

        return ob_get_clean();
    }
}
