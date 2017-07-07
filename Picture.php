<?php

define('WORKING_DIR', 'Work/');

class Pic {

  public $Exists = false;
  public $Basename, $Filename, $Folderpath, $Extension, $Fullpath;
  public $Experiment, $SubExperiment, $Description, $Y, $X, $ImageIndex;
  public $Filesize, $Width, $Height;
  public $Wellplate, $Wellsize, $Center, $Radius;
  public $Counter;

  private $WorkingCopy;

  function __construct($sPath) {
    if (!file_exists($sPath)) {
      $this->Exists = false;
      return;
    }
    $this->Exists = true;

    $pi = pathinfo($sPath);
    $this->Basename = $pi['basename'];
    $this->Filename = $pi['filename'];
    $this->Folderpath = $pi['dirname'];
    $this->Extension = $pi['extension'];
    $this->Fullpath = $sPath;

    $gi = getimagesize($sPath);
    $this->Width = $gi[0];
    $this->Height = $gi[1];
    $this->Filesize = filesize($sPath);

    if (preg_match('/(Test)?\s?(?<Experiment>[0-9]+)(\.(?<Subexperiment>[0-9]+))?(?<Description>.*)_(?<Y>[A-Z])(?<X>[0-9]{2})_(?<Image>[0-9]+)\.PNG$/i', $this->Basename, $aPregOut)) {
      $this->Experiment = $aPregOut['Experiment'];
      if (array_key_exists('Subexperiment', $aPregOut))
        $this->SubExperiment = $aPregOut['Subexperiment'];
      $this->Description = trim($aPregOut['Description']);
      $this->Y = $aPregOut['Y'];
      $this->X = intval($aPregOut['X']);
      $this->ImageIndex = intval($aPregOut['Image']);
    }

    $this->_CreateWorkingCopy();
    $this->Counter = new Counter($this->Wellplate);
    $this->Counter->CountInImage($this->WorkingCopy);

    $iDraw = new ImagickDraw();
    $iDraw->setStrokeColor('#000000');
    $iDraw->setStrokeOpacity(1.0);
    $iDraw->setFillColor('none');
    $iDraw->circle($this->Center[0], $this->Center[1], $this->Center[0] + $this->Radius + 1, $this->Center[1]);
    $iDraw->line($this->Center[0], $this->Center[1] - $this->Radius, $this->Center[0], $this->Center[1] + $this->Radius);
    $iDraw->line($this->Center[0] - $this->Radius, $this->Center[1], $this->Center[0] + $this->Radius, $this->Center[1]);
    $this->WorkingCopy->drawImage($iDraw);
    $this->WorkingCopy->writeImage(WORKING_DIR.$this->Filename.'-working.png');
    $iDraw->destroy();

  }

  private function _CreateWorkingCopy() {
    copy($this->Fullpath, WORKING_DIR.$this->Basename);
    $this->WorkingCopy = new Imagick(WORKING_DIR.$this->Basename);
    $this->WorkingCopy->setImageMatte(true);
    $this->_CropWorkingCopy();
  }

