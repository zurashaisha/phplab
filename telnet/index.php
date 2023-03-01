<?php
error_reporting(0);
session_start();
require_once "PHPTelnet.php";
$telnet = new PHPTelnet(); // using a function from the included script
$result = $telnet->Connect('192.168.0.42','hioxsoftwares','hioxindia'); //IP address of the router, username, password
echo "<title>PHP Telnet Script</title>";
echo "<div class='resp_code'>";
echo "<center><b>PHP Telnet Script</b></center>";
echo "<br><br>";
		switch ($result)
		{
			case 0: 
				echo "<b><font style='color:green;'>Connected!!</font></b>";
				echo "<br><br>";
				$result = str_replace("Password:"," ",$result);
				$result=explode('*',$result);
				echo $result[0];
				echo "<br><br>";
				break; 
			case 1:
				echo "<font style='color:red;'>PHP Telnet Connecttion failed: Unable to open network connection</font>";
			break; 
			case 2:
				echo "<font style='color:red;'>PHP Telnet Connecttion failed: Unknown host</font>";
			break; 
			case 3:
				echo "<font style='color:red;'>PHP Telnet Connecttion failed: Login failed</font>";
			break; 
			case 4:
				echo "<font style='color:red;'>PHP Telnet Connecttion failed: Your PHP version does not support PHP Telnet</font>";
			break; 
		}
		echo '<div align="center"><a id="dum" style="text-decoration:none;color: #dadada;text-align:center;" href="https://www.hscripts.com">
		&copy;h
		</a></div>';
		echo "</div>";
?>
<style>
.resp_code
{
margin:5px 10px 10px 300px;
padding:10px 20px 10px 20px;
font:normal 1em/1.3em Tahoma, Geneva, sans-serif;
color:#333;
background:#f8f8f8;
border:#ddd 1px solid;
border-radius:.25em;
overflow:auto;width:50%;
}
@media screen and (max-width: 480px)
{
.resp_code{width:auto !important;margin:0px !important;}
}
</style>