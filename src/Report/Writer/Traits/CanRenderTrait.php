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
        $response = $this->generateResponse();
        $response->send();
        exit();
    }

    /**
     * @return Response
     */
    protected function generateResponse()
    {
        $response = new Response();

        $response = $this->generateResponseContent($response);

        $response->headers->set('Cache-Control', 'public');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="' . $this->getFileName() . '"'
        );

        return $response;
    }

    /**
     * @param $response
     *
     * @return Response
     */
    abstract protected function generateResponseContent($response);
}
