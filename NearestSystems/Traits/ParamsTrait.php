<?php
/** Delegates param parsing to NearestSystemsParams to keep behavior identical. */
trait NearestSystemsParamsTrait
{
    private function getQueryParams()
    {
        $built = NearestSystemsParams::build($_GET);

        // Preserve original side-effects on $this:
        if (!isset($this->addToQuery))       $this->addToQuery = '';
        if (!isset($this->powerParams))      $this->powerParams = '';
        if (!isset($this->allegianceParams)) $this->allegianceParams = '';

        $this->addToQuery       .= $built['addToQuery'];
        $this->powerParams      .= $built['powerParams'];
        $this->allegianceParams .= $built['allegianceParams'];
    }
}
