<?php

class Counter{

  public $Tens = array();
  public $Unified = array();
  public $X = 0, $Y = 0, $Z = 0, $F = 0;
  private $Plate = 0;
  private $ImageCenter;

  function __construct($PlateType) {
    $this->Plate = ($PlateType == null ? $PlateType = 0 : $PlateType);
    for ($i=0; $i<10; $i++) {
      $this->Tens[$i] = new Radiant();
      if ($i < 2)
        $this->Unified[$i] = array('NW' => 0, 'NE' => 0, 'SE' => 0, 'SW' => 0, 'C' => 0);
    }
  }

  function GetRawData() {
    $sOut = $this->X.';'.$this->Y.';'.$this->Z.';'.($this->X * 100).';'.($this->Y * 100).';'.($this->Z * 100).';';
    $sOut .= $this->Tens[0]->NW->GetRawData().';'.$this->Tens[0]->NE->GetRawData().';'.$this->Tens[0]->SE->GetRawData().';'.$this->Tens[0]->SW->GetRawData().';';
    $sOut .= $this->Tens[1]->NW->GetRawData().';'.$this->Tens[1]->NE->GetRawData().';'.$this->Tens[1]->SE->GetRawData().';'.$this->Tens[1]->SW->GetRawData().';';
    $sOut .= $this->Tens[2]->NW->GetRawData().';'.$this->Tens[2]->NE->GetRawData().';'.$this->Tens[2]->SE->GetRawData().';'.$this->Tens[2]->SW->GetRawData().';';
    $sOut .= $this->Tens[3]->NW->GetRawData().';'.$this->Tens[3]->NE->GetRawData().';'.$this->Tens[3]->SE->GetRawData().';'.$this->Tens[3]->SW->GetRawData().';';
    $sOut .= $this->Tens[4]->NW->GetRawData().';'.$this->Tens[4]->NE->GetRawData().';'.$this->Tens[4]->SE->GetRawData().';'.$this->Tens[4]->SW->GetRawData().';';
    $sOut .= $this->Tens[5]->NW->GetRawData().';'.$this->Tens[5]->NE->GetRawData().';'.$this->Tens[5]->SE->GetRawData().';'.$this->Tens[5]->SW->GetRawData().';';
    $sOut .= $this->Tens[6]->NW->GetRawData().';'.$this->Tens[6]->NE->GetRawData().';'.$this->Tens[6]->SE->GetRawData().';'.$this->Tens[6]->SW->GetRawData().';';
    $sOut .= $this->Tens[7]->NW->GetRawData().';'.$this->Tens[7]->NE->GetRawData().';'.$this->Tens[7]->SE->GetRawData().';'.$this->Tens[7]->SW->GetRawData().';';
    $sOut .= $this->Tens[8]->NW->GetRawData().';'.$this->Tens[8]->NE->GetRawData().';'.$this->Tens[8]->SE->GetRawData().';'.$this->Tens[8]->SW->GetRawData().';';
    $sOut .= $this->Tens[9]->NW->GetRawData().';'.$this->Tens[9]->NE->GetRawData().';'.$this->Tens[9]->SE->GetRawData().';'.$this->Tens[9]->SW->GetRawData().';';
    $sOut .= $this->Unified[0]['NW'].';'.$this->Unified[0]['NE'].';'.$this->Unified[0]['SE'].';'.$this->Unified[0]['SW'].';';
    $sOut .= $this->Unified[1]['NW'].';'.$this->Unified[1]['NE'].';'.$this->Unified[1]['SE'].';'.$this->Unified[1]['SW'];
    return $sOut;
  }

  function CalculateRating() {
    $i1Inner = $this->Unified[0]['NW'] + $this->Unified[0]['NE'] + $this->Unified[0]['SE'] + $this->Unified[0]['SW'];
    $i1Outer = $this->Unified[1]['NW'] + $this->Unified[1]['NE'] + $this->Unified[1]['SE'] + $this->Unified[1]['SW'];
    $i2Inner = $i1Inner + 4;
    $i2Outer = $i1Outer + 4;
    $iSum = $i2Inner + $i2Outer;
    $iFactor = (($this->Unified[0]['NW'] + $this->Unified[1]['NW'] + 2) / $iSum) *
               (($this->Unified[0]['NE'] + $this->Unified[1]['NE'] + 2) / $iSum) *
               (($this->Unified[0]['SE'] + $this->Unified[1]['SE'] + 2) / $iSum) *
               (($this->Unified[0]['SW'] + $this->Unified[1]['SW'] + 2) / $iSum);
    $iFactor *= 256;
    $fX = 1.1384332 - 2.297894 * exp(-0.70234011 * ($i2Outer / $i2Inner));
    $this->X = round($fX, 5);
    $fY = max($i1Outer, $i1Inner) / 12;
    $this->Y = round($fY, 5);
    $fZ = -1.693767 * $iFactor + 1.693767;
    $this->Z = round($fZ, 5);
    $this->F = round($iFactor, 5);
  }

