<?php
require "config.php";
require "header.php";
require "topbar.php";
require "sidebar.php";
require "commands.php";
require "ip_module.php";

?>
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

.city {
  background-color: green;
  color: white;
  border: 2px solid black;
  margin: 50px;
  padding: 5px;
}
.usersdet {
  background-color: white;
  color: black;
  border: 0px solid black;
  margin: 50px;
  padding: 5px;
}
</style>
<body>
<div class="wp-content">
  <div class="container-fluid"> 
<?php
function getmacvendor($mac) {
  $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.macvendors.com/".$mac,
  CURLOPT_HEADER => false,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);
curl_close($curl);
return $response;
}
if ($_REQUEST["unauthorized_subs"] == "no_service"){
  
  echo "<h2>Unauthorized Subscribers Total: ".$conn->query("SELECT * from fake_subscribers")->num_rows."</h2>";
  echo "<table border=0 id='customers'><tbody>
<tr><td>Service Number</td><td>PS Interface</td><td>BNG</td></tr>";
$get_unauthorized_users=$conn->query("SELECT * from fake_subscribers where service_id is not null");
foreach ($get_unauthorized_users as $unauthorized_p2p) {
  $bng_id = $unauthorized_p2p["BNG"];
  echo "<tr><td>".$unauthorized_p2p["service_id"]."</td><td><a href=".$_SERVER['PHP_SELF']."?user_det_ps=".$unauthorized_p2p["ifname"]."&bng_det=".$unauthorized_p2p["BNG"].">".$unauthorized_p2p["ifname"]."</a></td>
  <td>".$conn->query("SELECT dev_name from devices where devserial='$bng_id'")->fetch_assoc()["dev_name"]."</td><td>".$unauthorized_p2p["ps_desc"]."</td></tr>";

}
echo "</tbody></table>";
  }

  if ($_REQUEST["unauthorized_subs"] == "no_group") {
    echo "<h2>Customers with no Subscriber group | Total: ".$conn->query("SELECT * from user_interfaces where service_id is null")->num_rows."</h2>";
  echo "<table border=0 id='customers'><tbody>
  <tr><td>Description</td><td>PS Interface</td><td>BNG</td></tr>";
  $get_unauthorized_users=$conn->query("SELECT * from user_interfaces where service_id is null");
  foreach ($get_unauthorized_users as $unauthorized_p2p) {
    $bng_id = $unauthorized_p2p["BNG"];
    echo "<tr><td>".$unauthorized_p2p["ifdesc"]."</td><td><a href=".$_SERVER['PHP_SELF']."?user_det_ps=".$unauthorized_p2p["ifname"]."&bng_det=".$unauthorized_p2p["BNG"].">".$unauthorized_p2p["ifname"]."</a></td><td>".$conn->query("SELECT dev_name from devices where devserial='$bng_id'")->fetch_assoc()["dev_name"]."</td><td>".$unauthorized_p2p["ps_desc"]."</td></tr>";
    
  }
  echo "</tbody></table>";
  }


