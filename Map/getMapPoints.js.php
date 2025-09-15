<?php
require_once __DIR__ . '/../GalMap/lib/GalMapParams.php';
use EDTB\GalMap\GalMapParams;
$params = GalMapParams::fromRequest($_GET);
/**
 * Ajax backend file to fetch map points for Neighborhood Map
 *
 * No description
 *
 * @package EDTB\Backend
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

/** @require config */
require_once __DIR__ . '/../source/config.inc.php';
/** @require functions */
require_once __DIR__ . '/../source/functions.php';
/** @require MySQL */
require_once __DIR__ . '/../source/MySQL.php';
/** @require curSys */
require_once __DIR__ . '/../source/curSys.php';
require_once __DIR__ . '/Services/MapSeriesBuilder.php';

header('Content-Type: application/javascript; charset=utf-8');

if (isset($params->maxDistance) && is_numeric($params->maxDistance)) {
    $settings['maxdistance'] = $params->maxDistance;
}

/**
 * if current coordinates aren't valid, use last known coordinates
 */
$disclaimer = '';
if (!validCoordinates($curSys['x'], $curSys['y'], $curSys['z'])) {
    // get last known coordinates
    $lastCoords = lastKnownSystem();

    $curSys['x'] = $lastCoords['x'];
    $curSys['y'] = $lastCoords['y'];
    $curSys['z'] = $lastCoords['z'];

    $disclaimer = '<p><strong>No coordinates for current location, last known location used</strong></p>';
}

if (!validCoordinates($curSys['x'], $curSys['y'], $curSys['z'])) {
    $curSys['x'] = '0';
    $curSys['y'] = '0';
    $curSys['z'] = '0';

    $disclaimer = '<p><strong>Current location unknown, Sol used.</strong></p>';
}


$series = \EDTB\Map\Services\MapSeriesBuilder::build($mysqli, $settings, $curSys, $params);

// === derive extents & chart options ===
$centerX = (float)$curSys['x'];
$centerY = (float)$curSys['y'];
$centerZ = (float)$curSys['z'];
$maxdistance = (isset($settings['maxdistance']) && is_numeric($settings['maxdistance']))
    ? (float)$settings['maxdistance'] : 50.0;

$minx = $centerX - $maxdistance;
$maxx = $centerX + $maxdistance;
$miny = $centerY - $maxdistance;
$maxy = $centerY + $maxdistance;
$minz = $centerZ - $maxdistance;
$maxz = $centerZ + $maxdistance;

// Highcharts options expected inline below
$zoomtype = "zoomType: 'xy',";
$panning  = 'true';
$pankey   = "panKey: 'shift',";
$threed   = (isset($params->mode) && $params->mode === '3d') ? 'true' : 'false';
?>


/** custom tooltip format */
function tooltipFormatter() {
    var value;
    <?php
    if (isset($params->mode) && $params->mode === '2d') {
        ?>
        value = this.series.name.toUpperCase();
        <?php
    } else {
        ?>
        value = this.series.name.toUpperCase() + " is " +
            Math.round(Math.sqrt(
                Math.pow((this.point.x - (<?= $curSys['x'] ?>)), 2) +
                Math.pow((this.point.y - (<?= $curSys['y'] ?>)), 2) +
                Math.pow((this.point.z - (<?= $curSys['z'] ?>)), 2)
            )) + " ly away";
        <?php
    }
    ?>
    return value;
}

