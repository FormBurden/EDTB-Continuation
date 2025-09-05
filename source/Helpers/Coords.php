<?php
function lastKnownSystem($onlyedsm = false): array
{
    global $mysqli;

    // Cold start: if the table(s) don't exist yet, return empty coords safely
    $exists = $mysqli->query("SHOW TABLES LIKE 'user_visited_systems'");
    if (!$exists || $exists->num_rows === 0) {
        return ['name' => '', 'x' => '', 'y' => '', 'z' => ''];
    }
    $exists->close();

    if ($onlyedsm === true) {
        $query = "
            SELECT uvs.system_name, es.x, es.y, es.z
            FROM user_visited_systems uvs
            LEFT JOIN edtb_systems es ON uvs.system_name = es.name
            WHERE es.x != ''
            ORDER BY uvs.visit DESC
            LIMIT 1
        ";
    } else {
        $query = "
            SELECT uvs.system_name, es.x, es.y, es.z,
                   uso.x AS own_x, uso.y AS own_y, uso.z AS own_z
            FROM user_visited_systems uvs
            LEFT JOIN edtb_systems  es  ON uvs.system_name = es.name
            LEFT JOIN user_systems_own uso ON uvs.system_name = uso.name
            WHERE es.x != '' OR uso.x != ''
            ORDER BY uvs.visit DESC
            LIMIT 1
        ";
    }

    $result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

    $lastSystem = ['name' => '', 'x' => '', 'y' => '', 'z' => ''];

    if ($result && $result->num_rows > 0) {
        $coordObj = $result->fetch_object();
        $result->close();

        $lastSystem['name'] = $coordObj->system_name;
        $lastSystem['x']    = $coordObj->x;
        $lastSystem['y']    = $coordObj->y;
        $lastSystem['z']    = $coordObj->z;

        // If not in edtb_systems, fall back to user-owned coords
        if ($lastSystem['x'] === '') {
            $lastSystem['x'] = $coordObj->own_x;
            $lastSystem['y'] = $coordObj->own_y;
            $lastSystem['z'] = $coordObj->own_z;
        }
    }

    if ($result) {
        $result->close();
    }

    return $lastSystem;
}

/**
 * Return usable coordinates
 *
 * @return array of floats x, y, z and bool current
 * @author Mauri Kujala <contact@edtb.xyz>
 */
function usableCoords()
{
    global $mysqli;

    // 1) Try current system coordinates
    $cquery = "SELECT
                   user_currentsystem.x AS x,
                   user_currentsystem.y AS y,
                   user_currentsystem.z AS z
               FROM user_currentsystem
               LIMIT 1";

    $cresult = $mysqli->query($cquery);
    $cx = $cy = $cz = null;

    if ($cresult && $cresult->num_rows > 0) {
        $crow = $cresult->fetch_object();
        $cx = $crow->x;
        $cy = $crow->y;
        $cz = $crow->z;
    }
    if ($cresult) {
        $cresult->close();
    }

    $usable = ['x' => 0, 'y' => 0, 'z' => 0, 'current' => false];

    if (validCoordinates($cx, $cy, $cz)) {
        $usable['x'] = $cx;
        $usable['y'] = $cy;
        $usable['z'] = $cz;
        $usable['current'] = true;
        return $usable;
    }

    // Fall back to last known (handles cold-starts if tables exist)
    $last = lastKnownSystem();
    $lx = is_array($last) ? ($last['x'] ?? null) : null;
    $ly = is_array($last) ? ($last['y'] ?? null) : null;
    $lz = is_array($last) ? ($last['z'] ?? null) : null;

    if (validCoordinates($lx, $ly, $lz)) {
        $usable['x'] = $lx;
        $usable['y'] = $ly;
        $usable['z'] = $lz;
    }

    return $usable;
}

/**
 * Validate coordinate tuple
 *
 * @param mixed $x
 * @param mixed $y
 * @param mixed $z
 * @return bool
 */
function validCoordinates($x, $y, $z): bool
{
    if ($x === null || $y === null || $z === null) {
        return false;
    }
    if ($x === '' || $y === '' || $z === '') {
        return false;
    }
    return is_numeric($x) && is_numeric($y) && is_numeric($z);
}

/**
 * Get distance from current or last-known system to given system
 *
 * @param string $system
 * @return string $distance rounded to 2 decimals
 * @author Mauri Kujala <contact@edtb.xyz>
 */
function getDistance($system)
{
    global $mysqli;

    /**
     * fetch target coordinates
     */
    $escSys = $mysqli->real_escape_string($system);

    $query = "  (SELECT
                edtb_systems.x AS target_x,
                edtb_systems.y AS target_y,
                edtb_systems.z AS target_z
                FROM edtb_systems
                WHERE edtb_systems.name = '$escSys')
                UNION
                (SELECT
                user_systems_own.x AS target_x,
                user_systems_own.y AS target_y,
                user_systems_own.z AS target_z
                FROM user_systems_own
                WHERE user_systems_own.name = '$escSys')
               ";

    $result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

    $target_x = $target_y = $target_z = null;

    if ($result && $result->num_rows > 0) {
        $obj = $result->fetch_object();
        $target_x = $obj->target_x;
        $target_y = $obj->target_y;
        $target_z = $obj->target_z;
    }
    if ($result) {
        $result->close();
    }

    $coords = usableCoords();
    $cur_x = $coords['x'];
    $cur_y = $coords['y'];
    $cur_z = $coords['z'];

    // make sure we have numeric values
    if (!validCoordinates($cur_x, $cur_y, $cur_z) ||
        !validCoordinates($target_x, $target_y, $target_z)) {
        return 'n/a';
    }

    /**
     * calculate the distance
     */
    $distance = round(sqrt(
        pow(($target_x - $cur_x), 2) +
        pow(($target_y - $cur_y), 2) +
        pow(($target_z - $cur_z), 2)
    ), 2);

    return $distance;
}
