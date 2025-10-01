<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('getRole')) {
    function getRole(): string
    {
        return Auth::user()->roles->first()->name;
    }
}
