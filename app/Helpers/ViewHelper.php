<?php
// app/Helpers/ViewHelper.php

namespace App\Helpers;

use App\Services\AuthService;
use Carbon\Carbon;

class ViewHelper
{
// Fungsi yang sudah ada
public static function currentAccess()
{
    return AuthService::currentAccess();
}
public static function statusSubmissionHTML($status_number)
{
    $label = '';
    $color = '';
    // $bg_color = '';
    switch ($status_number) {
        case 0:
            $label = 'Draft';
            $color = 'secondary';
            break;
        case 1:
                $label = 'Revise';
                $color = 'warning';
        break;
        case 5:
                $label = 'Approval Process';
                $color = 'primary';
            break;
        case 10:
                $label = 'Approved';
                $color = 'success';
                break;
        case 25:
                $label = 'Rejected';
                $color = 'danger';
                break;
        default:
            break;
    }

    return '<div class="badge border border-2 rounded-pill text-'.$color.' bg-light-'.$color.' p-2 text-uppercase px-3"><i class="bx bxs-circle me-1"></i>'.$label.'</div>';
}

public static function getHourAndMinute($date_time){
        $parsed_date = Carbon::parse($date_time);

        $formatted_time = $parsed_date->format('H:i');

        return $formatted_time;
}


public static function humanReadableDate($date_time)
{
    // Cek jika $date_time null atau kosong
    if (empty($date_time)) {
        return '-';
    }

    try {
        $date = Carbon::parse($date_time);
        $date->locale('id');
        return $date->isoFormat('dddd, D MMMM YYYY');
    } catch (\Exception $e) {
        return '-';
    }
}



}
