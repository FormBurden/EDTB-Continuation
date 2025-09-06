<?php
/** Filters are now applied inside the NearestSystemsQuery service to keep behavior identical. */
trait NearestSystemsFiltersTrait
{
    private function filters()
    {
        // no-op: kept for call compatibility; service already appended filters + ORDER BY.
    }
}
