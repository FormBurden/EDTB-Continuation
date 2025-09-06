<?php
/** @var string $uAgent the users user_agent */
global $uAgent;
$uAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

/**
 * Get user's browser and platform
 * @return array
 * @author ruudrp
 */
function getBrowser()
{
    global $uAgent;

    $bname = 'Unknown';
    $platform = 'Unknown';

    // First get the platform?
    if (stripos($uAgent, 'linux') !== false) {
        $platform = 'linux';
    } elseif (preg_match('/macintosh|mac os x/i', $uAgent)) {
        $platform = 'mac';
    } elseif (preg_match('/windows|win32/i', $uAgent)) {
        $platform = 'windows';
    }

    // Next get the name of the useragent yes seperately and for good reason
    if (stripos($uAgent, 'MSIE') !== false && !false !== stripos($uAgent, 'Opera')) {
        $bname = 'Internet Explorer';
        $ub = 'MSIE';
    } elseif (stripos($uAgent, 'Firefox') !== false) {
        $bname = 'Mozilla Firefox';
        $ub = 'Firefox';
    } elseif (stripos($uAgent, 'Chrome') !== false) {
        $bname = 'Google Chrome';
        $ub = 'Chrome';
    } elseif (stripos($uAgent, 'Safari') !== false) {
        $bname = 'Apple Safari';
        $ub = 'Safari';
    } elseif (stripos($uAgent, 'Flock') !== false) {
        $bname = 'Flock';
        $ub = 'Flock';
    } elseif (stripos($uAgent, 'Opera') !== false) {
        $bname = 'Opera';
        $ub = 'Opera';
    } elseif (stripos($uAgent, 'Netscape') !== false) {
        $bname = 'Netscape';
        $ub = 'Netscape';
    } else {
        $ub = 'other';
    }

    // finally get the correct version number
    $known = ['Version', $ub, 'other'];
    $pattern = '#(?<browser>' . join('|', $known) .
               ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    $version = '?';

    if (!preg_match_all($pattern, $uAgent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($uAgent, 'Version') < strripos($uAgent, $ub)) {
            $version= $matches['version'][0];
        } else {
            $version= $matches['version'][1];
        }
    } else {
        $version= $matches['version'][0];
    }

    if ($version == null || $version == '') {
        $version = '?';
    }

    return [
        'userAgent' => $uAgent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'   => $pattern
    ];
}

/**
 * Get user's OS
 * @return string
 */
function getOS()
{
    global $uAgent;

    $osPlatform = 'Unknown OS Platform';

    $osArray = [
        '/windows nt 10/i'     =>  'Windows 10',
        '/windows nt 6.3/i'    =>  'Windows 8.1',
        '/windows nt 6.2/i'    =>  'Windows 8',
        '/windows nt 6.1/i'    =>  'Windows 7',
        '/windows nt 6.0/i'    =>  'Windows Vista',
        '/windows nt 5.2/i'    =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'    =>  'Windows XP',
        '/windows xp/i'        =>  'Windows XP',
        '/windows nt 5.0/i'    =>  'Windows 2000',
        '/windows me/i'        =>  'Windows ME',
        '/win98/i'             =>  'Windows 98',
        '/win95/i'             =>  'Windows 95',
        '/win16/i'             =>  'Windows 3.11',
        '/macintosh|mac os x/i'=>  'Mac OS X',
        '/mac_powerpc/i'       =>  'Mac OS 9',
        '/linux/i'             =>  'Linux',
        '/ubuntu/i'            =>  'Ubuntu',
        '/iphone/i'            =>  'iPhone',
        '/ipod/i'              =>  'iPod',
        '/ipad/i'              =>  'iPad',
        '/android/i'           =>  'Android',
        '/blackberry/i'        =>  'BlackBerry',
        '/webos/i'             =>  'Mobile'
    ];

    foreach ($osArray as $regex => $value) {
        if (preg_match($regex, $uAgent)) {
            $osPlatform = $value;
        }
    }

    return $osPlatform;
}
