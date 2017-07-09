<?php

class Group {

  public $Name, $From, $Until;
  public $Children = array();

  function __construct(int $iIndex, array $aData) {
    $this->Name = ($aData['Name'] != '' ? $aData['Name'] : ($iIndex+1));
    $this->From = intval($aData['From']);
    $this->Until = intval($aData['Until']);
  }

}
