<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bitwiseFlag
 *
 * @author mariano
 */
abstract class bitwiseFlag {

    protected $flags;

    /*
     * Note: these functions are protected to prevent outside code
     * from falsely setting BITS. See how the extending class 'User'
     * handles this.
     *
     */

    protected function isFlagSet($flag) {
        return (($this->flags & $flag) == $flag);
    }

    protected function setFlag($flag, $value) {
        if ($value) {
            $this->flags |= $flag;
        } else {
            $this->flags &= ~$flag;
        }
    }

}
