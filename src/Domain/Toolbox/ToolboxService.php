<?php
namespace EDTB\Domain\Toolbox;

class ToolboxService
{
    /**
     * Extracted from get/getData.php.
     * Returns keys:
     *   - update_map ("true"/"false")
     *   - new_sys ("true"/"false")
     *   - current_system_name (string)
     *   - current_coordinates (string|null)
     *
     * @param array $curSys
     * @param mixed $newSystem
     * @return array
     */
    public static function mapAndCurrent(array $curSys, $newSystem): array
    {
        $data = [];

        // update galmap json if system is new or file doesn't exist
        // or if last update was more than an hour ago
        $data['update_map'] = 'false';
        $lastMapUpdate = edtbCommon('last_map_update', 'unixtime');
        $mapUpdateTimeFrame = time() - 1 * 60 * 60;

        if ($newSystem !== false
            || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/GalMap/map_points.json')
            || $lastMapUpdate < $mapUpdateTimeFrame
        ) {
            $data['update_map'] = 'true';
        }

        $data['new_sys'] = 'false';
        if ($newSystem !== false) {
            $data['new_sys'] = 'true';
        }

        $data['current_system_name'] = $curSys['name'] ?? '';
        $data['current_coordinates'] = $curSys['coordinates'] ?? null;

        return $data;
    }
}
