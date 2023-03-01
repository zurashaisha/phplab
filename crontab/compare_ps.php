<?php
require "/var/www/html/project/config.php";
require "/var/www/html/project/commands.php";

#$dev_bng=$conn->query("SELECT ip, devserial from devices where dev_role='BNG");
#foreach ($dev_bng as $bng_from_db) {
    #$current_bng=$bng_from_db["devserial"];
    $current_bng="JN126E1BBJCB";
    $ps_int_bng=$conn->query("SELECT ps_int, backup_bng from ps_int where bng='$current_bng'");
    foreach ($ps_int_bng as $each_ps_int) {
        $ps_subif_array = array();
        $backup_ps_subif_array = array();
        $backup_bng_serial=$each_ps_int["backup_bng"];
        $current_ps=$each_ps_int["ps_int"];
        $backup_bng_ip=long2ip($conn->query("SELECT ip from devices where devserial='$backup_bng_serial'")->fetch_assoc()["ip"]);
        $all_subif=$conn->query("SELECT * from user_interfaces where bng='$current_bng' and ifname like '$current_ps.%'");
        foreach ($all_subif as $each_ps_subif) {
            array_push($ps_subif_array, $each_ps_subif["ifname"]);
        }
        $backup_bng_command="show configuration interfaces $current_ps | display json";
        $backup_bng_ps=ssh_host_result($backup_bng_ip, $backup_bng_command);
        for ($d=0; $d<count($backup_bng_ps["configuration"]["interfaces"]["interface"][0]["unit"]); $d++) {
            if ($backup_bng_ps["configuration"]["interfaces"]["interface"][0]["unit"][$d]["name"] !=0) {
            $bps_subif=$current_ps.".".$backup_bng_ps["configuration"]["interfaces"]["interface"][0]["unit"][$d]["name"];
            array_push($backup_ps_subif_array, $bps_subif); }
        }
        for ($i=0; $i<count($ps_subif_array); $i++) {
            if (in_array($ps_subif_array[$i], $backup_ps_subif_array)) {
                #echo $ps_subif_array[$i]." has backup<br>";
            }
            else { echo $ps_subif_array[$i]." has no backup<br>"; }
        }

    }

#}


?>