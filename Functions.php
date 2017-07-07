<?php

function GetDistanceToCenter(int $x, int $y, int $xCenter, int $yCenter) { // distanz hochgerechnet auf einen radius von 100
  return (sqrt((abs($x - $xCenter) ** 2) + (abs($y - $yCenter) ** 2))) * 100 / $xCenter;
}

function GetFillColor(int $iValue) {
  switch($iValue) {
    case 0:
      return COL_EMPTY;
    case 1:
      return COL_SLOW;
    case 2:
      return COL_MIXED;
    case 3:
      return COL_FAST;
  }
  return null;
}

function IsBlack(array $rgba) {
  return ($rgba['r'] == 0 && $rgba['g'] == 0 && $rgba['b'] == 0 && $rgba['a'] == 1);
}

function IsBlue(array $rgba) {
  return ($rgba['r'] == 0 && $rgba['g'] == 0 && $rgba['b'] == 255 && $rgba['a'] == 1);
}

function IsGreen(array $rgba) {
  return ($rgba['r'] == 0 && $rgba['g'] == 255 && $rgba['b'] == 0 && $rgba['a'] == 1);
}

function IsRed(array $rgba) {
  return ($rgba['r'] == 255 && $rgba['g'] == 0 && $rgba['b'] == 0 && $rgba['a'] == 1);
}

function IsWhite(array $rgba) {
  return (($rgba['r'] == 255 && $rgba['g'] == 255 && $rgba['b'] == 255 && $rgba['a'] == 1)
    || ($rgba['r'] == 248 && $rgba['g'] == 248 && $rgba['b'] == 248 && $rgba['a'] == 1));
}