  private function _CropWorkingCopy() {
    $leftbar = null;
    for ($x=0; $x<$this->Width; $x++) {
      for ($y=0; $y<$this->Height; $y++) {
        $cDot = $this->WorkingCopy->getImagePixelColor($x, $y);
        if (IsRed($cDot->getColor())) {
          $leftbar = [[$x, $y], [$x, 0]];
          break;
        }
      }
      if ($leftbar != null)
        break;
    }
    $x = $leftbar[0][0];
    for ($y=$leftbar[0][1]; $y<$this->Height; $y++) {
      $cDot = $this->WorkingCopy->getImagePixelColor($x, $y);
      if (IsRed($cDot->getColor())) {
        $leftbar[1][1] = $y;
      } elseif (IsWhite($cDot->getColor())) {
        break;
      }
    }
    $center = [$x, intval(($leftbar[0][1] + $leftbar[1][1]) / 2)];
    $y = $center[1];
    $xmax = $x + 255;
    if ($xmax > $this->Width) $xmax = $this->Width - 10;
    $xfoundmax = 0;
    for ($x=$center[0]; $x<$xmax; $x++) {
      $cDot = $this->WorkingCopy->getImagePixelColor($x, $y);
      if (!IsWhite($cDot->getColor()))
        $xfoundmax = $x;
    }
    $size1 = [$center, [$xfoundmax + 1, $y]];
    $rad = intval(($size1[1][0] - $size1[0][0]) / 2);
    $center[0] = intval(($size1[0][0] + $size1[1][0]) / 2);

    if ($rad >= 31 && $rad <= 35)
      $this->Wellplate = 96;
    elseif ($rad >= 62 && $rad <= 66)
      $this->Wellplate = 24;
    elseif ($rad >= 88 && $rad <= 92)
      $this->Wellplate = 12;

    // the wellplate picture for displaying
    $this->WorkingCopy->cropImage($rad * 2, $rad * 2, $center[0] - $rad, $center[1] - $rad);
    $this->WorkingCopy->writeImage();

    // no starting with the real working copy
    $this->WorkingCopy->cropImage($rad * 2 - 2, $rad * 2 - 2, $center[0] - $rad + 1, $center[1] - $rad + 2);
    $this->WorkingCopy->setImagePage(0,0,0,0);
    $this->WorkingCopy->writeImage(WORKING_DIR.$this->Filename.'-working.png');
    //$this->WorkingCopy->writeImage();
    $this->Wellsize = [$this->WorkingCopy->getImageWidth(), $this->WorkingCopy->getImageHeight()];
    $this->Center = [intval($this->Wellsize[0] / 2), intval($this->Wellsize[1] / 2)];
    $this->Radius = intval($this->Wellsize[0] / 2) - 1;
    $this->_RadialCropWC();
    $this->Wellsize = [$this->WorkingCopy->getImageWidth(), $this->WorkingCopy->getImageHeight()];
    $this->Center = [intval($this->Wellsize[0] / 2), intval($this->Wellsize[1] / 2)];
    $this->Radius = intval($this->Wellsize[0] / 2) - 1;

    $oTemp = new Imagick();
    $oTemp->newImage($this->WorkingCopy->getImageWidth(), $this->WorkingCopy->getImageHeight(), 'none', "png");
    $oTemp->setImageMatte(true);

    $oDraw = new ImagickDraw();
    $oDraw->setStrokeColor('none');
    $oDraw->setFillColor('white');
    $oDraw->circle($this->Center[0], $this->Center[1], $this->Center[0] - $this->Radius + 1, $this->Center[1]);
    $oTemp->drawImage($oDraw);

    $this->WorkingCopy->compositeImage($oTemp, Imagick::COMPOSITE_DSTIN, 0, 0);
    $this->WorkingCopy->writeImage(WORKING_DIR.$this->Filename.'-working.png');

    $oDraw->destroy();
    $oTemp->clear();

  }

