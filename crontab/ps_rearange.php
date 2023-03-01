<?php
require "/var/www/html/project/config.php";
require "/var/www/html/project/commands.php";
echo "<pre>";
$all_204=$conn->query("SELECT DISTINCT mx204 from ps_int");
foreach ($all_204 as $mx204) {
    #echo $mx204["mx204"]."<br>";
$devserial = $mx204["mx204"];
#echo $devserial;
$dev_ip=long2ip($conn->query("SELECT ip from devices where devserial='$devserial'")->fetch_assoc()["ip"]);
#echo $devserial." ".$dev_ip."<br>";
$ps_data=ssh_host_result($dev_ip, "show l2circuit connections brief | display json");
$int_count=$ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"];
$ps_int = array();
#print_r($ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"]);
for ($i=0; $i<count($ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"]); $i++) {
    $bng_ip=$ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["neighbor-address"][0]["data"];
    #$ps_int[$i]["device"]=$devserial;
    for ($int_count=0; $int_count <= count($ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"]); $int_count++) {
        $interface = explode("(", $ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$int_count]["connection-id"][0]["data"]);
        $status=$ps_data["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$int_count]["connection-status"][0]["data"];
        #echo $interface. " ".$status." ".$bng_ip."<br>";
        $check_if_int_exist = $conn->query("SELECT mx204_int from ps_int where mx204='$devserial' and mx204_int='$interface[0]'")->fetch_assoc()["mx204_int"];
        if ($check_if_int_exist) {
        $ps_int[$i]["interfaces"][$int_count]["interface"] = $interface[0];
        $ps_int[$i]["interfaces"][$int_count]["BNG"] = $bng_ip;
        $bng_serial=get_device_serial_by_loip($ps_int[$i]["interfaces"][$int_count]["BNG"]);
        if ($status == "Up") {
            $ps_int[$i]["interfaces"][$int_count]["status"] = "Active";
            $replace_bng_active=$conn->query("UPDATE ps_int set bng='$bng_serial' where mx204='$devserial' and mx204_int='$interface[0]'");
            if ($replace_bng_active === TRUE) {
                ;
                } else {
                echo "Error: " . $replace_bng_active . "<br>" . $conn->error; }
            echo "Interface ".$interface[0]." on ".$devserial." has BNG active ".$bng_serial."<br>";
        }
        if ($status == "HS") {
            $ps_int[$i]["interfaces"][$int_count]["status"] = "Backup";
            $replace_bng_active=$conn->query("UPDATE ps_int set backup_bng='$bng_serial' where mx204='$devserial' and mx204_int='$interface[0]'");
            #echo "Interface ".$interface[0]." on ".$devserial." has BNG Backup ".$bng_serial."<br>";
        }
        }
        #$ps_int[$i]["interfaces"]=array_values($ps_int[$i]["interfaces"]);
        if (!$ps_int[$i]["interfaces"][$int_count]["interface"]) {
            unset($ps_int[$i]["interfaces"][$int_count]);
        }
    }
}
$ps_int=array_values($ps_int);
#print_r($ps_int);

}

?>