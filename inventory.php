<?php
require "config.php";
require "header.php";
require "topbar.php";
require "sidebar.php";
?>
<script>
$(document).ready(function(){
    $('input[type="radio"]').click(function(){
    	var demovalue = $(this).val(); 
        $("div.Divform").hide();
        $("#show"+demovalue).show();
    });
});
</script>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 70%;
}

#customers td, #customers th {
  border: 1px solid #ddd;
  padding: 8px;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #04AA6D;
  color: white;

}
a:link {
  text-decoration: none;
}
#myDIV {
  width: 100%;
  padding: 50px 0;
  text-align: center;
  background-color: lightblue;
  margin-top: 20px;
}

.Divform{
	display:none;
}  
#showfileupload{
	color:green;
    border:1px solid green;
    padding:10px;
}
#showdevice{
	color:green;
    border:1px solid green;
    padding:10px;
}
</style>
<body>
<div class="wp-content">
  <div class="container-fluid">   
<?php

function splitoid($oid) {
    $oid_splitted=explode(".", $oid);
    $ifindex=array_slice($oid_splitted, count($oid_splitted)-1);
    return $ifindex[0];
}
if ($_REQUEST["delete_device"]) {
  $del_device=$_REQUEST["delete_device"];
  echo $del_device;
  $conn->query("DELETE from devices where devserial='$del_device'");
}

if ($_REQUEST["dev_ip"] & $_REQUEST["dev_id"]) {
    $snmp=$_REQUEST["snmp"];
    $device_id = $_REQUEST["dev_id"];
    $snmp_comm="SELECT string from snmp_table where id='$snmp'";
    $snmp_res=$conn->query($snmp_comm);
    foreach ($snmp_res as $snmp_ress)
    $a = snmprealwalk($_REQUEST["dev_ip"], $snmp_ress["string"], "1.3.6.1.2.1.2.2.1.2");
    $a->oid_output_format = SNMP_OID_OUTPUT_FULL;
    foreach ($a as $intoid=>$int)
    if (preg_match('/^(xe-|et-|TenGigabitEthernet)([0-9\/\:]+)$/', $int)) {
        $intdesc=snmprealwalk($_REQUEST["dev_ip"], $snmp_ress["string"], "1.3.6.1.2.1.31.1.1.1.18.".splitoid($intoid));
        $intdesc->oid_output_format = SNMP_OID_OUTPUT_FULL;
        foreach ($intdesc as $int_desc)
        if ($int_desc) {
            $split_oid=splitoid($intoid);
            $insert_int="INSERT into interfaces (device, ifname, ifindex, ifdescription) VALUES('$device_id', '$int', '$split_oid', '$int_desc')";
            if ($conn->query($insert_int) === TRUE) {
                echo "Saved";
                } else {
                echo "Error: " . $insert_int . "<br>" . $conn->error; }
        echo splitoid($intoid)." ".$int." ".$int_desc."<br>";} }
    
}

echo "

<input type='radio' name='demo' value='fileupload'> Upload File
<input type='radio' name='demo' value='device'> Manual Add

<div id='showfileupload' class='Divform'>
<form action='inventory.php' method='post' enctype='multipart/form-data'>
  Upload CSV file only:
  <input type='file' name='file' id='file'>
  <input type='hidden' name='formname' value='file'>
  <input type='submit' value='Upload' name='submit'>
</form>
</div>

<div id='showdevice' class='Divform'>
<form action='inventory.php' method='post' enctype='multipart/form-data'>
<table border=0>
<tr><td>Device IP: </td><td><input type=text name='dev_ip'></td></tr>
<input type='hidden' name='formname' value='ip_device'>
<tr><td>Select Vendor: </td><td><select name='vendor' id='vendor'>
<option value='Juniper'>Juniper</option>
<option value='Cisco'>Cisco</option>
<option value='Huawei'>Huawei</option></select></td>
<td><input type='submit' value='device_add' name='submit'></tr></table></form>
</div>

