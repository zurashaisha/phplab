<?php
function ssh_host_result($host, $command) {
    $connection = ssh2_connect($host, 22);
    ssh2_auth_password($connection, 'zura', 'Pls4mail');
    $stream = ssh2_exec($connection, $command);
    stream_set_blocking($stream, true);
    $stream_out=ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $units = json_decode(stream_get_contents($stream_out), true);
    return $units;
    }
    function ssh_host_result_nojson($host, $command) {
        $connection = ssh2_connect($host, 22);
        ssh2_auth_password($connection, 'zura', 'Pls4mail');
        $stream = ssh2_exec($connection, $command);
        stream_set_blocking($stream, true);
        $stream_out=ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        $output = stream_get_contents($stream_out);
        return $output;
        }
    function get_bng_ip_by_serial($bngser) {
        global $conn;
            $bng_ip=$conn->query("SELECT ip from devices where devserial='$bngser'")->fetch_assoc()["ip"];
            return long2ip($bng_ip);
      }
    function get_device_name_by_serial($devserial) {
        global $conn;
        $dev_name=$conn->query("SELECT dev_name from devices where devserial='$devserial'")->fetch_assoc()["dev_name"];
        return $dev_name;
    }
    function get_device_serial_by_loip($ip) {
        global $conn;
        $ip= ip2long($ip);
        $dev_serial=$conn->query("SELECT devserial from devices where lo100_ip = '$ip'")->fetch_assoc()["devserial"];
        return $dev_serial;
    }

function get_agg_l2c($agg_serial) {
    global $conn;
$agg_ip_for_db=long2ip($conn->query("SELECT ip from devices where devserial='$agg_serial'")->fetch_assoc()["ip"]);
$connection = ssh2_connect($agg_ip_for_db, 22);
ssh2_auth_password($connection, 'zura', 'Pls4mail');
$stream = ssh2_exec($connection, "show l2circuit connections brief | display json");
stream_set_blocking($stream, true);
$stream_out=ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
$units = json_decode(stream_get_contents($stream_out), true);
for ($i=0; $i < count($units["l2circuit-connection-information"][0]["l2circuit-neighbor"]); $i++) {
    $dev_ip=$units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["neighbor-address"][0]["data"];
    $ps_data[$i]["ip"] = $dev_ip;
    for ($d=0; $d < count($units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"]); $d++) {
        $ps_int = explode("(", $units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$d]["connection-id"][0]["data"]);
        $vc_tag= substr(explode(" ", $units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$d]["connection-id"][0]["data"])[1], 0, -1);
		$status = $units["l2circuit-connection-information"][0]["l2circuit-neighbor"][$i]["connection"][$d]["connection-status"][0]["data"];
        $ps_data[$i]["interfaces"][$d]["ps_int"] = $ps_int[0];
        $ps_data[$i]["interfaces"][$d]["vc_tag"] = $vc_tag;
        $ps_data[$i]["interfaces"][$d]["status"] = $status;

    }
}

return $ps_data;

    }

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