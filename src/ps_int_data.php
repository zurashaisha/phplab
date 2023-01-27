<?php
require "/var/www/html/project/config.php";
require "/var/www/html/project/commands.php";
#echo "<pre>";
function build_ps_data($bng_ser, $ps_data) {
    global $conn;
for ($t=0; $t < count($ps_data); $t++) {
    $agg_lo0=ip2long($ps_data[$t]["ip"]);
    $agg_serial=$conn->query("SELECT devserial from devices where lo100_ip='$agg_lo0'")->fetch_assoc()["devserial"];
    $agg_l2c_array=get_agg_l2c($agg_serial);
    for ($agg_n=0; $agg_n<count($agg_l2c_array); $agg_n++) {
        $bng_lo0 = ip2long($agg_l2c_array[$agg_n]["ip"]);
        $bng_serial=$conn->query("SELECT devserial from devices where lo100_ip='$bng_lo0'")->fetch_assoc()["devserial"];
        for ($agg_t=0; $agg_t<count($agg_l2c_array[$agg_n]["interfaces"]); $agg_t++) {
            $agg_int=$agg_l2c_array[$agg_n]["interfaces"][$agg_t]["ps_int"];
            $vcn=$agg_l2c_array[$agg_n]["interfaces"][$agg_t]["vc_tag"];
            $int_status=$agg_l2c_array[$agg_n]["interfaces"][$agg_t]["status"];
            #echo $agg_serial." ".$bng_serial." ".$agg_int." ".$vcn." ".$int_status."<br>";
            $insert = "INSERT into agg_l2c_data (agg_dev, bng_serial, agg_int, vc_n, vc_status) VALUES ('$agg_serial', '$bng_serial', '$agg_int', '$vcn', '$int_status')";
            if ($conn->query($insert) === TRUE) {
                #echo "saved<br>";
              }
              #else { echo "Error: " . $insert . "<br>" . $conn->error;}
        }
    }
    for ($i=0; $i<count($ps_data[$t]["interfaces"]); $i++) {
        $ps_int=$ps_data[$t]["interfaces"][$i]["ps_int"];
        $vc_tag=$ps_data[$t]["interfaces"][$i]["vc_tag"];
        $insert_ps_data = "INSERT into ps_int_data (ps_int, bng, vc_tag) VALUES ('$ps_int','$bng_ser', '$vc_tag')";
        if ($conn->query($insert_ps_data) === TRUE) {
            #echo "saved<br>";
          }
          #else { echo "Error: " . $insert_ps_data . "<br>" . $conn->error;}

    }

}
}
?>