  function _RadialCropWC() {
    $marker = array(
      'N' => $this->_GetRadialMarker('N'),
      'NE' => $this->_GetRadialMarker('NE'),
      'E' => $this->_GetRadialMarker('E'),
      'SE' => $this->_GetRadialMarker('SE'),
      'S' => $this->_GetRadialMarker('S'),
      'SW' => $this->_GetRadialMarker('SW'),
      'W' => $this->_GetRadialMarker('W'),
      'NW' => $this->_GetRadialMarker('NW'),
    );
    if (is_null($marker['N']) || is_null($marker['NE']) || is_null($marker['E']) || is_null($marker['SE'])
       || is_null($marker['S']) || is_null($marker['SW']) || is_null($marker['W']) || is_null($marker['NW']))
      return;

    while (ceil($marker['E'][2]) < floor(($marker['NE'][2] + $marker['E'][2] + $marker['SE'][2]) / 3)) {
      $marker['E'][0]++;
      $marker['E'][2] = GetDistanceToCenter($marker['E'][0], $marker['E'][1], $this->Center[0], $this->Center[1]);
    }

    while (ceil($marker['W'][2]) < floor(($marker['NW'][2] + $marker['W'][2] + $marker['SW'][2]) / 3)) {
      $marker['W'][0]--;
      $marker['W'][2] = GetDistanceToCenter($marker['W'][0], $marker['W'][1], $this->Center[0], $this->Center[1]);
    }

    while (ceil($marker['N'][2]) < floor(($marker['NW'][2] + $marker['N'][2] + $marker['NE'][2]) / 3)) {
      $marker['N'][1]--;
      $marker['N'][2] = GetDistanceToCenter($marker['N'][0], $marker['N'][1], $this->Center[0], $this->Center[1]);
    }

    while (ceil($marker['S'][2]) < floor(($marker['SW'][2] + $marker['S'][2] + $marker['SE'][2]) / 3)) {
      $marker['S'][1]++;
      $marker['S'][2] = GetDistanceToCenter($marker['S'][0], $marker['S'][1], $this->Center[0], $this->Center[1]);
    }

    $center = array(
      'N2S' => array(
        'Dist' => $marker['S'][1] - $marker['N'][1],
      ),
      'W2E' => array(
        'Dist' => $marker['E'][0] - $marker['W'][0],
      )
    );

    $this->WorkingCopy->cropImage($center['W2E']['Dist'], $center['N2S']['Dist'], $marker['W'][0], $marker['N'][1]);
    $this->WorkingCopy->setImagePage(0,0,0,0);
    $this->WorkingCopy->writeImage(WORKING_DIR.$this->Filename.'-working.png');
  }

  function _GetRadialMarker(string $sDir) {
    $x = $this->_GetRadialStartpointX($sDir);
    $y = $this->_GetRadialStartpointY($sDir);
    $lastx = $x;
    $lasty = $y;
    while (true) {
      $fDist = GetDistanceToCenter($x, $y, $this->Center[0], $this->Center[1]);
      if ($fDist < 60)
        break;
      $aColor = $this->WorkingCopy->getImagePixelColor($x, $y)->getColor();
      if (IsGreen($aColor) || IsRed($aColor) || IsBlack($aColor)) {
        if ((!IsWhite($this->WorkingCopy->getImagePixelColor($lastx, $lasty)->getColor())
          || !IsWhite($this->WorkingCopy->getImagePixelColor($this->_GetRadialNextX($x, $sDir), $this->_GetRadialNextY($y, $sDir))->getColor()))
          || $fDist < 98)
          return [$x, $y, $fDist];
      }

      $lastx = $x;
      $lasty = $y;
      $x = $this->_GetRadialNextX($x, $sDir);
      $y = $this->_GetRadialNextY($y, $sDir);
    }
    return null;
  }

  function _GetRadialStartpointX(string $sDir) {
    switch ($sDir) {
      case 'N':
      case 'S':
        return $this->Center[0];
      case 'NE':
      case 'E':
      case 'SE':
        return $this->Wellsize[0];
      case 'SW':
      case 'W':
      case 'NW':
        return 0;
    }
  }

  function _GetRadialStartpointY(string $sDir) {
    switch ($sDir) {
      case 'NW':
      case 'N':
      case 'NE':
        return 0;
      case 'SE':
      case 'S':
      case 'SW':
        return $this->Wellsize[1];
      case 'E':
      case 'W':
        return $this->Center[1];
    }
  }

  function _GetRadialNextX(int $x, string $sDir) {
    switch ($sDir) {
      case 'N':
      case 'S':
        return $x;
      case 'NE':
      case 'E':
      case 'SE':
        return ($x -1);
      case 'SW':
      case 'W':
      case 'NW':
        return ($x + 1);
    }
  }

