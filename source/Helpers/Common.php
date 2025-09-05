<?php
/**
 * Check if data is old
 *
 * @param int $time unix timestamp
 * @return bool
 * @author Mauri Kujala
 */
function dataIsOld($time)
{
    global $settings;

    $old = (isset($settings['data_notify_age']) ? (int)$settings['data_notify_age'] : 30) * 24 * 60 * 60;
    $since = time()-$old;

    if (empty($time)) {
        return false;
    }

    if ($time < $since) {
        return true;
    }

    return false;
}

/**
 * Fetch or update data from edtb_common
 *
 * @param string $name
 * @param string $field
 * @param bool $update
 * @param string $value
 * @return string|null $value if $update = false
 * @author Mauri Kujala
 */
function edtbCommon($name, $field, $update = false, $value = '')
{
    global $mysqli;

    // If the table doesn't exist yet, behave safely on cold start
    $exists = $mysqli->query("SHOW TABLES LIKE 'edtb_common'");
    if (!$exists || $exists->num_rows === 0) {
        // Reads return neutral defaults; writes are no-ops
        if ($update === true) {
            return null;
        }
        return ($field === 'unixtime') ? 0 : '';
    }

    if ($update !== true) {
        $query = "SELECT $field FROM edtb_common WHERE name = '$name' LIMIT 1";
        $result = $mysqli->query($query) or write_log($mysqli->error, __FILE__, __LINE__);

        if ($result && $result->num_rows > 0) {
            $obj = $result->fetch_object();
            $ret = $obj->$field;
            $result->close();
            return $ret;
        }

        if ($result) {
            $result->close();
        }
        return ($field === 'unixtime') ? 0 : '';
    }

    $escVal = $mysqli->real_escape_string($value);
    $stmt = "UPDATE edtb_common SET $field = '$escVal' WHERE name = '$name' LIMIT 1";
    $mysqli->query($stmt) or write_log($mysqli->error, __FILE__, __LINE__);
    return null;
}
