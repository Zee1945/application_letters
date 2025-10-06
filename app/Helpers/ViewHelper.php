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
            $label = 'Sedang diproses';
            $color = 'info';
        } elseif ($status_number == 3) {
            $label = 'Tersedia';
            $color = 'success';
        } else {
            $label ='Gagal';
            $color ='danger';
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
        }elseif ($status_number == 1) {
            $label = 'Unsubmitted Report';
            $color = 'dangeer';
        } elseif ($status_number == 5) {
            $label = 'Submitted';
            $color = 'success';
        } 
        elseif ($status_number == 2) {
            $label = 'Revise';
            $color = 'warning';
        } elseif ($status_number > 5 && $status_number < 11 ) {
            $label = 'Approval Process';
            $color = 'primary';
        } elseif ($status_number > 10 && $status_number < 21) {
            if ($status_number == 11) {
                $label = 'Filling Letter Number';
                $color = 'info';
            }elseif($status_number == 13){
                $label = 'Approved & Finish';
                $color = 'success';
            }
            else {
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
  list($name,$position,$department) = explode('-',$app->currentUserApproval->user_text);
  $data = [
    'name'=>$name,
    'position'=>$position??'',
    'department'=>$department??'',
  ];
  return $data;


}


public static function humanReadableDate($date_time,$is_with_day=true)
{

    // Cek jika $date_time null atau kosong
    if (empty($date_time)) {
        return '-';
    }

    try {
        $date = Carbon::parse($date_time);
        $date->locale('id');
        if (!$is_with_day) {
            return $date->isoFormat('D MMMM YYYY');
        }
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
        $status =  $application->current_approval_status;
        $is_relevan_admin = AuthService::adminHasAccessToApplication($application->department_id);
        if ($is_relevan_admin) return '';
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
        if (AuthService::currentAccess()['role'] == 'super_admin') return true;
        $department = Department::find(AuthService::currentAccess()['department_id']);
        $quota_remaining = $department->limit_submission - $department->current_limit_submission;
        $admin_has_access = $app? AuthService::adminHasAccessToApplication($app->department_id):false;
        switch ($action) {
            case 'approval_process':
                if (!$is_report) {
                      if ($app->current_approval_status > 5 && $app->current_approval_status < 11 && $app->currentUserApproval->user_id == AuthService::currentAccess()['id']) {
                            return true;
                        }
                }else{

                    if ($app->current_approval_status > 5 && $app->current_approval_status < 11 && $app->currentUserApproval->user_id == AuthService::currentAccess()['id']) {
                        return true;
                    }
                }

                return false;
            case 'submit':
                if ($app->current_approval_status < 6 && $app->created_by == AuthService::currentAccess()['id'] && $quota_remaining > 0) {
                    return true;
                }
                return false;
            case 'submit-report':
                if ($app->current_approval_status < 6 && $app->created_by == AuthService::currentAccess()['id']) {
                    return true;
                }
                return false;
            case 'submit-letter-number':
                // dd(User::rolePosition('kabag',$app->department_id)->first());
                if ($app->current_approval_status == 11 &&
                    User::rolePosition('kabag',$app->department_id)->first()->id == AuthService::currentAccess()['id']) {
                    return true;
                }
                return false;
            case 'create-new-application':
                if ($quota_remaining > 0) {
                    return true;
                }
                return false;
            case 'admin-submit':
                if ($admin_has_access) {
                    if ($app->created_by == AuthService::currentAccess()['id'] && $app->current_approval_status != 12) {
                        return false;
                    }
                    return true;
                }
                return false;
            case 'admin-submit-letter-number':
                if ($admin_has_access) {
                    if ($app->created_by == AuthService::currentAccess()['id'] && $app->current_approval_status != 12) {
                        return false;
                    }
                    return true;
                }
                return false;
            case 'admin-submit-report':
                if ($admin_has_access){
                    if ($app->report->created_by == AuthService::currentAccess()['id'] && $app->current_approval_status != 11) {
                        return false;
                    }
                    return true;
                } 
                return false;
            default:
            
            return false;
        }

    }

    public static function getCurrentAccess(){
        return AuthService::currentAccess();
    }

    public static function departmentToShow(Department $department){
        if ($department->approval_by =='self') {
            return $department;
        }else if ($department->approval_by == 'parent') {
            return $department->parent;
        }else if ($department->approval_by == 'central') {
            return Department::whereCode('code','REKTORAT')->first();
        }
    }

    public static function canDo($permission)
{
    $user = auth()->user();
    // Jika permission ada di posisi user
    if ($user && $user->position && $user->position->hasPermissionTo($permission)) {
        return true;
    }
    return false;
}

    public static function generateColorSequence($current_seq, $sequence,$status)
    {
        if ($current_seq == $sequence){
            if ($status == 13) {
                return 'success';
            }
            return 'warning';
        }elseif( $current_seq > $sequence){
            return 'success';
        }elseif ($current_seq < $sequence) {
            return 'secondary';
        }
    }





}