  function _GetRadialNextY(int $y, string $sDir) {
    switch ($sDir) {
      case 'NW':
      case 'N':
      case 'NE':
        return ($y + 1);
      case 'SE':
      case 'S':
      case 'SW':
        return ($y - 1);
      case 'E':
      case 'W':
        return $y;
    }
  }

  function CreateTensImage() {

    $iMgck = new Imagick();
    $iMgck->newImage($this->Wellsize[0], $this->Wellsize[1], new ImagickPixel('white'), "png");

    $iDraw = new ImagickDraw();

    for ($x=0; $x<$this->Wellsize[0]; $x++) {
      for ($y=0; $y<$this->Wellsize[1]; $y++) {
        $col = $this->Counter->GetTensColor($x, $y);
        if ($col != null) {
          $iDraw->setFillColor($col);
          $iDraw->color($x, $y, 0);
        }
      }
    }

    $iDraw->setFillColor('none');
    $iDraw->setStrokeWidth(1);
    $iDraw->setStrokeColor('#dddddd');
    $iDraw->setStrokeOpacity(0.5);
    for ($i=0; $i<9; $i++) {
      $rr = $this->Radius * ($i + 1) / 10;
      $iDraw->circle($this->Center[0], $this->Center[1], $this->Center[0] + $rr, $this->Center[1]);
    }
    $iDraw->setStrokeColor('#000000');
    $iDraw->setStrokeOpacity(1.0);
    $iDraw->circle($this->Center[0], $this->Center[1], $this->Center[0] + $this->Radius + 1, $this->Center[1]);
    $iDraw->line($this->Center[0], $this->Center[1] - $this->Radius, $this->Center[0], $this->Center[1] + $this->Radius);
    $iDraw->line($this->Center[0] - $this->Radius, $this->Center[1], $this->Center[0] + $this->Radius, $this->Center[1]);

    $iMgck->drawImage($iDraw);
    $iMgck->writeImage(WORKING_DIR.$this->Filename.'-10circle.png');
    $iDraw->destroy();
    $iMgck->clear();
    return $this->Filename.'-10circle.png';
  }

  function CreateTwinsImage() {

    $iMgck = new Imagick();
    $iMgck->newImage($this->Wellsize[0], $this->Wellsize[1], new ImagickPixel('white'), "png");

    $iDraw = new ImagickDraw();

    for ($x=0; $x<$this->Wellsize[0]; $x++) {
      for ($y=0; $y<$this->Wellsize[1]; $y++) {
        if (($dot = $this->Counter->GetPixelData($x, $y)) != null) {
          $iDraw->setFillColor(GetFillColor($this->Counter->Unified[$dot[3]][$dot[0]]));
          $iDraw->color($x, $y, 0);
        }
      }
    }

    $iDraw->setFillColor('none');
    $iDraw->setStrokeWidth(1);
    $iDraw->setStrokeColor('#000000');
    $iDraw->setStrokeOpacity(1.0);
    $iDraw->circle($this->Center[0], $this->Center[1], $this->Center[0] + $this->Radius + 1, $this->Center[1]);
    $iDraw->setStrokeColor('#DDDDDD');
    //$iDraw->circle($this->Center[0], $this->Center[1], $this->Center[0] + ($this->Radius * 0.6), $this->Center[1]);
    $iDraw->circle($this->Center[0], $this->Center[1], $this->Center[0] + ($this->Radius * ($this->Wellplate == 96 ? 0.7 : 0.8)), $this->Center[1]);
    $iDraw->line($this->Center[0], $this->Center[1] - $this->Radius, $this->Center[0], $this->Center[1] + $this->Radius);
    $iDraw->line($this->Center[0] - $this->Radius, $this->Center[1], $this->Center[0] + $this->Radius, $this->Center[1]);

    $iMgck->drawImage($iDraw);
    $iMgck->writeImage(WORKING_DIR.$this->Filename.'-2circle.png');
    $iDraw->destroy();
    $iMgck->clear();
    return $this->Filename.'-2circle.png';
  }

}
