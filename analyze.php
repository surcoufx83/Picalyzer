<?php

if (!array_key_exists('Folder', $_GET)) {
  header('Location:index.php');
  exit;
}

define('COL_EMPTY', '#FFFFFF');
define('COL_SLOW', '#AAAAAA');
define('COL_MIXED', '#555555');
define('COL_FAST', '#000000');
define('DEBUG', (array_key_exists('DevInfo', $_GET)));

require_once 'Functions.php';
require_once 'Counter.php';
require_once 'Picture.php';

$sPath = 'Data/'.$_GET['Folder'];
$sTemp = 'Work/';
if (!is_dir($sPath)) {
  header('Location:index.php');
  exit;
}

if (!array_key_exists('Circles', $_GET))
  $_GET['Circles'] = 12;

$aExperiments = array();
if ($handle = opendir($sPath)) {
  while (false !== ($sFilename = readdir($handle))) {

    $fc = strtolower(substr($sFilename, 0, 1));
    $sPathCombined = $sPath.$sFilename;

    if ($fc == '.' || $fc == '@' || $fc == '~')
      continue;

    if (is_dir($sPathCombined))
      continue;

    // test 1 - 12 well-plate, 4mm high vel, 1mm low vel, thresh 30, 12 wells_A01_1.PNG
    if (preg_match('/(?<Experiment>Test [0-9]+).*_(?<Y>[A-Z])(?<X>[0-9]{2})_(?<Image>[0-9]+)\.PNG$/i', $sFilename, $aPregOut)) {
      addPictureToArray($aPregOut);
    }

  }
}

$htmlout = '';
$now = new DateTime();
$tarfolder = '__Olds/'.$now->format('Y-m-d_H-i-s').'/';
mkdir($tarfolder.'Work', 0777, true);
$workstart = time();
$htmlout .= '<html><head></head><body>';
$htmlout .= '<p>Cached version of this file: <a href="'.$tarfolder.'result.html">'.$tarfolder.'result.html</a></p>';
$htmlout .= '<p style="font-size:80%;">Page and images created in [duration].</p>';

ksort($aExperiments);
foreach ($aExperiments AS $sExperimentName => $aData) {
  $htmlout .= '<h1>'.$sExperimentName.'</h1>';
  $htmlout .= '<table border="1">
    <tr>
      <th colspan="'.($aData['Width'] + 1).'">used wells: '.$aData['Height'].' * '.$aData['Width'].' = '.($aData['Width'] * $aData['Height']).'</th>
    </tr>';

  ksort($aData['Animals']);
  foreach ($aData['Animals'] AS $y => $yData) {
    ksort($yData);
    $htmlout .= '<tr><th>'.$y.'</th>';

    foreach ($yData AS $x => $xData) {
      $htmlout .= '<td>'.$x.'<br />';
      sort($xData);

      for ($i=0; $i<count($xData); $i++) {
        if (count($xData) != 1)
          $htmlout .= '<h3>'.$xData[$i].'</h3><br />';

        $sp = extractPicture($xData[$i]);
        $htmlout .= '<div style="white-space:nowrap;">';
        $htmlout .= '<img src="Work/'.$xData[$i].'" />';
        $htmlout .= '<img src="Work/'.str_replace('.PNG', '-working.png', $xData[$i]).'" />';
        copy('Work/'.$xData[$i], $tarfolder.'Work/'.$xData[$i]);

        if (is_array($sp)) {
          for ($im = 0; $im<count($sp); $im++) {
            $htmlout .= '<img src="Work/'.$sp[$im].'"/>';
            copy('Work/'.$sp[$im], $tarfolder.'Work/'.$sp[$im]);
          }
        }
        $htmlout .= '</div><br />';
      }
      $htmlout .= '</td>';
    }
    $htmlout .= '</tr>';
  }

  $htmlout .= '</table>';
  //break;
}
$htmlout .= '</body></html>';
$dur = time() - $workstart;
$sDur = '';
if ($dur >= 3600) {
  $calc = floor($dur / 3600);
  $sDur = $calc.'h ';
  $dur -= ($calc * 3600);
}
if ($sDur != '' || $dur >= 60) {
  $calc = floor($dur / 60);
  if ($sDur != '' && $calc < 10)
    $sDur .= '0';
  $sDur .= $calc.'m ';
  $dur -= ($calc * 60);
}
if ($dur < 10 && $sDur != '')
  $sDur .= '0';
