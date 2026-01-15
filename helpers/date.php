<?php

function formatDateMDY($date)
{
    if (!$date || $date === '0000-00-00') return '';
    return date('m-d-Y', strtotime($date));
}

function formatDateTimeMDY($datetime)
{
    if (!$datetime) return '';
    return date('m-d-Y h:i A', strtotime($datetime));
}
