<?php

class Mousse {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $occurrence;

    /**
     * @var int
     */
    public $vote;

    public function getRating()
    {
        return $this->occurrence > 0
            ? $this->vote / $this->occurrence
            : 0;
    }
}