  function CountInImage(Imagick $iObj) {
    $_xmax = $iObj->getImageWidth();
    $_ymax = $iObj->getImageHeight();
    $this->ImageCenter = [$_xmax / 2, $_ymax / 2];
    $_xmax -= 4;
    $_ymax -= 4;
    $c1Obj = null;
    for ($x=2; $x<$_xmax; $x++) {
      for ($y=2; $y<$_ymax; $y++) {
        $dObj = $iObj->getImagePixelColor($x, $y);
        $dColor = $dObj->getColor();
        $fDist = $this->_getDistance($x, $y);
        if ($fDist > 99)
          continue;

        $i1Dist = intval(floor($fDist / 10));

        $xtest = $x - $this->ImageCenter[0];
        $ytest = $y - $this->ImageCenter[1];

        if ($xtest < 0 && $ytest < 0)
          $c1Obj =& $this->Tens[$i1Dist]->NW;
        elseif ($xtest > 0 && $ytest < 0)
          $c1Obj =& $this->Tens[$i1Dist]->NE;
        elseif ($xtest > 0 && $ytest > 0)
          $c1Obj =& $this->Tens[$i1Dist]->SE;
        elseif ($xtest < 0 && $ytest > 0)
          $c1Obj =& $this->Tens[$i1Dist]->SW;
        else
          $c1Obj =& $this->Tens[$i1Dist]->C;

        $c1Obj->AddPixel($dColor);
      }
    }
    for ($i=0; $i<10; $i++) {
      foreach ($this->Tens[$i] AS $key => $rObj) {
        $rObj->Update();
      }
    }

    if ($this->Plate == 96) {

      $this->Unified[0]['NW'] = round(
        ($this->Tens[0]->NW->Rating * $this->Tens[0]->NW->Total +
        $this->Tens[1]->NW->Rating * $this->Tens[1]->NW->Total +
        $this->Tens[2]->NW->Rating * $this->Tens[2]->NW->Total +
        $this->Tens[3]->NW->Rating * $this->Tens[3]->NW->Total +
        $this->Tens[4]->NW->Rating * $this->Tens[4]->NW->Total +
        $this->Tens[5]->NW->Rating * $this->Tens[5]->NW->Total +
        $this->Tens[6]->NW->Rating * $this->Tens[6]->NW->Total) /
          ($this->Tens[0]->NW->Total +
          $this->Tens[1]->NW->Total +
          $this->Tens[2]->NW->Total +
          $this->Tens[3]->NW->Total +
          $this->Tens[4]->NW->Total +
          $this->Tens[5]->NW->Total +
          $this->Tens[6]->NW->Total + 1)
      );

      $this->Unified[0]['NE'] = round(
        ($this->Tens[0]->NE->Rating * $this->Tens[0]->NE->Total +
        $this->Tens[1]->NE->Rating * $this->Tens[1]->NE->Total +
        $this->Tens[2]->NE->Rating * $this->Tens[2]->NE->Total +
        $this->Tens[3]->NE->Rating * $this->Tens[3]->NE->Total +
        $this->Tens[4]->NE->Rating * $this->Tens[4]->NE->Total +
        $this->Tens[5]->NE->Rating * $this->Tens[5]->NE->Total +
        $this->Tens[6]->NE->Rating * $this->Tens[6]->NE->Total) /
          ($this->Tens[0]->NE->Total +
          $this->Tens[1]->NE->Total +
          $this->Tens[2]->NE->Total +
          $this->Tens[3]->NE->Total +
          $this->Tens[4]->NE->Total +
          $this->Tens[5]->NE->Total +
          $this->Tens[6]->NE->Total + 1)
      );

      $this->Unified[0]['SE'] = round(
        ($this->Tens[0]->SE->Rating * $this->Tens[0]->SE->Total +
        $this->Tens[1]->SE->Rating * $this->Tens[1]->SE->Total +
        $this->Tens[2]->SE->Rating * $this->Tens[2]->SE->Total +
        $this->Tens[3]->SE->Rating * $this->Tens[3]->SE->Total +
        $this->Tens[4]->SE->Rating * $this->Tens[4]->SE->Total +
        $this->Tens[5]->SE->Rating * $this->Tens[5]->SE->Total +
        $this->Tens[6]->SE->Rating * $this->Tens[6]->SE->Total) /
          ($this->Tens[0]->SE->Total +
          $this->Tens[1]->SE->Total +
          $this->Tens[2]->SE->Total +
          $this->Tens[3]->SE->Total +
          $this->Tens[4]->SE->Total +
          $this->Tens[5]->SE->Total +
          $this->Tens[6]->SE->Total + 1)
      );

      $this->Unified[0]['SW'] = round(
        ($this->Tens[0]->SW->Rating * $this->Tens[0]->SW->Total +
        $this->Tens[1]->SW->Rating * $this->Tens[1]->SW->Total +
        $this->Tens[2]->SW->Rating * $this->Tens[2]->SW->Total +
        $this->Tens[3]->SW->Rating * $this->Tens[3]->SW->Total +
        $this->Tens[4]->SW->Rating * $this->Tens[4]->SW->Total +
        $this->Tens[5]->SW->Rating * $this->Tens[5]->SW->Total +
        $this->Tens[6]->SW->Rating * $this->Tens[6]->SW->Total) /
          ($this->Tens[0]->SW->Total +
          $this->Tens[1]->SW->Total +
          $this->Tens[2]->SW->Total +
          $this->Tens[3]->SW->Total +
          $this->Tens[4]->SW->Total +
          $this->Tens[5]->SW->Total +
          $this->Tens[6]->SW->Total + 1)
      );

      $this->Unified[0]['C'] = round(
        ($this->Tens[0]->C->Rating * $this->Tens[0]->C->Total +
        $this->Tens[1]->C->Rating * $this->Tens[1]->C->Total +
        $this->Tens[2]->C->Rating * $this->Tens[2]->C->Total +
        $this->Tens[3]->C->Rating * $this->Tens[3]->C->Total +
        $this->Tens[4]->C->Rating * $this->Tens[4]->C->Total +
        $this->Tens[5]->C->Rating * $this->Tens[5]->C->Total +
        $this->Tens[6]->C->Rating * $this->Tens[6]->C->Total) /
          ($this->Tens[0]->C->Total +
          $this->Tens[1]->C->Total +
          $this->Tens[2]->C->Total +
          $this->Tens[3]->C->Total +
          $this->Tens[4]->C->Total +
          $this->Tens[5]->C->Total +
          $this->Tens[6]->C->Total + 1)
      );

      $this->Unified[1]['NW'] = round(
        ($this->Tens[7]->NW->Rating * $this->Tens[7]->NW->Total +
        $this->Tens[8]->NW->Rating * $this->Tens[8]->NW->Total +
        $this->Tens[9]->NW->Rating * $this->Tens[9]->NW->Total) /
          ($this->Tens[7]->NW->Total +
          $this->Tens[8]->NW->Total +
          $this->Tens[9]->NW->Total + 1)
      );

      $this->Unified[1]['NE'] = round(
        ($this->Tens[7]->NE->Rating * $this->Tens[7]->NE->Total +
        $this->Tens[8]->NE->Rating * $this->Tens[8]->NE->Total +
        $this->Tens[9]->NE->Rating * $this->Tens[9]->NE->Total) /
          ($this->Tens[7]->NE->Total +
          $this->Tens[8]->NE->Total +
          $this->Tens[9]->NE->Total + 1)
      );

      $this->Unified[1]['SE'] = round(
        ($this->Tens[7]->SE->Rating * $this->Tens[7]->SE->Total +
        $this->Tens[8]->SE->Rating * $this->Tens[8]->SE->Total +
        $this->Tens[9]->SE->Rating * $this->Tens[9]->SE->Total) /
          ($this->Tens[7]->SE->Total +
          $this->Tens[8]->SE->Total +
          $this->Tens[9]->SE->Total + 1)
      );

      $this->Unified[1]['SW'] = round(
        ($this->Tens[7]->SW->Rating * $this->Tens[7]->SW->Total +
        $this->Tens[8]->SW->Rating * $this->Tens[8]->SW->Total +
        $this->Tens[9]->SW->Rating * $this->Tens[9]->SW->Total) /
          ($this->Tens[7]->SW->Total +
          $this->Tens[8]->SW->Total +
          $this->Tens[9]->SW->Total + 1)
      );

      $this->Unified[1]['C'] = round(
        ($this->Tens[7]->C->Rating * $this->Tens[7]->C->Total +
        $this->Tens[8]->C->Rating * $this->Tens[8]->C->Total +
        $this->Tens[9]->C->Rating * $this->Tens[9]->C->Total) /
          ($this->Tens[7]->C->Total +
          $this->Tens[8]->C->Total +
          $this->Tens[9]->C->Total + 1)
      );

    } else {

      $this->Unified[0]['NW'] = round(
        ($this->Tens[0]->NW->Rating * $this->Tens[0]->NW->Total +
        $this->Tens[1]->NW->Rating * $this->Tens[1]->NW->Total +
        $this->Tens[2]->NW->Rating * $this->Tens[2]->NW->Total +
        $this->Tens[3]->NW->Rating * $this->Tens[3]->NW->Total +
        $this->Tens[4]->NW->Rating * $this->Tens[4]->NW->Total +
        $this->Tens[5]->NW->Rating * $this->Tens[5]->NW->Total +
        $this->Tens[6]->NW->Rating * $this->Tens[6]->NW->Total +
        $this->Tens[7]->NW->Rating * $this->Tens[7]->NW->Total) /
          ($this->Tens[0]->NW->Total +
          $this->Tens[1]->NW->Total +
          $this->Tens[2]->NW->Total +
          $this->Tens[3]->NW->Total +
          $this->Tens[4]->NW->Total +
          $this->Tens[5]->NW->Total +
          $this->Tens[6]->NW->Total +
          $this->Tens[7]->NW->Total + 1)
      );

      $this->Unified[0]['NE'] = round(
        ($this->Tens[0]->NE->Rating * $this->Tens[0]->NE->Total +
        $this->Tens[1]->NE->Rating * $this->Tens[1]->NE->Total +
        $this->Tens[2]->NE->Rating * $this->Tens[2]->NE->Total +
        $this->Tens[3]->NE->Rating * $this->Tens[3]->NE->Total +
        $this->Tens[4]->NE->Rating * $this->Tens[4]->NE->Total +
        $this->Tens[5]->NE->Rating * $this->Tens[5]->NE->Total +
        $this->Tens[6]->NE->Rating * $this->Tens[6]->NE->Total +
        $this->Tens[7]->NE->Rating * $this->Tens[7]->NE->Total) /
          ($this->Tens[0]->NE->Total +
          $this->Tens[1]->NE->Total +
          $this->Tens[2]->NE->Total +
          $this->Tens[3]->NE->Total +
          $this->Tens[4]->NE->Total +
          $this->Tens[5]->NE->Total +
          $this->Tens[6]->NE->Total +
          $this->Tens[7]->NE->Total + 1)
      );

      $this->Unified[0]['SE'] = round(
        ($this->Tens[0]->SE->Rating * $this->Tens[0]->SE->Total +
        $this->Tens[1]->SE->Rating * $this->Tens[1]->SE->Total +
        $this->Tens[2]->SE->Rating * $this->Tens[2]->SE->Total +
        $this->Tens[3]->SE->Rating * $this->Tens[3]->SE->Total +
        $this->Tens[4]->SE->Rating * $this->Tens[4]->SE->Total +
        $this->Tens[5]->SE->Rating * $this->Tens[5]->SE->Total +
        $this->Tens[6]->SE->Rating * $this->Tens[6]->SE->Total +
        $this->Tens[7]->SE->Rating * $this->Tens[7]->SE->Total) /
          ($this->Tens[0]->SE->Total +
          $this->Tens[1]->SE->Total +
          $this->Tens[2]->SE->Total +
          $this->Tens[3]->SE->Total +
          $this->Tens[4]->SE->Total +
          $this->Tens[5]->SE->Total +
          $this->Tens[6]->SE->Total +
          $this->Tens[7]->SE->Total + 1)
      );

      $this->Unified[0]['SW'] = round(
        ($this->Tens[0]->SW->Rating * $this->Tens[0]->SW->Total +
        $this->Tens[1]->SW->Rating * $this->Tens[1]->SW->Total +
        $this->Tens[2]->SW->Rating * $this->Tens[2]->SW->Total +
        $this->Tens[3]->SW->Rating * $this->Tens[3]->SW->Total +
        $this->Tens[4]->SW->Rating * $this->Tens[4]->SW->Total +
        $this->Tens[5]->SW->Rating * $this->Tens[5]->SW->Total +
        $this->Tens[6]->SW->Rating * $this->Tens[6]->SW->Total +
        $this->Tens[7]->SW->Rating * $this->Tens[7]->SW->Total) /
          ($this->Tens[0]->SW->Total +
          $this->Tens[1]->SW->Total +
          $this->Tens[2]->SW->Total +
          $this->Tens[3]->SW->Total +
          $this->Tens[4]->SW->Total +
          $this->Tens[5]->SW->Total +
          $this->Tens[6]->SW->Total +
          $this->Tens[7]->SW->Total + 1)
      );

      $this->Unified[0]['C'] = round(
        ($this->Tens[0]->C->Rating * $this->Tens[0]->C->Total +
        $this->Tens[1]->C->Rating * $this->Tens[1]->C->Total +
        $this->Tens[2]->C->Rating * $this->Tens[2]->C->Total +
        $this->Tens[3]->C->Rating * $this->Tens[3]->C->Total +
        $this->Tens[4]->C->Rating * $this->Tens[4]->C->Total +
        $this->Tens[5]->C->Rating * $this->Tens[5]->C->Total +
        $this->Tens[6]->C->Rating * $this->Tens[6]->C->Total +
        $this->Tens[7]->C->Rating * $this->Tens[7]->C->Total) /
          ($this->Tens[0]->C->Total +
          $this->Tens[1]->C->Total +
          $this->Tens[2]->C->Total +
          $this->Tens[3]->C->Total +
          $this->Tens[4]->C->Total +
          $this->Tens[5]->C->Total +
          $this->Tens[6]->C->Total +
          $this->Tens[7]->C->Total + 1)
      );

      $this->Unified[1]['NW'] = round(
        ($this->Tens[8]->NW->Rating * $this->Tens[8]->NW->Total +
        $this->Tens[9]->NW->Rating * $this->Tens[9]->NW->Total) /
          ($this->Tens[8]->NW->Total +
          $this->Tens[9]->NW->Total + 1)
      );

      $this->Unified[1]['NE'] = round(
        ($this->Tens[8]->NE->Rating * $this->Tens[8]->NE->Total +
        $this->Tens[9]->NE->Rating * $this->Tens[9]->NE->Total) /
          ($this->Tens[8]->NE->Total +
          $this->Tens[9]->NE->Total + 1)
      );

      $this->Unified[1]['SE'] = round(
        ($this->Tens[8]->SE->Rating * $this->Tens[8]->SE->Total +
        $this->Tens[9]->SE->Rating * $this->Tens[9]->SE->Total) /
          ($this->Tens[8]->SE->Total +
          $this->Tens[9]->SE->Total + 1)
      );

      $this->Unified[1]['SW'] = round(
        ($this->Tens[8]->SW->Rating * $this->Tens[8]->SW->Total +
        $this->Tens[9]->SW->Rating * $this->Tens[9]->SW->Total) /
          ($this->Tens[8]->SW->Total +
          $this->Tens[9]->SW->Total + 1)
      );

      $this->Unified[1]['C'] = round(
        ($this->Tens[8]->C->Rating * $this->Tens[8]->C->Total +
        $this->Tens[9]->C->Rating * $this->Tens[9]->C->Total) /
          ($this->Tens[8]->C->Total +
          $this->Tens[9]->C->Total + 1)
      );

    }

  }

