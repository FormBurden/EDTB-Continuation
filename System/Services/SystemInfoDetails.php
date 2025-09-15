<?php
declare(strict_types=1);

/**
 * Detailed panel renderer for System Information.
 * Pure presentation: no global side effects. Mirrors legacy markup.
 */
function buildSystemDetailsHtml(
    \mysqli $mysqli,
    $siSystemPower,
    $siSystemPowerState,
    $siSystemPopulation,
    $siSystemAllegiance,
    $siSystemGovernment,
    $siSystemEconomy,
    $siSystemRulingFaction
): string {
    // Power block + HQ tooltip (if available)
    $out = '';
    $siSystemData = '';
    if (!empty($siSystemPower) && !empty($siSystemPowerState)) {
        $isNone = (strcasecmp((string)$siSystemPower, 'None') === 0) && (strcasecmp((string)$siSystemPowerState, 'None') === 0);

        // Optional HQ lookup (only if the class/method exists and power isn't "None")
        $hq = null;
        if (!$isNone
            && class_exists('\\EDTB\\Domain\\Powers\\PowersRepository')
            && method_exists('\\EDTB\\Domain\\Powers\\PowersRepository', 'getHQSystemName')) {
            $hq = \EDTB\Domain\Powers\PowersRepository::getHQSystemName($mysqli, (string)$siSystemPower);
        }

        $hqTitle = $hq ? ('Headquarters: ' . $hq) : '';
        $label   = htmlspecialchars((string)$siSystemPower, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $state   = htmlspecialchars((string)$siSystemPowerState, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // When "None", show plain text; otherwise, keep the tooltip but avoid a fake "#" link
        $nameHtml = $isNone
            ? $label
            : '<span title="' . htmlspecialchars($hqTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . $label . '</span>';

        $siSystemData = $nameHtml . ' [' . $state . ']';
    } elseif (empty($siSystemPower) && empty($siSystemPowerState)) {
        $siSystemData = '';
    } else {
        $siSystemData = '';
    }


    $dispPopulation = is_numeric($siSystemPopulation)
        ? number_format((int)$siSystemPopulation)
        : (string)$siSystemPopulation;

    if (!empty($siSystemPower)) {
        $img = '/style/img/powers/' . strtolower(str_replace(' ', '_', (string)$siSystemPower)) . '.jpg';
        $out .= '<img src="' . $img . '" class="powerpic" alt="' . $siSystemPower . '"><br>';
    }
    $out .= '<span style="font-size: 13px; font-weight: 700">' . $siSystemData . '</span><br><br>';

    $rows = [];
    if (!empty($siSystemAllegiance))    { $rows[] = '<strong>Allegiance:</strong> ' . $siSystemAllegiance; }
    if (!empty($siSystemGovernment))    { $rows[] = '<strong>Government:</strong> ' . $siSystemGovernment; }
    $rows[] = '<strong>Population:</strong> ' . (is_numeric($siSystemPopulation) ? number_format((int)$siSystemPopulation) : '0');
    if (!empty($siSystemEconomy))       { $rows[] = '<strong>Economy:</strong> '    . $siSystemEconomy; }
    if (!empty($siSystemRulingFaction)) { $rows[] = '<strong>Faction:</strong> '    . $siSystemRulingFaction; }

    if (!empty($rows)) {
        $out .= '<span>' . implode('<br>', $rows) . '</span>';
    }

    return $out;
}
