<?php

/**
 *
 *  @link           https://github.com/demetris/omni-contact-form
 *  @author         Demetris Kikizas <d@kikizas.com>
 *  @copyright      2017 Demetris Kikizas
 *  @license        GPL-2.0
 *
 */

namespace OmniContactForm;

class Quiz
{
    /*
    |
    |   The properties for the multiplication quiz: two factors ($a and $b) and product ($p).
    |
    */
    private $a;
    private $b;
    private $p;

    public function __construct() {
        $this->a  = rand(1, 4);
        $this->b  = rand(1, 4);
        $this->p  = $this->a * $this->b;
    }

    public function getA(): int {
        return $this->a;
    }

    public function getB(): int {
        return $this->b;
    }

    public function getProduct(): int {
        return $this->p;
    }
}
