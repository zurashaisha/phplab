<?php
require "/var/www/html/project/config.php";
require "/var/www/html/project/commands.php";

function check_int_on_bng($ifindex, $ps_int, $bng_ip) {
    $a = snmprealwalk($bng_ip, "@JuN1p3R", "1.3.6.1.2.1.2.2.1.2".$ifindex);
    $a->oid_output_format = SNMP_OID_OUTPUT_FULL;
}

$get_bng_devices=$conn->query("SELECT * from devices where dev_role='BNG'");
foreach ($get_bng_devices as $bng_dev) {
    $ps_int_array=array();
    $bng_ip=long2ip($bng_dev["ip"]);
    $bng_serial=$bng_dev["devserial"];
    if ($conn->query("SELECT ps_int from ps_int where bng='$bng_serial'")->num_rows > 0) {
        $get_ps_int=$conn->query("SELECT ps_int from ps_int where bng='$bng_serial'");
        foreach ($get_ps_int as $ps_int_bng) {
            array_push($ps_int_array, $ps_int_bng["ps_int"]);
        }
    $select_int_drom_db = $conn->query("SELECT ifindex, ifname from user_interfaces where BNG='$bng_serial'");
    foreach ($select_int_drom_db as $user_int_from_db) {

        $oid="1.3.6.1.2.1.2.2.1.2.".$user_int_from_db["ifindex"];
        $ifname=$user_int_from_db["ifname"];
        $snmp_check=snmprealwalk($bng_ip, "@JuN1p3R", $oid);
        if (!$snmp_check) {
            echo $oid." ".$bng_ip." Interface ".$ifname." not found<br>";
            $remove_row=$conn->query("DELETE from user_interfaces where ifname='$ifname' and BNG='$bng_serial'");
            if ($remove_row === TRUE) {
                echo "delete completed<br>";
            }
            else { echo "Error ".$conn->error; }
        }
        else if ($snmp_check) {
            $ps_int_new_desc_oid = "1.3.6.1.2.1.31.1.1.1.18.".$user_int_from_db["ifindex"];
            $snmp_new_int_desc = snmprealwalk($bng_ip, "@JuN1p3R", $ps_int_new_desc_oid);
            foreach ($snmp_new_int_desc as $snmp_ps_desc) {
                $db_edit_desc = $conn->query("UPDATE user_interfaces set ifdesc ='$snmp_ps_desc' where ifname='$ifname' and BNG='$bng_serial'");
            }
        }
    }
    

$new_int_list = snmprealwalk($bng_ip, "@JuN1p3R", "1.3.6.1.2.1.2.2.1.2");
$new_int_list->oid_output_format = SNMP_OID_OUTPUT_FULL;
foreach ($new_int_list as $intoid=>$snmp_int) {
    if (in_array(explode(".", $snmp_int)[0], $ps_int_array)) {

  if (preg_match('/^(ps)([0-9\.\/]+)$/',$snmp_int) && (!preg_match('/^ps\d+\.\d$/',$snmp_int)) && (!preg_match('/^ps\d+$/',$snmp_int)) && (!preg_match('/^ps\d+\.\d{3}$/',$snmp_int)) && (!preg_match('/\b32767\b/',$snmp_int)))
{
  $ifoidsplit=explode(".", $intoid);
$ifindex= $ifoidsplit[count($ifoidsplit)-1];
if (!$snmp_int==$conn->query("SELECT ifname from user_interfaces where ifname='$snmp_int' and BNG='$bng_serial'")->fetch_assoc()["ifname"])
{
    $int_desc = snmprealwalk($bng_ip, "@JuN1p3R", "1.3.6.1.2.1.31.1.1.1.18.".$ifindex);
    foreach ($int_desc as $ps_int_desc)
    
   # echo "New Interface ".$snmp_int." in ".$bng_ip." " .$intoid." ".$ps_int_desc."<br>";
    $ps_int_det_command="show configuration interfaces ".$snmp_int." | display json";
    $ps_int_det=ssh_host_result($bng_ip, $ps_int_det_command)["configuration"]["interfaces"]["interface"][0]["unit"];
    $ps_outer_vlan=$ps_int_det[0]["vlan-tags"]["outer"];
    $ps_inner_vlan=$ps_int_det[0]["vlan-tags"]["inner"];
    $insert_subif="INSERT into user_interfaces (ifindex, BNG, ifname, ifdesc, outer_vlan, inner_vlan) VALUES ($ifindex, '$bng_serial', '$snmp_int', '$ps_int_desc', '$ps_outer_vlan', '$ps_inner_vlan')";
    if ($conn->query($insert_subif) === TRUE) {
      ;
      } else {
      echo "Error: " . $insert_subif . "<br>" . $conn->error; }
}

}
 } }
    }
}





?>