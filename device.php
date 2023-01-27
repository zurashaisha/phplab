<?php
require "config.php";
require "commands.php";
require "header.php";
require "topbar.php";
require "sidebar.php";
#require "/var/www/html/project/src/ps_int_data.php";
echo "

<style>
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 50%;
}

#customers td, #customers th {
  border: 1px solid #ddd;
  padding: 8px;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
  text-align: left;
  background-color: #04AA6D;
  color: white;
}
.generalclass {
  width: 100%;
  text-align: center;
  margin-top: 20px;
  display: none;
}

.button{
	background: lightblue;
    padding: 10px;
    border-radius: 5px;
    
    
    border: none;   
    
}

.button:hover{
	background: grey;    
}
</style>";

function lldp_dev($data) {
    global $conn;
    $get_data = "SELECT dev_name from devices where devserial='$data'";
    $get_data_proc=$conn->query($get_data);
    foreach ($get_data_proc as $get_dev_data) 
    return $get_dev_data["dev_name"];
}
function lldp_int($id, $index) {
    global $conn;
    $intget_data = "SELECT ifname from interfaces where device='$id' and ifindex='$index'";
    $intget_data_proc = $conn->query($intget_data);
    foreach ($intget_data_proc as $intget_dev_data) {
    return $intget_dev_data["ifname"]; }
}
#-------------------------- BUILD PS int data ----------------------
if ($_REQUEST["get_ps_data"] & $_REQUEST["dev_id"] ) {
  #echo $_REQUEST["get_ps_data"];
  $ps_int_array=array();
  $up_int_array=array();
  $bng_id=$_REQUEST["get_ps_data"];
  $devip=long2ip($conn->query("SELECT ip from devices where devserial='$bng_id'")->fetch_assoc()["ip"]);
  
#echo $devip;
$connection = ssh2_connect($devip, 22);
ssh2_auth_password($connection, $ssh_user, $ssh_pass);
$stream = ssh2_exec($connection, 'show l2circuit connections brief | display json');
stream_set_blocking($stream, true);
$stream_out=ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
$units = json_decode(stream_get_contents($stream_out), true);
for ($i=0; $i < count($units["l2circuit-connection-information"][0]["l2circuit-neighbor"]); $i++) {
  $dev_array=array();
    $dev_ip=$units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["neighbor-address"][0]["data"];
    $ps_data[$i]["ip"] = $dev_ip;
    for ($d=0; $d < count($units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"]); $d++) {
        $ps_int = explode(".", $units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$d]["connection-id"][0]["data"]);
        $status = $units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$d]["connection-status"][0]["data"];
        $vc_tag= substr(explode(" ", $units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$d]["connection-id"][0]["data"])[1], 0, -1);
        if ($status == "Up") {
          $ps_data[$i]["interfaces"][$d]["ps_int"] = $ps_int[0];
          $ps_data[$i]["interfaces"][$d]["vc_tag"] = $vc_tag;
          $ps_data[$i]["interfaces"][$d]["status"] = $status;
          array_push($dev_array, "UP");
          array_push($up_int_array, $ps_int[0]);
          }
          
    }
    if (count($dev_array) < 1) {
      unset($ps_data[$i]);
  }
}
echo "<pre>";
$ps_data = array_values($ps_data);
if (count($ps_data) > 0) {
build_ps_data($bng_id, $ps_data);
#print_r($ps_data);

$a = snmprealwalk($devip, "@JuN1p3R", "1.3.6.1.2.1.2.2.1.2");
$a->oid_output_format = SNMP_OID_OUTPUT_FULL;
foreach ($a as $intoid => $int) {
 if(preg_match('/^ps\d+$/',$int)) {
  $ifoidsplit=explode(".", $intoid);
$ifindex= $ifoidsplit[count($ifoidsplit)-1];
$int=(explode(".", $int)[0]);
    $int_desc = snmprealwalk($devip, "@JuN1p3R", "1.3.6.1.2.1.31.1.1.1.18.".$ifindex);
    $int_desc->oid_output_format = SNMP_OID_OUTPUT_FULL;
    foreach ($int_desc as $int_desc_value) {
      if ((!preg_match('/DSL/i', $int_desc_value))) {
      
      #echo $ifindex." ".$int." ".$int_desc_value."<br>";
      if ((preg_match('/OLT/i', $int_desc_value)) && (preg_match('/STATIC/i', $int_desc_value)) || (!preg_match('/OLT/i', $int_desc_value)) || (preg_match('/STATICS/i', $int_desc_value))) {
    array_push($ps_int_array, $int);



    for ($tt=0; $tt < count($ps_data); $tt++) {
      for ($td = 0; $td < count($ps_data[$tt]["interfaces"]); $td++) {
        if ($ps_data[$tt]["interfaces"][$td]["ps_int"] == $int) {
          $agg_ip=ip2long($ps_data[$tt]["ip"]);
          $agg_vc_tag=$ps_data[$tt]["interfaces"][$td]["vc_tag"];
          #echo $agg_ip. " ". $agg_vc_tag."<br>";
          $agg_serial=$conn->query("SELECT devserial from devices where lo100_ip ='$agg_ip'")->fetch_assoc()["devserial"];
          $mx204_int=$conn->query("SELECT agg_int from agg_l2c_data where vc_n='$agg_vc_tag' and bng_serial='$bng_id'")->fetch_assoc()["agg_int"];
          $backup_bng=$conn->query("SELECT bng_serial from agg_l2c_data where agg_dev='$agg_serial' and vc_n='$agg_vc_tag' and vc_status <> 'Up'")->fetch_assoc()["bng_serial"];
          if ($agg_serial) {
            if($conn->query("SELECT ps_int from ps_int where ps_int='$int' and bng='$bng_id'") == $int) {
              $insert=$conn->query("UPDATE ps_int set ps_int='$int' l2c_tag='$agg_vc_tag' psdesc='$int_desc_value' mx204_int='$mx204_int' backup_bng='$backup_bng' where bng='$bng_id'");
              if ($insert === TRUE) {
                ;
              }
              else { echo "Error: " . $insert . "<br>" . $conn->error;}
            } else {
          #echo $ps_data[$tt]["interfaces"][$td]." ".long2ip($agg_ip)." ".$ps_int_query["psdesc"]." ".$agg_serial."<br>";

          $insert="INSERT into ps_int (ps_int, bng, mx204, mx204_int, l2c_tag, psdesc, backup_bng) VALUES ('$int', '$bng_id', '$agg_serial', '$mx204_int', '$agg_vc_tag', '$int_desc_value', '$backup_bng')";
      if ($conn->query($insert) === TRUE) {
        ;
        } else {
        echo "Error: " . $insert . "<br>" . $conn->error; }
        }
      }
        else echo "<br>not found";
        }
      }
  }
       } 
      else  {echo $int_desc_value."<br>"; } } }
 }
 else {
  if (preg_match('/^(ps)([0-9\.\/]+)$/',$int) && (!preg_match('/^ps\d+\.\d$/',$int)) && (!preg_match('/^ps\d+$/',$int)) && (!preg_match('/^ps\d+\.\d{3}$/',$int)) && (!preg_match('/\b32767\b/',$int)))
{
  if (array_search(explode(".", $int)[0], $up_int_array)) {
  $ifoidsplit=explode(".", $intoid);
$ifindex= $ifoidsplit[count($ifoidsplit)-1];
if ($conn->query("SELECT ifname from user_interfaces where ifname='$int' and BNG='$bng_id'")->fetch_assoc()["ifname"] == $int) {
  ;
}
else {
  $ps_subif_agg=explode(".", $int)[0];
  $dev204=$conn->query("SELECT mx204 from ps_int where ps_int='$ps_subif_agg' and bng='$bng_id'")->fetch_assoc()["mx204"];
$insert_subif="INSERT into user_interfaces (ifindex, BNG, ifname, mx204_dev) VALUES ($ifindex, '$bng_id', '$int', '$dev204')";
if ($conn->query($insert_subif) === TRUE) {
  ;
  } else {
  echo "Error: " . $insert_subif . "<br>" . $conn->error; }
} } }
 }
} }
#--------------- insert ps interface details-----------------
for ($ps_i=0; $ps_i<=count($ps_int_array); $ps_i++) {
    $ssh_ps_int = ssh2_exec($connection, 'show configuration interfaces '.$ps_int_array[$ps_i].' | display json');
    stream_set_blocking($ssh_ps_int, true);
    $ssh_out = ssh2_fetch_stream($ssh_ps_int, SSH2_STREAM_STDIO);
    $units = json_decode(stream_get_contents($ssh_out), true)["configuration"]["interfaces"]["interface"][0]["unit"];
    for ($i=0; $i <= count($units); $i++) {
      $unit = $units[$i]["name"];
      $ps_int= $ps_int_array[$ps_i] . '.' . $unit;
      $desc = $units[$i]["description"];
      $vlan_outer = $units[$i]["vlan-tags"]["outer"];
      $vlan_inner = $units[$i]["vlan-tags"]["inner"];
      $ip = $units[$i]["family"]["inet"]["address"];
      if ($unit != 0) {
        $ins_vlan = "UPDATE user_interfaces SET ifdesc='$desc', outer_vlan='$vlan_outer', inner_vlan='$vlan_inner' where ifname='$ps_int'";
        if ($conn->query($ins_vlan) === TRUE) {
          #echo "Saved";
      } else {
          echo "Error: " . $ins_vlan . "<br>" . $conn->error; }
      }

  }
}
$delte_temp_table = $conn->query("TRUNCATE TABLE agg_l2c_data");
$delete_temp_ps_data = $conn->query("TRUNCATE TABLE ps_int_data");

}
#------------------END OF PS_INT DETAILS--------------------
if ($_REQUEST["dev_id"]) {
    $dev_id=$_REQUEST["dev_id"];
    $select_dev="SELECT dev_name, model, device_model, dev_role, devserial from devices where devserial='$dev_id'";
    $dev_proc=$conn->query($select_dev);
    foreach ($dev_proc as $dev_det) {
echo "
<div class='wp-content'>
  <div class='container-fluid'>
<table class='table' bgcolor=lightblue>
<tr>
<td align=left><h2>".$dev_det['dev_name']."</h2></td>
<td align=right><img src='site_images/".$dev_det['device_model'].".png'></td></tr></table><br>
"; }
#echo "<table border=0 align=left id='customers'>
echo "<button class='button' onclick=myFunction('button1')>LLDP neighbors</button>";
if ($dev_det["dev_role"] == "BNG") {
  echo "<button class='button' onclick=myFunction('button2')>PS interfaces</button>"; 


}
else if ($dev_det["dev_role"] == "AGG") {
  echo "<a href=p2psubs.php?aggdev=".$dev_det["devserial"].">P2P Subscribers</a>";
}
 
echo "
<div id='button1' class='generalclass'><table border=0 id='customers'><tbody>
<tr><td>Interface</td><td>Remote Device</td><td>Remote Int</td></tr>";
$dev_lldp="SELECT * from lldp_table where device='$dev_id'";
$dev_lldp_proc=$conn->query($dev_lldp);
foreach ($dev_lldp_proc as $lldp_det) {
  
    echo "<tr><td>".lldp_int($dev_id, $lldp_det['local_ifindex'])."</td><td><a href=".$_SERVER['PHP_SELF']."?dev_id=".$lldp_det['remote_device'].">".lldp_dev($lldp_det['remote_device'])."</a></td><td>".lldp_int($lldp_det['remote_device'], $lldp_det['remote_port'])."</td></tr>";
}
echo "</tbody></table></div>";

echo "<div id='button2' class='generalclass'><table border=0 id='customers' width='100%'><tbody>
<tr><td><input onClick=\"setAllCheckboxes('button2', this);\" type=\"checkbox\" />Check all</td><td>PS interface</td><td>Description</td><td>Active BNG</td><td>Backup BNG</td><td><a href=".$_SERVER['PHP_SELF']."?get_ps_data=".$dev_id."&dev_id=".$dev_id.">Refresh</a></td></tr>
";
$select_ps=$conn->query("SELECT ps_int, bng, psdesc, backup_bng from ps_int where bng='$dev_id'");
foreach ($select_ps as $ps_int_det) {
  echo "<tr><td><input type='checkbox' id='ps_backup'></td><td><a href='p2psubs.php?bng_dev=".$dev_id."&ps_int=".$ps_int_det["ps_int"]."'>".$ps_int_det["ps_int"]."</a></td><td>".$ps_int_det["psdesc"]."</td>
  
    <td>".get_device_name_by_serial($ps_int_det["bng"])."</td><td>".get_device_name_by_serial($ps_int_det["backup_bng"])."</td>"; 
}
  echo "</tr>";
}
echo "</tbody></table></div></div>
"; 
echo "
<script>
function myFunction(divid) {

  var x = document.getElementById(divid);  
  
  if (x.style.display == 'block') 
  {
    x.style.display = 'none';
  } 
  else {
    x.style.display = 'block';
  }  
}


function setAllCheckboxes(divId, sourceCheckbox) {
  divElement = document.getElementById(divId);
  inputElements = divElement.getElementsByTagName('input');
  for (i = 0; i < inputElements.length; i++) {
      if (inputElements[i].type != 'checkbox')
          continue;
      inputElements[i].checked = sourceCheckbox.checked;
  }
}

</script>
";


?>