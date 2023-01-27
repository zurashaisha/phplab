<?php
require "/var/www/html/project/config.php";
require "/var/www/html/project/commands.php";
echo "<pre>";
$all_204=$conn->query("SELECT DISTINCT mx204 from ps_int");
foreach ($all_204 as $mx204) {
    #echo $mx204["mx204"]."<br>";
$devserial = $mx204["mx204"];
$dev_ip=long2ip($conn->query("SELECT ip from devices where devserial='$devserial'")->fetch_assoc()["ip"]);
#echo $devserial." ".$dev_ip."<br>";
$ps_data=ssh_host_result($dev_ip, "show l2circuit connections brief | display json");
$int_count=$ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"];
$ps_int = array();
#print_r($ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"]);
for ($i=0; $i<count($ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"]); $i++) {
    $bng_ip=$ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["neighbor-address"][0]["data"];
    #$ps_int[$i]["ip"]=$bng_ip;
    for ($int_count=0; $int_count <= count($ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"]); $int_count++) {
        $interface = explode("(", $ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$int_count]["connection-id"][0]["data"]);
        $status=$ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$int_count]["connection-status"][0]["data"];
        #echo $interface. " ".$status." ".$bng_ip."<br>";
        $ps_int[$i]["interfaces"][$int_count]["interface"] = $interface[0];
        $ps_int[$i]["interfaces"][$int_count]["BNG"] = $bng_ip;
        $ps_int[$i]["interfaces"][$int_count]["status"] = $status;
        if (!$ps_int[$i]["interfaces"][$int_count]["interface"]) {
            unset($ps_int[$i]["interfaces"][$int_count]);
        }
    }
}
print_r($ps_int);
for ($d=0; $d<count($ps_int); $d++) {
    for ($t=0; $t<count($ps_int[$d]["interfaces"]); $t++) {
        $interface = $ps_int[$d]["interfaces"][$t]["interface"];
        $bng = get_device_serial_by_loip($ps_int[$d]["interfaces"][$t]["BNG"]);
        $status=$ps_int[$d]["interfaces"][$t]["status"];
        echo $interface." ".$bng."<br>";
        $insert=$conn->query("INSERT into agg_l2c_rearange (mx204, interface, bng, vc_status) VALUES ('$devserial', '$interface', '$bng', '$status')");
        if ($insert === TRUE) {
            ;
            } else {
            echo "Error: " . $insert . "<br>" . $conn->error; }
    }
}
}

?>