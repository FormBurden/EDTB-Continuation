<?php
/** Delegates SQL-building to NearestSystemsQuery service. */
trait NearestSystemsQueryTrait
{
    private function getQuery()
    {
        $svc = new NearestSystemsQuery($this->mysqli, $this->useX, $this->useY, $this->useZ);
        $built = $svc->build($_GET, $this->addToQuery);
        $this->mainQuery = $built['sql'];
        $this->stations  = $built['stations'];
    }
}
