<?php

namespace App\Entity;

class Location
{
    private $zip;
    private $name;
    private $lat;
    private $lon;
    private $country;
    
    public function getZip()
    {
        return $this->zip;
    }

    public function setZip($zip)
    {
        $this->zip = $zip;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function getLat()
    {
        return $this->lat;
    }
    public function setLat($lat)
    {
        $this->lat = $lat;
        return $this;
    }

    public function getLon()
    {
        return $this->lon;
    }
    public function setLon($lon)
    {
        $this->lon = $lon;
        return $this;
    }
}