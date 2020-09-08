<?php

namespace Utils;

class LogParser
{    
    public static function parseLog($path)
    {
        // 875; 2020-09-07 20:23:28; Level; Interstitial glucose; 8.61574; 9; 2; {B387A874-8D1E-460B-A5EC-BA36AB7516DE}; {3034568D-F498-455B-AC6A-BCF301F69C9E}
        
        $iglevels = [];
        
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV);
        $frst = false;
        foreach ($file as $row)
        {
            // skip header
            if (!$frst)
            {
                $frst = true;
                continue;
            }
            list($lamport, $datetime, $eventname, $info, $level, $segment, $eventcode, $deviceid, $signalid) = $row;

            if ($signalid == "{3034568D-F498-455B-AC6A-BCF301F69C9E}")
                $iglevels[] = new LevelEntry(strtotime($datetime), floatval($level));
        }
        
        return $iglevels;
    }
}
