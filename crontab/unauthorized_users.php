<?php
require "/var/www/html/project/config.php";
$date=date('m/d/Y h:i:s a', time());
$select_bng=$conn->query("SELECT ip, devserial from devices where dev_role='BNG'");
$delete_fake_subs_db = $conn->query("TRUNCATE TABLE fake_subscribers");
$remove_fake_message = $conn->query("UPDATE messages_table SET message_state='off' where message_class = 'fake_subscribers'");
$remove_no_group_message = $conn->query("UPDATE messages_table SET message_state='off' where message_class = 'no_group_subscribers'");
$check_no_group = $conn->query("SELECT * from user_interfaces where service_id is null")->num_rows;
if ($check_no_group>0) {
  $conn->query("UPDATE messages_table SET message_state='on', message_date='$date' where message_class = 'no_group_subscribers'");
}
foreach ($select_bng as $bng_det) {
  
  $bng_serial = $bng_det["devserial"];
  $bng_ip= long2ip($bng_det["ip"]);
  if ($conn->query("SELECT ps_int from ps_int where bng='$bng_serial'")->num_rows > 0) {
$connection = ssh2_connect($bng_ip, 22);
ssh2_auth_password($connection, 'zura', 'Pls4mail');
$stream = ssh2_exec($connection, 'show static-subscribers sessions | display json');
stream_set_blocking($stream, true);
$stream_out=ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
$units = json_decode(stream_get_contents($stream_out), true);


#echo "<pre>";
#print_r($units["static-subscribers-information"][0]["subscriber-session"]);
#echo count($units["static-subscribers-information"][0]["subscriber-session"]);
for ($i=0; $i<=count($units["static-subscribers-information"][0]["subscriber-session"])-1; $i++) {
  $user_int = $units["static-subscribers-information"][0]["subscriber-session"][$i]["interface"][0]["data"];
  $user_service = $units["static-subscribers-information"][0]["subscriber-session"][$i]["username"][0]["data"];
  $state= $units["static-subscribers-information"][0]["subscriber-session"][$i]["state"][0]["data"];
  if ($user_int) {
  $update_service = "UPDATE user_interfaces SET service_id ='$user_service' where ifname='$user_int' and BNG='$bng_serial'";
  if ($conn->query($update_service) === TRUE) {
    ;
    } else {
    echo "Error: " . $update_service . " " . $conn->error."<br>"; }
    
  
  if ($state == 'logged out') {
    $ps_int_main=explode(".", $user_int)[0];
    $ps_main_desc = $conn->query("SELECT psdesc from ps_int where bng='$bng_serial' and ps_int='$ps_int_main'")->fetch_assoc()["psdesc"];
    $fake_message_on = $conn->query("UPDATE messages_table SET message_state='on', message_date='$date' where message_class = 'fake_subscribers'");
    $fake_subscribers= "INSERT into fake_subscribers (service_id, ifname, BNG, ps_desc) VALUES ('$user_service', '$user_int', '$bng_serial', '$ps_main_desc')";
    if ($conn->query($fake_subscribers) === TRUE) {
      ;
      } else {
      echo "Error: " . $fake_subscribers . " " . $conn->error."<br>"; }
      #echo "Subscriber :".$user_service. " ".$user_int. " ".$bng_ip. " is not active<br>";
      }
     }
     else  { echo "Interface ".$user_int." not found<br>"; }
}
  
 }
}
 
?>