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
        $hq = \EDTB\Domain\Powers\PowersRepository::getHQSystemName($mysqli, (string)$siSystemPower);
        $hqTitle = $hq ? 'Headquarters: ' . $hq : '';
        $siSystemData = '<a href="#" title="' . $hqTitle . '">' . $siSystemPower . '</a> [' . $siSystemPowerState . ']';
    } elseif (empty($siSystemPower) && empty($siSystemPowerState)) {
        $siSystemData = $siSystemPowerState;
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
    if (is_numeric($siSystemPopulation)) { $rows[] = '<strong>Population:</strong> ' . number_format((int)$siSystemPopulation); }
    if (!empty($siSystemEconomy))       { $rows[] = '<strong>Economy:</strong> '    . $siSystemEconomy; }
    if (!empty($siSystemRulingFaction)) { $rows[] = '<strong>Faction:</strong> '    . $siSystemRulingFaction; }

    if (!empty($rows)) {
        $out .= '<span>' . implode('<br>', $rows) . '</span>';
    }

    return $out;
}
