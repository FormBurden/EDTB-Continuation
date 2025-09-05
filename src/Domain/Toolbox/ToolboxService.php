<?php
namespace EDTB\Domain\Toolbox;

/**
 * ED Toolbox domain helpers extracted from get/getData.php
 * (1:1 logic — no added guards)
 */
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

    /**
     * Extracted from get/getData.php.
     * Handles the "update_in_progress" & auto-update launcher + notification text.
     * Returns keys:
     *   - update_in_progress ("true"/"false")
     *   - update_notification (HTML string, may be empty)
     *   - update_notification_data (string or "false")
     *
     * @param array $settings
     * @param mixed $newSystem
     * @param int   $request
     * @return array
     */
    public static function updateStatusAndAutoUpdate(array $settings, $newSystem, int $request): array
    {
        $out = [
            'update_in_progress'       => 'false',
            'update_notification'      => '',
            'update_notification_data' => 'false',
        ];

        if ($newSystem !== false || $request == 0) {
            // update system and station data in the background if last update was more than 6 hours ago
            $lastUpdate = edtbCommon('last_data_update', 'unixtime');
            $timeFrame  = time() - 6 * 60 * 60;

            $autoUpdateEnabled = $settings['data_auto_update'] ?? true;

            // run update script
            if ($autoUpdateEnabled !== 'false' && $lastUpdate < $timeFrame) {
                // fetch last update start time
                $lastDataUpdateStart = edtbCommon('last_data_update_start', 'unixtime');
                $startTimeFrame      = time() - 160;

                if ($lastDataUpdateStart < $startTimeFrame) {
                    $batchFile = $settings['install_path'] . '/bin/UpdateData/updatedata_bg.bat';
                    $vbsFile   = $settings['install_path'] . '/bin/UpdateData/runbat.vbs';

                    if (file_exists($batchFile) && file_exists($vbsFile)) {
                        edtbCommon('last_data_update_start', 'unixtime', true, time());

                        // original background launcher (Windows style kept 1:1)
                        pclose(popen('"' . $vbsFile . '"' . ' ' . '"' . $batchFile . '"', 'r'));

                        $out['update_in_progress'] = 'true';
                        $out['update_notification'] .= '<a href="javascript:void(0)" title="Update in progress" onclick="$(\'#notice\').fadeToggle(\'fast\')">';
                        $out['update_notification'] .= '<img src="/style/img/notice.png" class="icon26" alt="Update">';
                        $out['update_notification'] .= '</a>';
                        $out['update_notification_data']  = 'System and station data is being updated in the background.<br><br>';
                        $out['update_notification_data'] .= 'You can continue using ED ToolBox normally.';
                    } else {
                        write_log('Error: ' . $batchFile . " doesn't exist");
                    }
                }
            }
        }

        return $out;
    }
    /**
     * Extracted from get/getData.php.
     * Builds the "now playing" HTML based on local file or VLC JSON.
     *
     * @param array $settings
     * @return string HTML including the <img> icon and the now-playing text.
     */
    public static function nowPlaying(array $settings): string
    {
        $nowplaying = '';

        /**
         *  from file
         */
        if (isset($settings['nowplaying_file']) && !empty($settings['nowplaying_file'])) {
            if (file_exists($settings['nowplaying_file'])) {
                /** If Filename is playback.json will read JSON data for Google Play Music Desktop Player */
                if (basename($settings['nowplaying_file']) === 'playback.json') {
                    $jsonData = json_decode(file_get_contents($settings['nowplaying_file']), true);
                    $nowplaying .= $jsonData['song']['title'] . ' By: ' . $jsonData['song']['artist'];
                } else {
                    /** Otherwise just output the contents of the file */
                    $nowplaying .= file_get_contents($settings['nowplaying_file']);
                }
            } else {
                $nowplaying .= "File doesn't exist";
            }
        }

        /**
         *  from VLC (@author Travis)
         */
        if (isset($settings['nowplaying_vlc_password']) && !empty($settings['nowplaying_vlc_password'])) {
            $username = '';
            $password = $settings['nowplaying_vlc_password'];
            $url = $settings['nowplaying_vlc_url'];

            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'Authorization: Basic ' . base64_encode("$username:$password")
                ]
            ];

            $context = stream_context_create($opts);
            $result = file_get_contents($url, false, $context);

            $jsonData = json_decode($result, true);

            $nowplaying .= $jsonData['information']['category']['meta']['now_playing'];
        }

        if (empty($nowplaying)) {
            $nowplaying = 'Not playing';
        }

        return '<img src="/style/img/music.png" class="icon" alt="Now playing">' . $nowplaying;
    }
    /**
     * Wrapper for get/getData_leftColumn.php (1:1 behavior).
     * Returns keys: system_title, system_info, station_data (if set).
     *
     * @param array $settings
     * @param array $curSys
     * @return array
     */
    public static function leftColumn(array $settings, array $curSys): array
    {
        // Make the include see the same globals it had when run from get/getData.php
        global $mysqli, $api;

        // The include populates $data[...] keys in-place.
        $data = [];

        // Ensure DB/$mysqli context is loaded before running the left-column include.
        // curSys.php wires the DB connection and helpers that getData_leftColumn.php expects.
        $base = dirname(__DIR__, 3);
        require_once $base . '/source/curSys.php';

        // Run the original script 1:1 (it writes into $data[...] in this scope)
        require $base . '/get/getData_leftColumn.php';

        // Return exactly the three keys ED Toolbox expects in the JSON
        return [
            'system_title' => isset($data['system_title']) ? $data['system_title'] : '',
            'system_info'  => isset($data['system_info'])  ? $data['system_info']  : '',
            'station_data' => isset($data['station_data']) ? $data['station_data'] : '',
        ];
    }





    /**
     * Wrapper for System/getData_systemInfo.php (1:1 behavior).
     * Returns keys: si_name, si_stations, si_detailed (if set).
     *
     * @param array $settings
     * @param array $curSys
     * @return array
     */
    public static function systemInfo(array $settings, array $curSys): array
    {
        global $mysqli;
        $data = [];
        $base = dirname(__DIR__, 3);
        include $base . '/System/getData_systemInfo.php';
        $out = [];
        foreach (['si_name','si_stations','si_detailed'] as $k) {
            if (isset($data[$k])) {
                $out[$k] = $data[$k];
            }
        }
        return $out;
    }
}


