<?php
/**
 * Random insult generator... yes
 *
 * @return string $insult
 * @author Mauri Kujala
 */
function randomInsult()
{
    $insults = [
        "Thargoids have more brains than you do.",
        "Do you even know how to fly?",
        "I've seen limpets with better aim.",
        "Your ship flies like a Sidewinder with no thrusters.",
        "My grandmother boosts better than that."
    ];

    $rand = mt_rand(0, count($insults) - 1);
    return $insults[$rand];
}

/**
 * TTS override text
 *
 * @param string $str
 * @return string $retvalue
 * @author Mauri Kujala
 */
function ttsOverride($str)
{
    $search = [
        'Å', 'ä', 'Ä', 'Ö', 'ö', 'Ü', 'ü', 'ß', 'é', 'è', 'ê', 'á', 'à', 'â',
        'É', 'È', 'Ê', 'Á', 'À', 'Â'
    ];
    $replace = [
        'Aa', 'a', 'A', 'O', 'o', 'Ue', 'ue', 'ss', 'e', 'e', 'e', 'a', 'a', 'a',
        'E', 'E', 'E', 'A', 'A', 'A'
    ];

    return str_replace($search, $replace, $str);
}

/**
 * Get rank name
 *
 * @param int $rank
 * @return string $retvalue
 * @author Mauri Kujala
 */
function shipName($name)
{
    // convert to a “nice” ship name if needed
    $name = trim($name);
    if ($name === '') {
        return 'Unknown Ship';
    }
    $name = str_replace('_', ' ', $name);
    return ucwords(strtolower($name));
}

/**
 * Remove invalid DOS characters from file/dir names
 *
 * @param string $sourceString
 * @return string
 * @author David Marshall
 */
function stripInvalidDosChars($sourceString)
{
    $invalidChars = ['"', '*', '/', ':', '<', '>', '?', '\\', '|']; // Invalid chars according to Windows 10

    return str_replace($invalidChars, '_', $sourceString);
}