$sDur .= $dur.'s';
$htmlout = str_replace('[duration]', $sDur, $htmlout);
echo $htmlout;
file_put_contents($tarfolder.'result.html', $htmlout);


function addPictureToArray($aPregOut) {
  global $aExperiments;
  if (!array_key_exists($aPregOut['Experiment'], $aExperiments)) {
    $aExperiments[$aPregOut['Experiment']] = array(
      'Animals' => array(),
      'Width' => 0,
      'Height' => 0,
    );
  }

  if (!array_key_exists($aPregOut['Y'], $aExperiments[$aPregOut['Experiment']]['Animals'])) {
    $aExperiments[$aPregOut['Experiment']]['Animals'][$aPregOut['Y']] = array();
    $aExperiments[$aPregOut['Experiment']]['Height']++;
  }

  $aPregOut['X'] = intval($aPregOut['X']);
  if (!array_key_exists($aPregOut['X'], $aExperiments[$aPregOut['Experiment']]['Animals'][$aPregOut['Y']])) {
    $aExperiments[$aPregOut['Experiment']]['Animals'][$aPregOut['Y']][$aPregOut['X']] = array();
    if ($aPregOut['X'] > $aExperiments[$aPregOut['Experiment']]['Width'])
    $aExperiments[$aPregOut['Experiment']]['Width'] = $aPregOut['X'];
  }

  array_push($aExperiments[$aPregOut['Experiment']]['Animals'][$aPregOut['Y']][$aPregOut['X']], $aPregOut[0]);
  /*$aPregOut['Image'] = intval($aPregOut['Image']);
  if (!array_key_exists($aPregOut['Image'], $aExperiments[$aPregOut['Experiment']]['Animals'][$aPregOut['Y']][$aPregOut['X']]))
    $aExperiments[$aPregOut['Experiment']]['Animals'][$aPregOut['Y']][$aPregOut['X']][$aPregOut['Image']] = $aPregOut[0];*/

}

