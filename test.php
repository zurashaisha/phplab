<?php
require "config.php";
require "commands.php";
require "header.php";
require "topbar.php";
require "sidebar.php";
$mydir = '/var/vsftp/mpls/ftp/Juniper/configs/TBS_74_MX10003_BNG_01'; 
  
  $myfiles = array_diff(scandir($mydir), array('.', '..')); 
  $myfiles = array_values($myfiles);
  print_r($myfiles);

  echo count($myfiles);
?>