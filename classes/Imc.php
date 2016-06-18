<?php

class Imc {

    /**
     * @var int
     */
    public $people_id;

    /**
     * @var int
     */
    public $height;

    /**
     * @var float
     */
    public $mass;

    /**
     * @var string
     */
    public $date;

    public function getImc()
    {
        return $this->height > 0 ? $this->mass / $this->height / $this->height * 10000 : 0;
    }
}