<table border=0 id='customers'>";
#------------------------ Device Add Section------------------
if($_SERVER["REQUEST_METHOD"] == "POST") {
  if($_POST["formname"] == "file") {
  $target_dir = "/var/www/html/silknet/files/";
  $target_file = $target_dir . basename($_FILES["file"]["name"]);
  if(isset($_FILES["file"])) {
      $file = $_FILES["file"];
      #print_r($file["type"]);
      if ($file["type"] == "text/csv") {
      echo "CORRECT<br>";
      echo $target_file; 
      echo $_FILES["file"]["tmp_name"]."<br>";
      if (move_uploaded_file($_FILES["file"]["tmp_name"], "files/" . $_FILES["file"]["name"])){
          $file_csv=fopen("files/".$_FILES["file"]["name"], "r");
          while (($line = fgetcsv($file_csv)) !== FALSE) {
              #print_r($line);
              $snmp_comm=$conn->query("SELECT string from snmp_table where vendor='$line[2]'")->fetch_assoc()["string"];
              $sql_community_id = $conn->query("SELECT id from snmp_table where vendor='$line[2]'")->fetch_assoc()["id"];
              $hostvendor = snmprealwalk($line[1], $snmp_comm, "1.3.6.1.2.1.1.1.0");
              $hostname = snmprealwalk($line[1], $snmp_comm, "1.0.8802.1.1.2.1.3.3.0");
              if ($line[2] == "Juniper") {
              $router_model = snmprealwalk($line[1], $snmp_comm, "1.3.6.1.4.1.2636.3.1.2");
              $router_serial = snmprealwalk($line[1], $snmp_comm, "1.3.6.1.4.1.2636.3.1.3");
              }
              else if ($line[2]=="Cisco") {
                  $router_model = snmprealwalk($line[1], $snmp_comm, "1.3.6.1.2.1.47.1.1.1.1.13.1");
                  $router_serial = snmprealwalk($line[1], $snmp_comm, "1.3.6.1.2.1.47.1.1.1.1.11.1");
              }
              $hostvendor->oid_output_format = SNMP_OID_OUTPUT_FULL;
              $router_serial->oid_output_format = SNMP_OID_OUTPUT_FULL;
              $router_model->oid_output_format = SNMP_OID_OUTPUT_FULL;
              $hostname->oid_output_format = SNMP_OID_OUTPUT_FULL;
              foreach ($hostvendor as $dev_vendor)
              $dev_vendor=explode(" ", $dev_vendor);
              foreach ($hostname as $host_name) {

              }
              #echo $host_name." ";
              foreach ($router_model as $host_model) {
                if ($device_vendor = "Juniper") {
                  $host_model_short = explode(" ", $host_model)[1]; 
                      $lo_ip=array_slice(explode(".", $line[1]), 3);
                      $loopback = ip2long("10.255.255.".$lo_ip[0]);
                      }
                  else if ($device_vendor == "Cisco") {
                      $host_model_short=$host_model;
                      $loopback = ip2long($line[2]);
                    } }
              foreach ($router_serial as $host_serial)
              echo $snmp_comm." ".$host_serial." ".$host_name." ".$line[1]." ".$line[2]." ".$host_model_short." ".$loopback."<br>";
              $ip=ip2long($line[1]);
              if (preg_match('/BNG/i', $host_name)) {
                  $dev_role = "BNG";
              }
              else if (preg_match('/QFX/i', $host_name) || $line[2]=="Cisco") { $dev_role = "ACCESS-L2"; }
              else if (preg_match('/CORE/i', $host_name)) { $dev_role = "CORE"; }
              if ($host_model_short == "JNP204") { $dev_role="AGG"; }

              $insert_db = "INSERT into devices (dev_name, ip, model, devserial, snmp_id, vendor, device_model, dev_role, lo100_ip) VALUES('$host_name', '$ip', '$host_model', '$host_serial', '$sql_community_id', '$dev_vendor[0]', '$host_model_short', '$dev_role', '$loopback')";
              if ($conn->query($insert_db) === TRUE) {
                  echo " Saved ";
                } else {
                  echo "Error: " . $insert_db . "<br>" . $conn->error; }
              #echo $line[0]." ".$line[1]."<br>";
          }
          fclose($file_csv);
          echo "file uploaded<br>";
          
      }
      else { "error upload"; }
  }
      else { echo "incorrect ".$file["name"]. " ".$file["type"]; }
  }
  else { echo "no file"; } }

  else if ($_POST["formname"] == "ip_device") {
      $device_vendor = $_POST['vendor'];
      $device_ip=ip2long($_POST["dev_ip"]);
      #echo $device_vendor. " ".$_POST["dev_ip"]." ";
      $check_device_availablity=$conn->query("SELECT ip from devices where ip = '$device_ip'");
      #foreach ($check_device_availablity as $dev_ip_db) 
      #echo $dev_ip_db["ip"];
        if ($check_device_availablity->num_rows == 0) {
              #echo "Device not in list ".$device_vendor;
		          $sql_get_snmp_request="SELECT id, string from snmp_table where vendor='$device_vendor'";
              $sql_comm=$conn->query($sql_get_snmp_request);
              foreach ($sql_comm as $sql_community)
              $sql_community_id=$sql_community["id"];
              #echo " ".$sql_community["string"];
              $hostvendor = snmprealwalk($_POST["dev_ip"], $sql_community["string"], "1.3.6.1.2.1.1.1.0");
              $hostname = snmprealwalk($_POST["dev_ip"], $sql_community["string"], "1.0.8802.1.1.2.1.3.3.0");
              if ($device_vendor == "Juniper") {
              $router_model = snmprealwalk($_POST["dev_ip"], $sql_community["string"], "1.3.6.1.4.1.2636.3.1.2");
              $router_serial = snmprealwalk($_POST["dev_ip"], $sql_community["string"], "1.3.6.1.4.1.2636.3.1.3");
              }
              else if ($device_vendor=="Cisco") {
                  $router_model = snmprealwalk($_POST["dev_ip"], $sql_community["string"], "1.3.6.1.2.1.47.1.1.1.1.13.1");
                  $router_serial = snmprealwalk($_POST["dev_ip"], $sql_community["string"], "1.3.6.1.2.1.47.1.1.1.1.11.1");
              }
              $hostvendor->oid_output_format = SNMP_OID_OUTPUT_FULL;
              $router_serial->oid_output_format = SNMP_OID_OUTPUT_FULL;
              $router_model->oid_output_format = SNMP_OID_OUTPUT_FULL;
              $hostname->oid_output_format = SNMP_OID_OUTPUT_FULL;
              foreach ($hostvendor as $dev_vendor)
              $dev_vendor=explode(" ", $dev_vendor);
              foreach ($hostname as $host_name)
              echo $host_name." ";
              foreach ($router_model as $host_model) {
              if ($device_vendor == "Juniper") {
              $host_model_short = explode(" ", $host_model)[1]; 
                  $lo_ip=array_slice(explode(".", $_POST["dev_ip"]), 3);
                  $loopback = ip2long("10.255.255.".$lo_ip[0]);
                  }
              else if ($device_vendor == "Cisco") {
                  $host_model_short=$host_model;
                  $loopback = ip2long($_POST["dev_ip"]);
                } }
              foreach ($router_serial as $host_serial)
              echo $sql_community["string"]." ".$host_serial." ".$_POST["dev_ip"]." ".$host_model_short." ".$loopback."<br>";
              $ip=ip2long($_POST["dev_ip"]);
              if (preg_match('/BNG/i', $host_name)) {
                  $dev_role = "BNG";
              }
              else if (preg_match('/QFX/i', $host_name) || $device_vendor =="Cisco") { $dev_role = "ACCESS-L2"; }
              else if (preg_match('/CORE/i', $host_name)) { $dev_role = "CORE"; }
              if ($host_model_short=="JNP204") { $dev_role="AGG"; }
              $insert_db = "INSERT into devices (dev_name, ip, model, devserial, snmp_id, vendor, device_model, dev_role, lo100_ip) VALUES('$host_name', '$ip', '$host_model', '$host_serial', '$sql_community_id', '$dev_vendor[0]', '$host_model_short', '$dev_role', '$loopback')";
              if ($conn->query($insert_db) === TRUE) {
                  echo " Saved ";
                } else {
                  echo "Error: " . $insert_db . "<br>" . $conn->error; } 
              

  } 
else { echo "Device with IP ".$_POST["dev_ip"]." already exist.";}
 }
}
#---------------- End of Device Add section-----------------
echo "
<div class=\"container\">
    	<div class=\"card\">
    		<div class=\"card-header\">
    			<div class=\"row\">
    				<div class=\"col-md-6\">All Devices</div>
    				<div class=\"col-md-3 text-right\"><b>Total Device count - <span id=\"total_data\"></span></b></div>
    				<div class=\"col-md-3\">
    					<input type=\"text\" name=\"search\" class=\"form-control\" id=\"search\" placeholder=\"Search Here\" onkeyup='load_data(this.value);' />
    				</div>
    			</div>
    		</div>
    		<div class=\"card-body\">
    			<table class=\"table table-bordered\">
    				<thead>
    					<tr>
    						<th width=\"35%\">Device name</th>
    						<th width=\"20%\">IP Address</th>
							<th width=\"30%\">Serial Number</th>
							<th width=\"20%\">Device Role</th>
							<th width=\"20%\">Action</th>
    					</tr>
    				</thead>
    				<tbody id=\"post_data\"></tbody>
    			</table>
    			<div id=\"pagination_link\"></div>
    		</div>
    	</div>";
