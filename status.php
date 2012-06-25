<?php

function nagmap_status() {
  include('config.php');
  $fp = fopen($nagios_status_dat_file,"r");
  $type = "";
  $data = Array();
  while (!feof($fp)) {
    $line = trim(fgets($fp));
    //ignore all commented lines - hop to the next itteration
    if (empty($line) OR ereg("^;", $line) OR ereg("^#", $line)) {
      continue;
    }
    //if end of definition, skip to next itteration
    if (ereg("}",$line)) {
      $type = "0";
      unset($host);
      continue;
    }
    if (ereg("^hoststatus {", $line)) {
      $type = "hoststatus";
    };
    if (ereg("^servicestatus {", $line)) {
      $type = "servicestatus";
    };
    if(!ereg("}",$line) && ($type == "hoststatus" | $type == "servicestatus")) {
      $line = trim($line);
      $pieces = explode("=", $line, 2);
      //do not bother with invalid data
      if (count($pieces)<2) { continue; };
      $option = trim($pieces[0]);
      $value = trim($pieces[1]);
      if (($option == "host_name")) {
        $host = $value;
      }
      if (!isset($data[$host][$type][$option])) {
        $data[$host][$type][$option] = "";
      }
      if (!isset($data[$host]['servicestatus']['last_hard_state'])) {
        $data[$host]['servicestatus']['last_hard_state'] = "";
      }
      if ($option == "last_hard_state") {
        if ($value >= $data[$host][$type][$option]) {
          $data[$host][$type][$option] = $value;
        }
        if (($data[$host]['hoststatus']['last_hard_state'] == 0) && ($data[$host]['servicestatus']['last_hard_state'] == 0)) {
          $data[$host]['status'] = 0;
          $data[$host]['status_human'] = 'OK';
        } elseif (($data[$host]['hoststatus']['last_hard_state'] == 2) | ($data[$host]['servicestatus']['last_hard_state'] == 1)) {
          $data[$host]['status'] = 1;
          $data[$host]['status_human'] = 'WARNING / UNREACHABLE';
        } elseif (($data[$host]['hoststatus']['last_hard_state'] == 1) | ($data[$host]['servicestatus']['last_hard_state'] == 2)) {
          $data[$host]['status'] = 2;
          $data[$host]['status_human'] = 'CRITICAL / DOWN';
        } else {
          $data[$host]['status'] = 3;
          $data[$host]['status_human'] = 'UNKNOWN - NagMap bug!';
        }
      } 
    }
  }
  return $data;
}

?>
