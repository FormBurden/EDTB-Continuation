<?php
/**
 * Render a simple notice block
 *
 * @param string $msg
 * @param string $title
 * @return string
 */
function notice($msg, $title = 'Notice')
{
    $notice = '<div class="notice">';
    $notice .= '<div class="notice_title"><img src="/style/..." class="icon" style="margin-bottom: 3px">' . $title . '</div>';
    $notice .= '<div class="notice_text">' . $msg . '</div>';
    $notice .= '</div>';

    return $notice;
}