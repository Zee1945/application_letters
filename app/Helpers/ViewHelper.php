<?php
// app/Helpers/ViewHelper.php

namespace App\Helpers;

use App\Services\AuthService;

class ViewHelper
{
// Fungsi yang sudah ada
public static function currentAccess()
{
    return AuthService::currentAccess();
}

}