if ($_REQUEST["user_det_ps"] & $_REQUEST["bng_det"]){
  $bng_for_update = $_REQUEST["bng_det"];
    $ps_int_for_update=$_REQUEST["user_det_ps"];
    $bng_ip=get_bng_ip_by_serial($_REQUEST["bng_det"]);
  if ($_REQUEST["refreshint"] == 1) {
    $ifindex_detail=$conn->query("SELECT ifindex from user_interfaces where ifname='$ps_int_for_update' and BNG='$bng_for_update'")->fetch_assoc()["ifindex"];
    $refresh_desc=snmprealwalk($bng_ip, "@JuN1p3R", "1.3.6.1.2.1.31.1.1.1.18.".$ifindex_detail);
    $refresh_desc->oid_output_format = SNMP_OID_OUTPUT_FULL;
    foreach ($refresh_desc as $int_oid=>$new_desc)
    
    $command="show static-subscribers sessions | match ".$_REQUEST["user_det_ps"];
    $service_tag=ssh_host_result_nojson($bng_ip, $command);
    $service_id= preg_split('/\s+/', $service_tag);
    $update_service=$conn->query("UPDATE user_interfaces set service_id='$service_id[4]', ifdesc='$new_desc' where BNG='$bng_for_update' and ifname='$ps_int_for_update'");
    if ($update_service === TRUE) {
      ;
    }
    else { echo $conn->error; }
    #echo $service_id[4];
  }
  if ($_REQUEST["relogin"]) {
    $relogin_command="request services static-subscribers logout group ".$_REQUEST["relogin"];
    ssh_host_result_nojson($bng_ip, $relogin_command);
  }
  $ip_get = new ip_module;
    $user_ps_int=$_REQUEST["user_det_ps"];
    $user_bng_det=$_REQUEST["bng_det"];
    $user_description=$conn->query("SELECT * from user_interfaces where ifname ='$user_ps_int' and BNG='$user_bng_det'")->fetch_assoc();
    $service_tag = $user_description["service_id"];
    $check_service_command="show subscribers user-name $service_tag | display json";
    $bng_ip=get_bng_ip_by_serial($user_bng_det);
    $command_output=ssh_host_result($bng_ip, $check_service_command)["subscribers-information"][0]["subscriber"][0]["user-name"][0]["data"];
  if ($command_output == $service_tag) {
    $service_state = "Online";
  }
  else { $service_state = "Offline"; }
    echo "<a href=".$_SERVER['PHP_SELF']."?user_det_ps=".$user_ps_int."&bng_det=".$user_bng_det."&refreshint=1>Refresh Int data</a>";

    echo "<table border=0 id='customers'><tbody><tr><td>Subscriber interface Description</td></tr><tr><td>".$user_description["ifdesc"]."</td><td>".$user_ps_int."</td></tr>
    <tr><td>Service tag</td><td>".$user_description["service_id"]."</td><td>".$service_state."</td><td><a href=".$_SERVER['PHP_SELF']."?user_det_ps=".$user_ps_int."&bng_det=".$user_bng_det."&relogin=".$user_description["service_id"].">Relogin</a></td></tr></tbody></table>";
    echo "<br>";
    echo "<div><div><table border=0 background='green'><tr><td>User Interface Details</td></tr></table></div>
    <table border=0><tr><td>PS outer Vlan</td><td>PS inner Vlan</td></tr>
    <tr><td>".$user_description["outer_vlan"]."</td><td>".$user_description["inner_vlan"]."</td></tr></table>
    </div>";
    $snmp_ip_oid = snmprealwalk($bng_ip, "@JuN1p3R", "1.3.6.1.4.1.2636.3.12.1.1.1.3.".$user_description["ifindex"]);
    $snmp_ip_oid->oid_output_format = SNMP_OID_OUTPUT_FULL;
    foreach ($snmp_ip_oid as $ip_oid => $subnet_mask) {
      $user_ps_ip=implode(".", array_slice(explode(".", $ip_oid), 15));
      $userip=$user_ps_ip. " ".$subnet_mask;

      echo "Network: ".$ip_get->IPv4Network($userip)."<br>";
    }
    echo "<button class='button' onclick=myFunction('DivARP')>ARP Table</button>



    <div id='DivARP' class='generalclass'><table border=0 id='customers'><tbody>
    <tr><td>MAC Address</td><td>IP Address</td></tr>";
    $arp_table=ssh_host_result($bng_ip, "show arp interface ".$user_ps_int." | display json");
    for ($arp_n=0; $arp_n<count($arp_table["arp-table-information"][0]["arp-table-entry"]); $arp_n++) {
      echo "<td>".$arp_table["arp-table-information"][0]["arp-table-entry"][$arp_n]["mac-address"][0]["data"]."</td><td>".$arp_table["arp-table-information"][0]["arp-table-entry"][$arp_n]["ip-address"][0]["data"]."</td></tr>";
    }
    echo "</tbody></table></div>
    "; 



}



