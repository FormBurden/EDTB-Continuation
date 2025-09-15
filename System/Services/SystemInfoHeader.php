<?php
declare(strict_types=1);

/**
 * Render the System Info header strip (system name + mini meta + rares label).
 * Mirrors the legacy concatenations 1:1 (no behavior guards).
 */
function renderSystemHeaderHtml(
    string $siSystemDisplayName,
    string $siCrosslinks,
    string $siSystemState,
    string $siSystemSecurity,
    int $numVisits,
    string $rareText,
    string $userDists,
    ?string $siSystemAllegiance = null
): string {

    $out = '';

    $icon = '';
    if ($siSystemAllegiance !== null && $siSystemAllegiance !== '') {
        // Map allegiance -> icon file (style/img/*)
        $map = require __DIR__ . '/../Lookups/AllegianceIconMap.php';
        $iconFile = $map[$siSystemAllegiance] ?? null;
        if ($iconFile) {
            $path   = '/style/img/' . $iconFile;
            $fsPath = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . $path;
            if ($fsPath && is_file($fsPath)) {
                $icon = '<img class="si_allegiance" src="'
                      . $path
                      . '" alt="'
                      . htmlspecialchars($siSystemAllegiance, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                      . '">';
            }
        }
    }


    $out .= $icon . $siSystemDisplayName . $siCrosslinks;
    $out .= '&nbsp;&nbsp;<span style="font-size: 11px;  text-transform: uppercase; vertical-align: middle">';
    // formatSiHeaderMeta() is defined in System/Formatters/SystemInfoFormatters.php (already required by the controller)
    $out .= formatSiHeaderMeta($siSystemState, $siSystemSecurity, (int)$numVisits);
    $out .= ($rareText !== '' ? '&nbsp;|&nbsp;' . $rareText : '') . $userDists . '</span>';
    return $out;
}
