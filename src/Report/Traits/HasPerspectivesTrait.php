<?php

namespace ByTIC\ReportGenerator\Report\Traits;

use ByTIC\ReportGenerator\Perspectives\Perspective;
use ByTIC\ReportGenerator\Perspectives\PerspectiveCollection;

/**
 * Trait HasPerspectivesTrait
 * @package ByTIC\ReportGenerator\Report\Traits
 */
trait HasPerspectivesTrait
{
    /**
     * @var PerspectiveCollection
     */
    protected $perspectives = null;

    protected $perspectiveCurrent = null;

    /**
     * @return PerspectiveCollection
     */
    public function getPerspectives()
    {
        if ($this->perspectives === null) {
            $this->initPerspectives();
        }
        return $this->perspectives;
    }

    protected function initPerspectives()
    {
        $perspectives = new PerspectiveCollection();
        $this->perspectives = $perspectives;
    }

    /**
     * @param $name
     * @param string $label
     * @return Perspective
     */
    public function createPerspective($name, $label = ''): Perspective
    {
        $perspective = new Perspective();
        $perspective->setName($name);
        $perspective->setLabel($label);
        $this->getPerspectives()->add($perspective);
        return $perspective;
    }

    public function setPerspective($perspective): ?Perspective
    {
        return $this->currentPerspective($perspective);
    }

    /**
     * @param string|null $currentPerspective
     * @return Perspective
     */
    public function currentPerspective(string $currentPerspective = null)
    {
        if ($currentPerspective !== null) {
            $this->perspectiveCurrent = $currentPerspective;
        }

        return $this->getCurrentPerspective();
    }

    /**
     * @return Perspective
     */
    protected function getCurrentPerspective(): ?Perspective
    {
        return $this->getPerspectives()->get($this->perspectiveCurrent);
    }

}
