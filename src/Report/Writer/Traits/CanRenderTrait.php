<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer\Traits;

use Symfony\Component\HttpFoundation\Response;

/**
 * Trait CanRenderTrait.
 */
trait CanRenderTrait
{
    public function render()
    {
        $response = $this->getResponse();
        $response->send();
        exit();
    }

    protected function generateResponseHeaders(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'public');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="' . $this->getFileName() . '"'
        );

        return $response;
    }
}
