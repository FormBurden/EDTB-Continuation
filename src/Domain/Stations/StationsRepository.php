<?php
declare(strict_types=1);

namespace EDTB\Domain\Stations;

final class StationsRepository
{
    /**
     * Returns an array of stdClass station rows for the given system.
     * We use SELECT * to preserve whatever fields downstream currently expect.
     * No new guards; behavior mirrors the old inline query.
     */
    public static function findBySystemId(\mysqli $mysqli, int $systemId): array
    {
        $systemId = (int)$systemId;

        // Keep ordering stable for UI; old code typically sorts by name or distance.
        $sql = "SELECT * FROM edtb_stations WHERE system_id = '$systemId' ORDER BY name ASC";

        $res = $mysqli->query($sql);
        if (!$res) {
            return [];
        }

        $rows = [];
        while ($obj = $res->fetch_object()) {
            $rows[] = $obj;
        }
        $res->close();

        return $rows;
    }
	    /**
     * Returns the raw mysqli_result for stations in a system.
     * Kept to match existing call sites that iterate with fetch_object().
     * Mirrors the previous inline behavior (logs on error, returns the result as-is).
     */
    public static function selectResultBySystemId(\mysqli $mysqli, int $systemId): \mysqli_result|false
    {
        $systemId = (int)$systemId;

        $sql = "  SELECT SQL_CACHE *
                  FROM edtb_stations
                  WHERE system_id = '$systemId'
                  ORDER BY -ls_from_star DESC, name";

        $res = $mysqli->query($sql) or write_log($mysqli->error, __FILE__, __LINE__);
        return $res; // may be false on error (same as old inline code)
    }
        /**
     * Returns the same $modCat structure the page used to build, but fetched here.
     * Keys are category_name; values are arrays of ['group_name','class','price','rating'] with stable $i order.
     * Mirrors the old inline foreach-per-id behavior (one query per id), no guards added.
     */
    public static function modulesByIds(\mysqli $mysqli, array $ids): array
    {
        $modCat = [];
        $i = 0;

        foreach ($ids as $mods) {
            // match prior behavior (string id used directly in WHERE),
            // keeping minimal change to logic and ordering
            $mods = $mysqli->real_escape_string((string)$mods);

            $query = "  SELECT SQL_CACHE class, rating, price, group_name, category_name
                        FROM edtb_modules
                        WHERE id = '$mods'
                        LIMIT 1";

            $result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

            if ($result && $result->num_rows > 0) {
                $modulesObj = $result->fetch_object();

                $modsName         = $modulesObj->group_name;
                $modsCategoryName = $modulesObj->category_name;
                $modsClass        = $modulesObj->class;
                $modsRating       = $modulesObj->rating;
                $modsPrice        = $modulesObj->price;

                $modCat[$modsCategoryName][$i] = [];
                $modCat[$modsCategoryName][$i]['group_name'] = $modsName;
                $modCat[$modsCategoryName][$i]['class']      = $modsClass;
                $modCat[$modsCategoryName][$i]['price']      = $modsPrice;
                $modCat[$modsCategoryName][$i]['rating']     = $modsRating;
                $i++;
            }

            if ($result) {
                $result->close();
            }
        }

        return $modCat;
    }


}