  function _getDistance(int $x, int $y) { // distanz hochgerechnet auf einen radius von 100
    return (sqrt((abs($x - $this->ImageCenter[0]) ** 2) + (abs($y - $this->ImageCenter[1]) ** 2))) * 100 / $this->ImageCenter[0];
  }

  function GetTensColor(int $x, int $y) {
    $fDist = $this->_getDistance($x, $y);
    if ($fDist > 99)
      return null;
    $i1Dist = intval(floor($fDist / 10));

    $xtest = $x - $this->ImageCenter[0];
    $ytest = $y - $this->ImageCenter[1];

    if ($xtest < 0 && $ytest < 0)
      return $this->Tens[$i1Dist]->NW->Color;
    elseif ($xtest > 0 && $ytest < 0)
      return $this->Tens[$i1Dist]->NE->Color;
    elseif ($xtest > 0 && $ytest > 0)
      return $this->Tens[$i1Dist]->SE->Color;
    elseif ($xtest < 0 && $ytest > 0)
      return $this->Tens[$i1Dist]->SW->Color;
    else
      return $this->Tens[$i1Dist]->C->Color;

    return null;
  }

  function GetPixelData(int $x, int $y) {
    $fDist = $this->_getDistance($x, $y);
    if ($fDist > 99)
      return null;
    $i1Dist = intval(floor($fDist / 10));
    $i2Dist = 0;
    if ($i1Dist == 7 && $this->Plate == 96) $i2Dist = 1;
    else if ($i1Dist == 8 || $i1Dist == 9) $i2Dist = 1;
    $xtest = $x - $this->ImageCenter[0];
    $ytest = $y - $this->ImageCenter[1];
    if ($xtest < 0 && $ytest < 0)
      return ['NW', $fDist, $i1Dist, $i2Dist];
    elseif ($xtest > 0 && $ytest < 0)
      return ['NE', $fDist, $i1Dist, $i2Dist];
    elseif ($xtest > 0 && $ytest > 0)
      return ['SE', $fDist, $i1Dist, $i2Dist];
    elseif ($xtest < 0 && $ytest > 0)
      return ['SW', $fDist, $i1Dist, $i2Dist];
    else
      return ['C', $fDist, $i1Dist, $i2Dist];
    return null;
  }

}

