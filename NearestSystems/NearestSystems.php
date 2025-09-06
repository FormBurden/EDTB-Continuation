<?php
/**
 * Nearest systems & stations class
 *
 * @package EDTB\Main
 * @author Mauri Kujala <contact@edtb.xyz>
 * @copyright Copyright (C) 2016, Mauri Kujala
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 */

 /*
 * ED ToolBox, a companion web app for the video game Elite Dangerous
 * (C) 1984 - 2016 Frontier Developments Plc.
 * ED ToolBox or its creator are not affiliated with Frontier Developments Plc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 */
require_once __DIR__ . '/partials/AllegianceIcons.php';
require_once __DIR__ . '/partials/Filters.php';
require_once __DIR__ . '/partials/Powers.php';
require_once __DIR__ . '/partials/Search.php';







use \EDTB\source\System;


require_once __DIR__ . '/Traits/DbTrait.php';
require_once __DIR__ . '/Traits/ParamsTrait.php';
require_once __DIR__ . '/Traits/QueryTrait.php';
require_once __DIR__ . '/Traits/FiltersTrait.php';
require_once __DIR__ . '/Traits/ContentTrait.php';

/**
 * Display nearest systems
 *
 * @author Mauri Kujala <contact@edtb.xyz>
 */
class NearestSystems
{
    use NearestSystemsDbTrait, NearestSystemsParamsTrait, NearestSystemsQueryTrait, NearestSystemsFiltersTrait, NearestSystemsContentTrait;

    /** @var string $system the system to use as a starting point */
    public $system;

    /** @var float $useX , $usey, $usez x, y and z coords to use for calculations */
    public $useX, $useY, $useZ;

    /** @var string $powerParams parameters to add to Power links */
    private $powerParams = '';

    /** @var string $allegianceParams parameters to add to Allegiance links */
    private $allegianceParams = '';

    /** @var string $text the info text */
    private $text = 'Nearest';
    private $is_unknown = '';

    /** @var string $addToQuery */
    private $addToQuery = '';

    /** @var string $hiddenInputs */
    private $hiddenInputs = '';

    /** @var bool $stations */
    private $stations = true;

    /** @var string $mainQuery */
    private $mainQuery;
    private $mysqli;


    /**
     * NearestSystems constructor.
     */
    public function __construct()
    {
        // Prefer global mysqli if already created by config.inc.php / MySQL.php
        global $mysqli, $server, $user, $pwd, $db;

        if ($mysqli instanceof mysqli) {
            $this->mysqli = $mysqli;
        } else {
            $host = isset($server) && $server !== '' ? $server : ($_ENV['MYSQL_HOST'] ?? 'localhost');
            $usr  = isset($user)   && $user   !== '' ? $user   : ($_ENV['MYSQL_USER'] ?? 'root');
            $pass = isset($pwd)    && $pwd    !== '' ? $pwd    : ($_ENV['MYSQL_PASSWORD'] ?? '');

            if (isset($db) && $db !== '') {
                $this->mysqli = new mysqli($host, $usr, $pass, $db);
            } else {
                $this->mysqli = new mysqli($host, $usr, $pass);
            }
        }

        if ($this->mysqli->connect_errno) {
            echo 'Failed to connect to MySQL: ' . $this->mysqli->connect_error;
        }

        // ← exactly one call, here:
        $this->ensureDbSelected();

        // determine what coordinates to use
        $this->system = isset($_GET['system']) ? (int)$_GET['system'] : 0;

        if (!empty($this->system)) {
            $query  = "SELECT name, id, x, y, z FROM edtb_systems WHERE id = '$this->system' LIMIT 1";
            $result = $this->mysqli->query($query) or write_log($this->mysqli->error, __FILE__, __LINE__);
            $sysObj = $result->fetch_object();

            $sysName    = $sysObj->name;
            $this->useX = $sysObj->x;
            $this->useY = $sysObj->y;
            $this->useZ = $sysObj->z;
            $result->close();

            $this->text              .= ' (to ' . $sysName . ') ';
            $this->powerParams       .= '&system=' . $this->system;
            $this->allegianceParams  .= '&system=' . $this->system;
        } else {
            $coords     = usableCoords(); // safe fallback (uses $curSys or lastKnownSystem or Sol)
            $this->useX = $coords['x'];
            $this->useY = $coords['y'];
            $this->useZ = $coords['z'];
            if ($coords['current'] !== true) {
                $this->is_unknown = ' *';
            }
        }

        if (!validCoordinates($this->useX, $this->useY, $this->useZ)) {
            $this->useX = '0';
            $this->useY = '0';
            $this->useZ = '0';
            $this->is_unknown = ' *';
        }
    }

    /**
     *
     * @return string
     */
    public function nearest()
    {
        $this->getQueryParams();

        $this->getQuery();

        $this->filters();

        $this->content();
    }





}
