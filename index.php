<?php

echo '<html><head></head><body><ol>';

$sPath = 'Data/';
$aFolders = array();

if ($handle = opendir($sPath)) {
  while (false !== ($sFilename = readdir($handle))) {

    $fc = strtolower(substr($sFilename, 0, 1));
    $sPathCombined = $sPath.$sFilename;

    if ($fc == '.' || $fc == '@' || $fc == '~')
      continue;

    if (!is_dir($sPathCombined))
      continue;

    array_push($aFolders, $sFilename);

  }
}

asort($aFolders);

for ($i=0; $i<count($aFolders); $i++) {
  echo '<li><a href="analyze.php?Folder='.urlencode($aFolders[$i]).'">'.$aFolders[$i].'</li>';
}

echo '</ol></body></html>';
