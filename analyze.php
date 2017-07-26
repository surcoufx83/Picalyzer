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

require_once 'PlotGroup.php';
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

$bUseGroups = false;
$Groups = array();
if (array_key_exists('Groups', $_GET)) {
  $bUseGroups = true;
  CheckoutGroups($_GET['Groups']);
}

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
$csvfilename = 'rawdata_'.$now->format('Y-m-d_H-i-s').'.csv';
mkdir($tarfolder.'Work', 0777, true);
$workstart = time();

$csv = 'Experiment;SubExperiment;Description;Group;Plate-Y;Plate-X;ImageIndex;';
$csv .= 'Rating-X;Rating-Y;Rating-Z;Relative-X;Relative-Y;Relative-Z;';

$regs = ['TL', 'TR', 'BR', 'BL'];
$cols = ['White', 'Black', 'Green', 'Red', 'Rating'];
$csvdet = '';
for ($i=0; $i<10; $i++) {
  for ($j=0; $j<4; $j++) {
    for ($k=0; $k<5; $k++) {
      $csv .= 'Detail-'.$i.'-'.$regs[$j].'-'.$cols[$k].';';
    }
    if ($i<2)
      $csvdet .= 'Simple-'.$i.'-'.$regs[$j].'-Rating;';
  }
}
$csv .= $csvdet."\r\n";

$htmlout .= '<html><head></head><body>';
$htmlout .= '<p>Cached version of this file: <a href="'.$tarfolder.'result.html">'.$tarfolder.'result.html</a>.<br />';
$htmlout .= 'The raw data can be found in this file: <a href="Work/'.$csvfilename.'">Work/'.$csvfilename.'</a></p>';
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
      $htmlout .= '<td>';
      sort($xData);

      for ($i=0; $i<count($xData); $i++) {

        $pObj = loadPicture($xData[$i]);
        $pObj->CreateImages($_GET['Circles'], $tarfolder);
        $pObj->Counter->CalculateRating();

        $htmlout .= ($pObj->Group != null ? '<div style="font-weight:bold;">Group: '.$pObj->Group->Name.'</div>' : '');
        $htmlout .= '<div>'.$pObj->Basename.'</div>';
        $htmlout .= '<div style="white-space:nowrap; margin-top:10px;">';
        $htmlout .= '<img src="'.$pObj->FilenameOrigin.'" />';
        $htmlout .= '<img src="'.$pObj->FilenameWorking.'" />';
        $htmlout .= '<img src="'.$pObj->Filename10Circle.'" />';
        $htmlout .= '<img src="'.$pObj->Filename2Circle.'" /></div>';
        $htmlout .= '<div style="font-size:90%; margin-top:10px;">Rating XYZ: '.$pObj->Counter->X.', '.$pObj->Counter->Y.', '.$pObj->Counter->Z.'</div>';
        $htmlout .= '<div style="font-size:90%;">Relative XYZ: '.($pObj->Counter->X * 100).', '.($pObj->Counter->Y * 100).', '.($pObj->Counter->Z * 100).'</div>';
        $htmlout .= '<div style="font-size:90%;">Dots: '
          .($pObj->Counter->Unified[1]['NW'] + 1).($pObj->Counter->Unified[0]['NW'] + 1)
          .($pObj->Counter->Unified[1]['NE'] + 1).($pObj->Counter->Unified[0]['NE'] + 1)
          .($pObj->Counter->Unified[1]['SE'] + 1).($pObj->Counter->Unified[0]['SE'] + 1)
          .($pObj->Counter->Unified[1]['SW'] + 1).($pObj->Counter->Unified[0]['SW'] + 1).'</div>';

        $csv .= $pObj->GetRawData()."\r\n";

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

file_put_contents('Work/'.$csvfilename, $csv);
copy('Work/'.$csvfilename, $tarfolder.'Work/'.$csvfilename);

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

function loadPicture($sFilename) {
  global $sPath, $sTemp;
  $sFile = rtrim($sPath, '/').'/'.$sFilename;
  $pic = new Pic($sFile);
  if (!$pic->Exists)
    return null;
  return $pic;
}
