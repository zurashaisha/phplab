<?php
require "config.php";
require "commands.php";
require "header.php";
require "topbar.php";
require "sidebar.php";
echo "<html>
<head>
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
</style></head>";
echo "
<body>";
if ($_REQUEST["dev_name"]) {
    $device_name=$_REQUEST["dev_name"];
    $mydir = '/var/vsftp/mpls/ftp/Juniper/configs/'.$device_name; 
  
  $myfiles = array_diff(scandir($mydir), array('.', '..')); 
  $myfiles = array_values($myfiles);
  #echo $mydir;
  #print_r($myfiles); 
  echo "
  <div class='container'>
  <div class='container-fluid'>
<table class='table table-sm'><thead class='thead-dark'>
<tr><th>File name</th><th>Date</th></tr></thead>
";
  for ($i=0; $i<count($myfiles); $i++) {
    echo "<tr><td><a href=download.php?path=".$mydir."/".$myfiles[$i].">".$myfiles[$i]."</a></td><td>".date('d-m-Y', filectime($mydir."/".$myfiles[$i]))."</td></tr>";
  }
  echo "</table></div></div>";
}
else if ($_REQUEST["action"] = "alldata") {
  $result_per_page = 20;
  $alldev = "SELECT dev_name from devices where vendor='Juniper'";
  $alldev_db = $conn->query($alldev);
  $total_rows =mysqli_num_rows($alldev_db);
  $number_of_page = ceil ($total_rows / $result_per_page);
  if (!isset ($_GET['page']) ) {  
    $page = 1;  
} else {  
    $page = $_GET['page'];  
} 
echo "
<div class='wp-content'>
  <div class='container-fluid'>
<table class='table'>
<tr><td>Device Name</td><td align=right>
<form action='device_configs.php' method='post'>
<input type=text name=device_search id=device_search>&nbsp<input type=submit name=ok id=ok value=Search>
</form>
</td></tr>
";
if ($_POST['device_search']) {
  
  $dev_value=$_POST['device_search'];
  $find_dev="SELECT dev_name from devices where dev_name like '%$dev_value%'";
  $find_query=mysqli_query($conn, $find_dev);
  echo "<table>";
  foreach ($find_query as $dev_det) {
  echo "<tr><td><a href=".$_SERVER['PHP_SELF']."?dev_name=".$dev_det["dev_name"].">".$dev_det["dev_name"]."</a></td></tr>";
  }
  echo "</table>";
}

else {

$page_first_result = ($page-1) * $result_per_page; 
$query = "SELECT dev_name FROM devices where vendor='Juniper' LIMIT " . $page_first_result . ',' . $result_per_page;
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_array($result)) { 
    
  echo "<tr><td><a href=".$_SERVER['PHP_SELF']."?dev_name=".$row["dev_name"].">".$row["dev_name"]."</a></td></tr>";
}
echo "<tr><td>";
for($page = 1; $page<= $number_of_page; $page++) {  
  echo "<a href = ".$_SERVER['PHP_SELF']."?page=" . $page .">" . $page . " </a>";  
}





#$alldev = $conn->query("SELECT dev_name from devices where vendor='Juniper'");
#foreach ($alldev as $devices) {
#echo "<tr><td><a href=".$_SERVER['PHP_SELF']."?dev_name=".$devices["dev_name"].">".$devices["dev_name"]."</a></td></tr>";
#}
echo "
</td></tr></table></div></div></body></html>
";
unset($_REQUEST["action"]);
}
}
?>