$(function ()
{
    // Give the points a 3D feel by adding a radial gradient
    /** Highcharts.getOptions().colors = $.map(Highcharts.getOptions().colors, function (color) {
        return {
            radialGradient: {
                cx: 0.4,
                cy: 0.3,
                r: 0.5
            },
            stops: [
                [0, color],
                [1, Highcharts.Color(color).brighten(-0.2).get('rgb')]
            ]
        };
    }); */
    Highcharts.theme =
    {
        /** colors: ['rgba(117,38,38,0.7)', 'rgba(192,251,251,0.7)', 'rgba(120,171,173,0.7)', 'rgba(195,44,222,0.7)', 'rgba(255,179,0,0.7)', 'rgba(24,219,216,0.7)', 'rgba(128,0,0,0.7)', 'rgba(145,232,23,0.7)'], */
        chart:
        {
            backgroundColor: 'transparent',
            style:
            {
                fontFamily: "Telegrama"
            },
            plotBorderColor: '#606063'
        },
        xAxis:
        {
            gridLineColor: '#707073',
            backgroundColor: "#CCC",
            labels:
            {
                style:
                {
                    color: '#E0E0E3'
                }
            },
            lineColor: '#707073',
            minorGridLineColor: '#505053',
            tickColor: '#707073',
            title:
            {
                style:
                {
                    color: '#A0A0A3'

                }
            }
        },
        yAxis:
        {
            gridLineColor: '#707073',
            labels:
            {
                style:
                {
                    color: '#E0E0E3'
                }
            },
            lineColor: '#707073',
            minorGridLineColor: '#505053',
            tickColor: '#707073',
            tickWidth: 1,
            title:
            {
                style:
                {
                    color: '#A0A0A3'
                }
            }
        },
        tooltip:
        {
            backgroundColor: 'rgba(0, 0, 0, 0.85)',
            style:
            {
                color: '#FFFFFA',
                fontSize: '11px',
                fontFamily: 'Sintony',
                letterSpacing: 'normal'
            }
        },
        plotOptions:
        {
            series:
            {
                dataLabels:
                {
                    color: '#B0B0B3'
                },
                marker:
                {
                    lineColor: '#333'
                },
                enableMouseTracking: true
            },
            boxplot:
            {
                fillColor: '#505053'
            },
            candlestick:
            {
                lineColor: 'white'
            }
       }
    };

    // Apply the theme
    Highcharts.setOptions(Highcharts.theme);

    // get the jQuery wrapper
    //var $report = $('#report');

    // Set up the chart
    var chart = new Highcharts.Chart(
    {
        loading:
        {
            labelStyle:
            {
                fontStyle: 'italic'
            }
        },
        chart:
        {
            renderTo: 'container',
            margin: 90,
            type: 'scatter',
            stickyTracking: false,
            <?= $zoomtype?>
            panning: <?= $panning?>,
            <?= $pankey?>
            options3d:
            {
                enabled: <?= $threed?>,
                alpha: 20,
                beta: 30,
                depth: 120,
                frame:
                {
                    back:
                    {
                        color: "#1E2021"
                    },
                    side:
                    {
                        color: "#1E2021"
                    },
                    bottom:
                    {
                        color: "#1E2021"
                    }
                }
            }
        },
        title:
        {
            text: ''
        },
        subtitle:
        {
            text: ''
        },
        plotOptions:
        {
            scatter:
            {
                width:10,
                height: 10,
                depth: 10
            },
            series:
            {
                animation: false,
                cursor: 'pointer',
                point:
                {
                    events:
                    {
                        click: function ()
                        {
                            get_mi(this.series.name);
                        }
                    }
                }
            }
        },
        tooltip:
        {
            formatter: tooltipFormatter,
            animation: false
        },
        xAxis:
        {
            min: <?= round($minx)?>,
            max: <?= round($maxx)?>,
            gridLineWidth: 1
        },
        yAxis:
        {
            min: <?= round($miny)?>,
            max: <?= round($maxy)?>,
            title: null
        },
        zAxis:
        {
            min: <?= round($minz)?>,
            max: <?= round($maxz)?>
        },
        credits:
        {
            enabled: true
        },
        legend: { enabled: true, layout: 'vertical', align: 'left', verticalAlign: 'top' },
        exporting:
        {
            enabled: false
        },
        series: [<?= $series ?>]
    });

    // Add mouse events for rotation
    $(chart.container).bind('mousedown.hc touchstart.hc', function (e)
    {
        e = chart.pointer.normalize(e);

        var posX = e.pageX,
            posY = e.pageY,
            alpha = chart.options.chart.options3d.alpha,
            beta = chart.options.chart.options3d.beta,
            newAlpha,
            newBeta,
            sensitivity = 5; // lower is more sensitive

        $(document).bind(
        {
            'mousemove.hc touchdrag.hc': function (e)
            {
                // Run beta
                newBeta = beta + (posX - e.pageX) / sensitivity;
                newBeta = Math.min(100, Math.max(-100, newBeta));
                chart.options.chart.options3d.beta = newBeta;

                // Run alpha
                newAlpha = alpha + (e.pageY - posY) / sensitivity;
                newAlpha = Math.min(100, Math.max(-100, newAlpha));
                chart.options.chart.options3d.alpha = newAlpha;

                chart.redraw(false);
            },
                'mouseup touchend': function ()
                {
                    $(document).unbind('.hc');
                }
        });
    });
    $('#loader').hide();
});

$(function () {
    var disclaimer = $('#disclaimer');
    <?php if ($disclaimer !== '') { ?>
    disclaimer.html('<?= $disclaimer?>');
    <?php } else { ?>
    disclaimer.html("");
    <?php } ?>
});

