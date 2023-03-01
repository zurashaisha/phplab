<?php
require "/var/www/html/project/config.php";
require "/var/www/html/project/commands.php";
$serial='CF891';
$date=date('mdY', time());

$device=$conn->query("SELECT dev_name, devserial, ip from devices where vendor='Juniper'");
foreach ($device as $dev_det) {
    echo $dev_det["dev_name"]." ".$dev_det["ip"]."<br>";
    $dirname="/var/vsftp/mpls/ftp/Juniper/configs/".$dev_det["dev_name"];
    if (!file_exists($dirname)) {
        mkdir($dirname, 0777, true);
        chown($dirname, 'mpls');
    }
    $command="file copy /config/juniper.conf.gz ftp://mpls:Silknet#1@172.16.4.102/Juniper/configs/".$dev_det["dev_name"]."/".$dev_det["dev_name"]."-".$date."-config.gz routing-instance MGMT";
    echo $command;
    $upload = ssh_host_result_nojson(long2ip($dev_det["ip"]), $command);
    chmod($dirname."/".$dev_det["dev_name"]."-".$date."-config.gz", 0775);
}


/*

  $mydir = '/var/vsftp/mpls/ftp/Juniper'; 
  
  $myfiles = array_diff(scandir($mydir), array('.', '..')); 
  
  print_r($myfiles); 
  */
?>

