<?php

namespace anacaona;

class Charge
{
    public static function chajeklas()
    {
        spl_autoload_register([__CLASS__, 'urhc']);
    }

    public static function urhc($klasla)
    {
        $klasla = str_replace(__NAMESPACE__.'\\','', $klasla);
        $chimen = __DIR__.'/'.$klasla.'.php';
        if(file_exists($chimen))
        {
            require_once $chimen;
        }

    }
}