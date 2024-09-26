<?php

namespace App\Entity;

class Weather
{
    private $current;


    public function getCurrent()
    {
        return $this->current;
    }

    public function setCurrent($current)
    {
        $this->current = $current;
    }


}