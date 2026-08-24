<?php

namespace App\Enums;

enum Priority: string
{
    case EMERGENCY = 'Emergency';
    case PRIORITY = 'Priority';
    case NORMAL = 'Normal';

    public function rank():int{
        return match($this){
            self:: EMERGENCY => 3,
            self:: PRIORITY => 2,
            self:: NORMAL => 1,
        };
    }

}