function extractPicture($sFilename) {
  global $sPath, $sTemp;
  $sFile = rtrim($sPath, '/').'/'.$sFilename;

  $pic = new Pic($sFile);
  if (!$pic->Exists)
    return null;

  #$aInfo = getimagesize($sFile);
  #$rImg = imagecreatefrompng($sFile);
  #$cRed = imagecolorallocate($rImg, 255, 0, 0);
  #$cGreen = imagecolorallocate($rImg, 0, 255, 0);
  #$cBlack = imagecolorallocate($rImg, 0, 0, 0);
  #$cWhite = imagecolorallocate($rImg, 248, 248, 248);

  /*$minx = 99999;
  $miny = 99999;
  $maxx = 0;
  $maxy = 0;
  for ($x = 0; $x < $aInfo[0]; $x++) {
    if ($x > ($minx + 255)) break;
    for ($y = 0; $y < $aInfo[1]; $y++) {
      $cDot = imagecolorat($rImg, $x, $y);
      if ($cDot == $cGreen || $cDot == $cRed) {
        if ($x < $minx) $minx = $x;
        if ($x > $maxx) $maxx = $x;
        if ($y < $miny) $miny = $y;
        if ($y > $maxy) $maxy = $y;
      }
    }
  }

  $rTemp = imagecrop($rImg, ['x' => $minx, 'y' => $miny, 'width' => ($maxx - $minx + 1), 'height' => ($maxy - $miny + 1)]);
  $rOut = null;*/
  #if ($rTemp !== false) {
    #imagedestroy($rImg);
    #$rImg = $rTemp;

    #$width = imagesx($rImg);
    #$height = imagesy($rImg);
    #$rad = $width / 2;
    #$center = array(
  #    'x' => $width / 2,
  #    'y' => $height / 2,
  #  );
    /*$pixelzaehler = new Counter();
    $pixelzaehler = array(
      'rad' => array(  # [green, red, white, black]
        0 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), #  0 ~  9
        1 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 10 ~ 19
        2 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 20 ~ 29
        3 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 30 ~ 39
        4 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 40 ~ 49
        5 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 50 ~ 59
        6 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 60 ~ 69
        7 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 70 ~ 79
        8 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 80 ~ 89
        9 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ), # 90 ~ 99
      ),
      '2circle' => array(  # [green, red, white, black]
        0 => array( #  0 ~ 65
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ),
        1 => array( # 66 ~ 99
          'nw' => [0, 0, 0, 0],
          'ne' => [0, 0, 0, 0],
          'se' => [0, 0, 0, 0],
          'sw' => [0, 0, 0, 0],
          'c' => [0, 0, 0, 0],
        ),
      ),
      'unknown' => array(),
    );
    imageellipse($rImg, $center['x'], $center['y'], $width, $height, $cBlack);
    imageellipse($rImg, $center['x'], $center['y'], $width - 1, $height - 1, $cBlack);
    imageellipse($rImg, $center['x'], $center['y'], $width - 2, $height - 2, $cBlack);
*
    for ($y = 0; $y < $height; $y++) {
      for ($x = 0; $x < $width; $x++) {
        /*$cDot = imagecolorat($rImg, $x, $y);
        $x0 = $x - $center['x'];
        $y0 = $y - $center['y'];
        $fDist = (sqrt((abs($x0) ** 2) + (abs($y0) ** 2))) * 100 / $rad;
        $iDist = intval(floor($fDist / 10));
*
        if ($fDist > 99) {
          imagesetpixel($rImg, $x, $y, $cBlack);
          continue;
        }*
        $tcp = ($fDist < 66 ? 0 : 1);
        $tcd = '';
        if ($x0 < 0 && $y0 < 0) $tcd = 'nw';
        elseif ($x0 > 0 && $y0 < 0) $tcd = 'ne';
        elseif ($x0 > 0 && $y0 > 0) $tcd = 'se';
        elseif ($x0 < 0 && $y0 > 0) $tcd = 'sw';
        else $tcd = 'c';

        if ($cDot == $cGreen) {
          $pixelzaehler['rad'][$iDist][$tcd][0]++;
          $pixelzaehler['2circle'][$tcp][$tcd][0]++;
        } elseif ($cDot == $cRed) {
          $pixelzaehler['rad'][$iDist][$tcd][1]++;
          $pixelzaehler['2circle'][$tcp][$tcd][1]++;
        } elseif ($cDot == $cWhite) {
          $pixelzaehler['rad'][$iDist][$tcd][2]++;
          $pixelzaehler['2circle'][$tcp][$tcd][2]++;
        } elseif ($cDot == $cBlack) {
          $pixelzaehler['rad'][$iDist][$tcd][3]++;
          $pixelzaehler['2circle'][$tcp][$tcd][3]++;
        } else {
          if (!array_key_exists($cDot, $pixelzaehler['unknown']))
            $pixelzaehler['unknown'][$cDot] = imagecolorsforindex($rImg, $cDot);
        }
      }
    }

    $pixelzaehler = calculateValues($pixelzaehler);
var_dump($pixelzaehler['rad']);
exit;*/
  $sOut = array();
  if ($_GET['Circles'] == 2) {
    $sOut[] = $pic->CreateTwinsImage();
  } elseif ($_GET['Circles'] == 10) {
    $sOut[] = $pic->CreateTensImage();
  } else {
    $sOut[] = $pic->CreateTensImage();
    $sOut[] = $pic->CreateTwinsImage();
  }

  return $sOut;
  //var_dump($pic);
  //exit;

    $iMgck = $iM2c = null;
    if ($_GET['Circles'] == 10 || $_GET['Circles'] == 12) {

      $iMgck = new Imagick();
      $iMgck->newImage($width, $height, new ImagickPixel("white"), "png");
      $iDraw = new ImagickDraw();
      $iDraw->setStrokeWidth(1);
      $iDraw->setStrokeColor('#dddddd');
      $iDraw->setStrokeOpacity(0.5);
      $iDraw->setFillColor('none');

      for ($r=0; $r<10; $r++) {
        $rr = $rad * ($r + 1) / 10;
        $iDraw->circle($center['x'], $center['y'], $center['x'] + $rr, $center['y']);
      }

      $iDraw->circle($center['x'], $center['y'], $center['x'] + $rad, $center['y']);
      $iDraw->line($center['x'], $center['y'] - $rad, $center['x'], $center['y'] + $rad);
      $iDraw->line($center['x'] - $rad, $center['y'], $center['x'] + $rad, $center['y']);
      $iMgck->drawImage($iDraw);
      $iDraw->destroy();

      for ($r = 9; $r >= 0; $r--) {

        //$obj =

        $pixelzaehler['rad'][$r]['slow'] = $pixelzaehler['rad'][$r][0];
        $pixelzaehler['rad'][$r]['fast'] = $pixelzaehler['rad'][$r][1];
        $pixelzaehler['rad'][$r]['empty'] = $pixelzaehler['rad'][$r][2];
        $pixelzaehler['rad'][$r]['inactive'] = $pixelzaehler['rad'][$r][3];
        $pixelzaehler['rad'][$r]['totalpixel'] = $pixelzaehler['rad'][$r][0] + $pixelzaehler['rad'][$r][1] + $pixelzaehler['rad'][$r][2] + $pixelzaehler['rad'][$r][3];
        $pixelzaehler['rad'][$r]['nonempty'] = $pixelzaehler['rad'][$r][0] + $pixelzaehler['rad'][$r][1] + $pixelzaehler['rad'][$r][3];
        $pixelzaehler['rad'][$r]['rad'] = $rad * ($r + 1) / 10;
        $pixelzaehler['rad'][$r]['opac'] = 1.0 - ($pixelzaehler['rad'][$r]['empty'] / $pixelzaehler['rad'][$r]['totalpixel']);

        if ($pixelzaehler['rad'][$r]['nonempty'] == 0 ||
          $pixelzaehler['rad'][$r]['inactive'] > ($pixelzaehler['rad'][$r]['slow'] + $pixelzaehler['rad'][$r]['fast']) ||
          $pixelzaehler['rad'][$r]['empty'] > ($pixelzaehler['rad'][$r]['slow'] + $pixelzaehler['rad'][$r]['fast'])) {
          $rr = $gg = $bb = 'FF';
        } else {
          /*if ($pixelzaehler['rad'][$r]['fast'] > $pixelzaehler['rad'][$r]['slow']) {
            $rr = 'ff';
            $gg = '00';
          } else {
            $rr = '00';
            $gg = 'ff';
          }
          $rr = substr('00'.dechex(ceil($pixelzaehler['rad'][$r]['fast'] / $pixelzaehler['rad'][$r]['nonempty'] * 255)), -2);
          $gg = substr('00'.dechex(ceil($pixelzaehler['rad'][$r]['slow'] / $pixelzaehler['rad'][$r]['nonempty'] * 255)), -2);
          $bb = '00';*/

          $mpr = $pixelzaehler['rad'][$r]['nonempty'] / 3;
          if ($pixelzaehler['rad'][$r]['slow'] > $mpr && $pixelzaehler['rad'][$r]['fast'] > $mpr)
            $rr = $gg = $bb = '55';
          elseif ($pixelzaehler['rad'][$r]['slow'] > $pixelzaehler['rad'][$r]['fast']) {
            $rr = $gg = $bb = 'AA';
            /*$rr = '00';
            $gg = '80';
            $bb = '00';*/
          }
          else{
            $rr = $gg = $bb = '00';
            /*$rr = '80';
            $gg = '00';
            $bb = '00';*/
          }
        }

        /*if ($pixelzaehler['rad'][$r]['opac'] < 0.1)
          $pixelzaehler['rad'][$r]['opac'] = 0.0;
        else if ($pixelzaehler['rad'][$r]['opac'] <= 0.33)
          $pixelzaehler['rad'][$r]['opac'] = 0.33;
        else if ($pixelzaehler['rad'][$r]['opac'] <= 0.66)
          $pixelzaehler['rad'][$r]['opac'] = 0.66;
        else*/
          $pixelzaehler['rad'][$r]['opac'] = 1.0;

        $pixelzaehler['rad'][$r]['rgba'] = '#'.substr('00'.dechex(ceil($pixelzaehler['rad'][$r]['opac'] * 255)), -2).$rr.$gg.$bb;

        /*$iDraw = new ImagickDraw();
        $iDraw->setStrokeWidth(0);
        $iDraw->setFillColor('#ffffff');
        $iDraw->setFillOpacity(1.0);
        $iDraw->circle($center['x'], $center['y'], $center['x'] + $pixelzaehler['rad'][$r]['rad'], $center['y']);
        $iMgck->drawImage($iDraw);
        $iDraw->destroy();

        $iDraw = new ImagickDraw();
        $iDraw->setStrokeWidth(0);
        $iDraw->setFillColor('#'.$rr.$gg.$bb);
        $iDraw->setFillOpacity($pixelzaehler['rad'][$r]['opac']);
        $iDraw->circle($center['x'], $center['y'], $center['x'] + $pixelzaehler['rad'][$r]['rad'], $center['y']);
        $iMgck->drawImage($iDraw);
        $iDraw->destroy();

        $iDraw = new ImagickDraw();
        $iDraw->setStrokeWidth(1);
        $iDraw->setStrokeColor('#dddddd');
        $iDraw->setStrokeOpacity(0.5);
        $iDraw->setFillColor('none');
        $iDraw->circle($center['x'], $center['y'], $center['x'] + $pixelzaehler['rad'][$r]['rad'], $center['y']);
        $iMgck->drawImage($iDraw);
        $iDraw->destroy();*/
      }

    }
    if ($_GET['Circles'] == 2 || $_GET['Circles'] == 12) {

      $iM2c = new Imagick();
      $iM2c->newImage($width, $height, new ImagickPixel("white"), "png");

      $iDraw = new ImagickDraw();
      $iDraw->setStrokeWidth(1);
      $iDraw->setStrokeColor('#000000');
      $iDraw->setFillColor('none');
      $iDraw->circle($center['x'], $center['y'], $center['x'] + $rad, $center['y']);
      $iDraw->circle($center['x'], $center['y'], $center['x'] + ($rad / 1.5), $center['y']);
      $iDraw->line($center['x'], $center['y'] - $rad, $center['x'], $center['y'] + $rad);
      $iDraw->line($center['x'] - $rad, $center['y'], $center['x'] + $rad, $center['y']);

      // inner
      $in =& $pixelzaehler['2circle'][0];
      $x = $center['x'] - 2;
      $y = $center['y'] - ($rad / 2.5);
      $iDraw->setStrokeWidth(0);
      $iDraw->setFillColor('#'.$in['nw']['r'].$in['nw']['g'].$in['nw']['b']);
      $iDraw->setFillOpacity($in['nw']['opac']);
      $iDraw->color($x, $y, 3);

      $x = $center['x'] + 2;
      $iDraw->setFillColor('#'.$in['ne']['r'].$in['ne']['g'].$in['ne']['b']);
      $iDraw->setFillOpacity($in['ne']['opac']);
      $iDraw->color($x, $y, 3);

      $y = $center['y'] + ($rad / 2.5);
      $iDraw->setFillColor('#'.$in['se']['r'].$in['se']['g'].$in['se']['b']);
      $iDraw->setFillOpacity($in['se']['opac']);
      $iDraw->color($x, $y, 3);

      $x = $center['x'] - 2;
      $iDraw->setFillColor('#'.$in['sw']['r'].$in['sw']['g'].$in['sw']['b']);
      $iDraw->setFillOpacity($in['sw']['opac']);
      $iDraw->color($x, $y, 3);

      // outer
      $in =& $pixelzaehler['2circle'][1];
      $x = $center['x'] - 2;
      $y = $center['y'] - ($rad / 1.25);
      $iDraw->setStrokeWidth(0);
      $iDraw->setFillColor('#'.$in['nw']['r'].$in['nw']['g'].$in['nw']['b']);
      $iDraw->setFillOpacity($in['nw']['opac']);
      $iDraw->color($x, $y, 3);

      $x = $center['x'] + 2;
      $iDraw->setFillColor('#'.$in['ne']['r'].$in['ne']['g'].$in['ne']['b']);
      $iDraw->setFillOpacity($in['ne']['opac']);
      $iDraw->color($x, $y, 3);

      $y = $center['y'] + ($rad / 1.25);
      $iDraw->setFillColor('#'.$in['se']['r'].$in['se']['g'].$in['se']['b']);
      $iDraw->setFillOpacity($in['se']['opac']);
      $iDraw->color($x, $y, 3);

      $x = $center['x'] - 2;
      $iDraw->setFillColor('#'.$in['sw']['r'].$in['sw']['g'].$in['sw']['b']);
      $iDraw->setFillOpacity($in['sw']['opac']);
      $iDraw->color($x, $y, 3);

      $iM2c->drawImage($iDraw);
      $iDraw->destroy();

    }

    //var_dump($pixelzaehler['2circle']);

  #}

  $cm = imagecolorallocate($rImg, 255, 0, 255);
  imagepng($rImg, $sTemp.$sFilename);
  $sOut = array();
  if ($iMgck != null) {
    $iMgck->writeImage($sTemp.$sFilename.'-out-10.png');
    $sOut[] = $sFilename.'-out-10.png';
  }
  if ($iM2c != null) {
    $iM2c->writeImage($sTemp.$sFilename.'-out-2.png');
    $sOut[] = $sFilename.'-out-2.png';
  }

  return $sOut;

}