class Radiant {
  public $NW, $NE, $SW, $SE, $C;
  function __construct() {
    $this->NW = new Direction();
    $this->NE = new Direction();
    $this->SW = new Direction();
    $this->SE = new Direction();
    $this->C = new Direction();
  }
}

class Direction {
  public $Red = 0, $Green = 0, $White = 0, $Black = 0, $Total = 0;
  public $NotEmpty = 0, $Empty = 0, $NotMoving = 0, $Moving = 0;
  public $Color = '#FFFFFF', $Opacity = 1.0, $Rating = 0;

  function AddPixel(array $rgba) {
    if (IsGreen($rgba)) {
      $this->Green++;
      $this->NotEmpty++;
      $this->Moving++;
      $this->Total++;
    } elseif (IsRed($rgba)) {
      $this->Red++;
      $this->NotEmpty++;
      $this->Moving++;
      $this->Total++;
    } elseif (IsBlack($rgba)) {
      $this->Black++;
      $this->NotEmpty++;
      $this->NotMoving++;
      $this->Total++;
    } elseif (IsWhite($rgba)) {
      $this->White++;
      $this->Empty++;
      $this->NotMoving++;
      $this->Total++;
    }

  }

  function Update() {
    if ($this->NotEmpty == 0 ||
      $this->Black > ($this->Green + $this->Red) ||
      ($this->Empty / ($this->NotEmpty + $this->Empty)) > 0.9) {
      return;
    } else {
      $mpr = $this->NotEmpty / 3;
      if ($this->Green > $mpr && $this->Red > $mpr) {
        $this->Rating = 2;
      }
      elseif ($this->Green > $this->Red) {
        $this->Rating = 1;
      }
      else {
        $this->Rating = 3;
      }
      if ($this->NotEmpty / ($this->NotEmpty + $this->Empty) < 0.2)
        $this->Rating -= 2;
      elseif ($this->NotEmpty / ($this->NotEmpty + $this->Empty) < 0.4)
        $this->Rating -= 1;
      if ($this->Rating < 1)
        $this->Rating = 1;
      switch($this->Rating) {
        case 0:
          $this->Color = COL_EMPTY;
          return;
        case 1:
          $this->Color = COL_SLOW;
          return;
        case 2:
          $this->Color = COL_MIXED;
          return;
        case 3:
          $this->Color = COL_FAST;
          return;
      }
    }
  }

  function GetRawData() {
    return $this->White.';'.$this->Black.';'.$this->Green.';'.$this->Red.';'.$this->Rating;
  }

}
