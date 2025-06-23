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
        if ($status_number == 0) {
            $label = 'Draft';
            $color = 'secondary';
        } elseif ($status_number == 2) {
            $label = 'Revise';
            $color = 'warning';
        } elseif ($status_number > 5 && $status_number < 11 ) {
            $label = 'Approval Process';
            $color = 'primary';
        } elseif ($status_number > 10 && $status_number < 21) {
            $label = 'Approved';
            $color = 'success';
        } elseif ($status_number > 20) {
            $label = 'Rejected';
            $color = 'danger';
        } else {
            // Default case if needed
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

    public static function handleFieldDisabled($application) {
        $status = $application->approval_status;
        if ($status > 5) {
            return 'disabled';
        }
        return '';
    }


    public static function actionPermissionButton($action,$app) {
        switch ($action) {
            case 'approval_process':
                if ($app->approval_status > 5 && $app->approval_status < 11 && $app->current_user_approval == AuthService::currentAccess()['id']) {
                    return true;
                }
                return false;
            case 'submit':
                if ($app->approval_status < 6 && $app->created_by == AuthService::currentAccess()['id']) {
                    return true;
                }
                return false;
            default:
                # code...
                break;
        }

        return false;
    }



}
