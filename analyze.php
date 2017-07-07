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
        copy('Work/'.str_replace('.PNG', '-working.png', $xData[$i]), $tarfolder.'Work/'.str_replace('.PNG', '-working.png', $xData[$i]));

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

}

function extractPicture($sFilename) {
  global $sPath, $sTemp;
  $sFile = rtrim($sPath, '/').'/'.$sFilename;

  $pic = new Pic($sFile);
  if (!$pic->Exists)
    return null;

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

}