/*function calculateValues($obj) {
  foreach ($obj AS $skind => $aObj) {
    for ($r=0; $r<count($aObj); $r++) {
      foreach ($aObj[$r] AS $key => $bObj) {
        $bObj['slow'] = $bObj[0];
        $bObj['fast'] = $bObj[1];
        $bObj['empty'] = $bObj[2];
        $bObj['inactive'] = $bObj[3];
        $bObj['totalpixel'] = $bObj[0] + $bObj[1] + $bObj[2] + $bObj[3];
        $bObj['nonempty'] = $bObj[0] + $bObj[1] + $bObj[3];
        $bObj['opac'] = ($bObj['totalpixel'] == 0 ? 1 : $bObj['nonempty'] / $bObj['totalpixel']);

        if ($bObj['nonempty'] == 0 ||
          $bObj['inactive'] > ($bObj['slow'] + $bObj['fast']) ||
          $bObj['empty'] > ($bObj['slow'] + $bObj['fast'])) {
          $bObj['r'] = $bObj['g'] = $bObj['b'] = 'FF';
        } else {
          $mpr = $bObj['nonempty'] / 3;
          if ($bObj['slow'] > $mpr && $bObj['fast'] > $mpr) {
            $bObj['r'] = $bObj['g'] = $bObj['b'] = '55';
          }
          elseif ($bObj['slow'] > $bObj['fast']) {
            $bObj['r'] = $bObj['g'] = $bObj['b'] = 'AA';
          }
          else {
            $bObj['r'] = $bObj['g'] = $bObj['b'] = '00';
          }
        }
        $bObj['opac'] = 1.0;
        $bObj['rgba'] = '#'.substr('00'.dechex(ceil($bObj['opac'] * 255)), -2).$bObj['r'].$bObj['g'].$bObj['b'];
      }
    }
  }
  return $obj;
}*/