else if ($_REQUEST["aggdev"]) {
  $aggdev=$_REQUEST["aggdev"];
  #echo $aggdev;
  echo "<table border=0 id='customers'><tbody>
<tr><td>User Description</td><td>PS Interface</td></tr>";
  $users_per_agg=$conn->query("SELECT ps_int, bng from ps_int where mx204='$aggdev'");
  foreach ($users_per_agg as $p2p_per_agg) {
    $current_ps_int=$p2p_per_agg["ps_int"];
    #echo $current_ps_int."<br>";
    $current_ps_bng=$p2p_per_agg["bng"];
    $users_det_per_ps=$conn->query("SELECT ifname, ifdesc from user_interfaces where BNG='$current_ps_bng' and ifname like'$current_ps_int%'");
    foreach ($users_det_per_ps as $user_details) {
      echo "<tr><td>".$user_details["ifdesc"]."</td><td><a href=".$_SERVER['PHP_SELF']."?user_det_ps=".$user_details["ifname"]."&bng_det=".$current_ps_bng.">".$user_details["ifname"]."</td></tr>";
    }
  }
  echo "</tbody></table>";
}

if ($_REQUEST["ps_int"] & $_REQUEST["bng_dev"]) {
  echo "

<table border=0 id='customers'><tbody>
<tr><td>User Description</td><td>PS Interface</td><td>BNG</tr>";
    $ps_int=$_REQUEST["ps_int"];
    $bng_dev=$_REQUEST["bng_dev"];
    $p2p_users=$conn->query("SELECT * from user_interfaces where ifname like '%$ps_int%' and BNG = '$bng_dev'");
    foreach ($p2p_users as $p2p_users_det) {
      $bng=$p2p_users_det["BNG"];
    $bng_name=$conn->query("SELECT dev_name from devices where devserial='$bng'")->fetch_assoc()["dev_name"];
echo "<tr><td><a href=".$_SERVER['PHP_SELF']."?user_det_ps=".$p2p_users_det["ifname"]."&bng_det=".$bng.">".$p2p_users_det["ifname"]."</a></td><td>".$p2p_users_det["ifdesc"]."</td><td>".$bng_name."</td></tr>";
}
echo "</tbody></table></div></div>";
    } 
  

if ($_REQUEST["alldata"] == 1) {
  echo "
  <div class=\"container\">
        <div class=\"card\">
          <div class=\"card-header\">
            <div class=\"row\">
              <div class=\"col-md-6\">All Static Customers</div>
              <div class=\"col-md-3 text-right\"><b>Total P2P count - <span id=\"total_data\"></span></b></div>
              <div class=\"col-md-3\">
                <input type=\"text\" name=\"search\" class=\"form-control\" id=\"search\" placeholder=\"Search Here\" onkeyup='load_data(this.value);' />
              </div>
            </div>
          </div>
          <div class=\"card-body\">
            <table class=\"table table-bordered\">
              <thead>
                <tr>
                  <th width=\"15%\">PS Interface</th>
                  <th width=\"35%\">Description</th>
                  <th width=\"20%\">Service ID</th>
                <th width=\"20%\">BNG</th>
                </tr>
              </thead>
              <tbody id=\"post_data\"></tbody>
            </table>
            <div id=\"pagination_link\"></div>
          </div>
        </div>"; 

}

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
</script>
";

?>
<script>

load_data();

function load_data(query = '', page_number = 1)
{
	var form_data = new FormData();

	form_data.append('query', query);

	form_data.append('page', page_number);

	var ajax_request = new XMLHttpRequest();

	ajax_request.open('POST', 'p2psubs_data.php');

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
					html += '<td><a href=p2psubs.php?user_det_ps='+response.data[count].ifname+'&bng_det='+response.data[count].bng+'>'+response.data[count].ifname+'</a></td>';
					html += '<td>'+response.data[count].ifdesc+'</td>';
          html += '<td>'+response.data[count].service_id+'</td>';
					html += '<td>'+response.data[count].bng+'</td>';
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