#$select_devices="SELECT * from devices";
#$dev_result=$conn->query($select_devices);
#foreach ($dev_result as $devices)

 

?>
<script>

load_data();

function load_data(query = '', page_number = 1)
{
	var form_data = new FormData();

	form_data.append('query', query);

	form_data.append('page', page_number);

	var ajax_request = new XMLHttpRequest();

	ajax_request.open('POST', 'process_data_dev.php');

	ajax_request.send(form_data);

	ajax_request.onreadystatechange = function()
	{
		if(ajax_request.readyState == 4 && ajax_request.status == 200)
		{
			var response = JSON.parse(ajax_request.responseText);

			var html = '';

			var serial_no = 1;

			if(response.data.length > 0)
			{
				for(var count = 0; count < response.data.length; count++)
				{
					html += '<tr>';
					html += '<td><a href=device.php?dev_id='+response.data[count].devserial+'>'+response.data[count].dev_name+'</a></td>';
					html += '<td>'+response.data[count].ip+'</td>';
					html += '<td>'+response.data[count].devserial+'</td>';
					html += '<td>'+response.data[count].dev_role+'</td>';
					html += '</tr>';
					serial_no++;
				}
			}
			else
			{
				html += '<tr><td colspan="3" class="text-center">No Data Found</td></tr>';
			}

			document.getElementById('post_data').innerHTML = html;

			document.getElementById('total_data').innerHTML = response.total_data;

			document.getElementById('pagination_link').innerHTML = response.pagination;

		}

	}
}

</script>