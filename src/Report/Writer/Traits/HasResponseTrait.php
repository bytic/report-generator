<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Report\Writer\Traits;

use ByTIC\ReportGenerator\Report\Writer\AbstractWriter;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
trait HasResponseTrait
{
    protected ?Response $response = null;

    public function getResponse(): ?Response
    {
        if ($this->response === null) {
            $this->response = $this->generateResponse();
        }
        return $this->response;
    }

    /**
     * @param $response
     * @return AbstractWriter
     */
    public function setResponse($response): AbstractWriter
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return Response
     */
    protected function generateResponse()
    {
        $response = new Response();

        $response = $this->generateResponseContent($response);
        $response = $this->generateResponseHeaders($response);

        return $response;
    }

    /**
     * @param $response
     *
     * @return Response
     */
    abstract protected function generateResponseContent(Response $response): Response;

    /**
     * @param $response
     * @return mixed
     */
    abstract protected function generateResponseHeaders(Response $response): Response;
}