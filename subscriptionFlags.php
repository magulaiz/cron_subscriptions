<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of subscriptionFlags
 *
 * @author mariano
 */

abstract class bitwiseFlag {

    protected $flags;


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
class subscriptionFlags extends bitwiseFlag
{
  const FLAG_USER_EXISTS = 1;
  const FLAG_DB_EXISTS = 2;
  const FLAG_DRUPAL_CONFIG = 4;
  const FLAG_WWW_AVAILABLE = 8;
  const FLAG_WWW_ENABLED = 16;
  const FLAG_WWW_ONLINE = 32;

  public function userExists(){
    return $this->isFlagSet(self::FLAG_USER_EXISTS);
  }

  public function dbExists(){
    return $this->isFlagSet(self::FLAG_DB_EXISTS);
  }

  public function drupalIsConfig(){
    return $this->isFlagSet(self::FLAG_DRUPAL_CONFIG);
  }
  
  public function wwwIsAvailable(){
    return $this->isFlagSet(self::FLAG_WWW_AVAILABLE);
  }

  public function wwwIsEnabled(){
    return $this->isFlagSet(self::FLAG_WWW_ENABLED);
  }

  public function wwwIsOnline(){
    return $this->isFlagSet(self::FLAG_WWW_ONLINE);
  }

  public function userSetExists($value){
    $this->setFlag(self::FLAG_USER_EXISTS, $value);
  }

  public function dbSetExists($value){
    $this->setFlag(self::FLAG_DB_EXISTS, $value);
  }
  
  public function drupalSetConfig($value){
    $this->setFlag(self::FLAG_DRUPAL_CONFIG, $value);
  }
  
  public function wwwSetAvailable($value){
    $this->setFlag(self::FLAG_WWW_AVAILABLE, $value);
  }

  public function wwwSetEnabled($value){
    $this->setFlag(self::FLAG_WWW_ENABLED, $value);
  }

  public function wwwSetOnline($value){
    $this->setFlag(self::FLAG_WWW_ONLINE, $value);
  }

  public function __toString(){
    $value = $this->userExists() * self::FLAG_USER_EXISTS + $this->dbExists() * self::FLAG_DB_EXISTS + $this->drupalIsConfig() * self::FLAG_DRUPAL_CONFIG + $this->wwwIsAvailable() * self::FLAG_WWW_AVAILABLE + $this->wwwIsEnabled() * self::FLAG_WWW_ENABLED + $this->wwwIsOnline() * self::FLAG_WWW_ONLINE;
    return strval($value);
  }
}
