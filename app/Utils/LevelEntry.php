<?php

namespace Utils;

class LevelEntry
{
    public $time;
    public $level;
    
    public function __construct($time, $level)
    {
        $this->time = $time;
        $this->level = $level;
    }
}
