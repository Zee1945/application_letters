<?php
// app/Helpers/ViewHelper.php

namespace App\Helpers;

use App\Models\Department;
use App\Models\User;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ViewHelper
{
// Fungsi yang sudah ada
public static function currentAccess()
{
    return AuthService::currentAccess();
}
public static function statusReportHTML($status_number)
{
        $label = '';
        $color = '';
        // $bg_color = '';
        if ($status_number == 0) {
            $label = 'Unsubmitted';
            $color = 'danger';
        } elseif ($status_number == 2) {
            $label = 'Revise';
            $color = 'warning';
        } elseif ($status_number > 5 && $status_number < 11) {
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

        return '<div class="badge border border-2 rounded-pill text-' . $color . ' bg-light-' . $color . ' p-2 text-uppercase px-3"><i class="bx bxs-circle me-1"></i>' . $label . '</div>';
}
public static function generateStatusFileHTML($status_number)
{
        $label = '';
        $color = '';
        // $bg_color = '';
        if ($status_number == 0) {
            $label = 'Belum Tersedia';
            $color = 'secondary';
        } elseif ($status_number == 2) {
            $label = 'Proses';
            $color = 'info';
        } elseif ($status_number == 3) {
            $label = 'Tersedia';
            $color = 'success';
        } else {
            // Default case if needed
        }

        return '<span class="badge rounded-pill bg-pastel-' . $color . '">' . $label . '</span>';
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
            if ($status_number == 11) {
                $label = 'Filling Letter Number';
                $color = 'info';
            }else {
                $label = 'Approved';
                $color = 'success';
            }

        }elseif ($status_number > 20) {
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

public static function getCurrentUserProcess($app,$is_report=false){
    $status_number = $is_report? $app->report->approval_status :$app->approval_status;
    $creator = $app->createdBy->name;
        if ($status_number < 6) {
            return $creator;
        } elseif ($status_number > 5 && $status_number < 11) {
            if ($is_report) {
                return $app->report->currentUserApproval->user_text;
            }
            return $app->currentUserApproval->user_text;
        } elseif ($status_number > 10 && $status_number < 21) {
            if ($status_number == 11 && !$is_report) {
                $get_kabag = User::rolePosition('kabag',$app->department_id)->first();

                return $get_kabag->name;
            } else {
               return $app->currentUserApproval->user_text;
            }
        } elseif ($status_number > 20) {
            return $app->currentUserApproval?->user_text;
        } else {
            return '';
        }
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

    public static function formatDateToHumanReadable($date, $format = 'd m Y')
    {
        $date = Carbon::parse($date)->locale('id');
        return $date->translatedFormat($format);
    }

    public static function handleFieldDisabled($application,$is_letter_number =false,$is_report=false) {
        $status = $is_report ? $application->report->approval_status : $application->approval_status;
        if ($status > 5 ) {
            if ($status == 11 && AuthService::currentAccess()['role'] == 'kabag' && $is_letter_number) {
                return '';
            }
            return 'disabled';
        }else if($status < 5 && $application->created_by != AuthService::currentAccess()['id']){
            return 'disabled';
        }
        return '';
    }

    public static function currencyFormat($amount=0  , $type='rupiah')
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
    public static function handleConfirmModal($modal_type='')
    {
        $array_info = [];
        switch ($modal_type) {
            case 'reject':
                $array_info = [
                    'color'=>'danger',
                    'title'=> 'Anda yakin ingin menolak?',
                    'text_reaseon'=>'penolakan',
                    'icon_class'=> 'fa-circle-xmark',
                ];

                break;
            case 'revise':
                $array_info = [
                    'color' => 'warning',
                    'title' => 'Anda yakin ingin mengajukan revisi?',
                    'text_reaseon' => 'revisi',
                    'icon_class' => 'fa-triangle-exclamation',
                ];

                break;
            case 'approve':
                $array_info = [
                    'color' => 'success',
                    'title' => 'Konfirmasi',
                    'text_reaseon' => '',
                    'icon_class' => '',
                ];
                break;

            default:
                $array_info = [
                    'color' => '',
                    'title' => '',
                    'text_reaseon' => '',
                    'icon_class' => '',
                ];
                break;
        }
        return $array_info;
    }


    public static function actionPermissionButton($action,$app=null, $is_report=false) {
        $department = Department::find(AuthService::currentAccess()['department_id']);
        $quota_remaining = $department->limit_submission - $department->current_limit_submission;
        switch ($action) {
            case 'approval_process':
                if (!$is_report) {
                      if ($app->approval_status > 5 && $app->approval_status < 11 && $app->current_user_approval == AuthService::currentAccess()['id']) {
                            return true;
                        }
                }else{

                    if ($app->report->approval_status > 5 && $app->report->approval_status < 11 && $app->report->current_user_approval == AuthService::currentAccess()['id']) {
                        return true;
                    }
                }

                return false;
            case 'submit':
                if ($app->approval_status < 6 && $app->created_by == AuthService::currentAccess()['id'] && $quota_remaining > 0) {
                    return true;
                }
                return false;
            case 'submit-report':
                if ($app->report->approval_status < 6 && $app->created_by == AuthService::currentAccess()['id']) {
                    return true;
                }
                return false;
            case 'submit-letter-number':
                // dd(User::rolePosition('kabag',$app->department_id)->first());
                if ($app->approval_status == 11 &&
                    User::rolePosition('kabag',$app->department_id)->first()->id == AuthService::currentAccess()['id']) {
                    return true;
                }
                return false;
            case 'create-new-application':
                if ($quota_remaining > 0) {
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
