<?php
$servername = "localhost";
$username = "silknet";
$password = "Silknet#1";
$swcomm = "@JuN1p3R";
$router = "172.31.255.13";
$router_username = "zura";
$router_password = "Pls4mail";
$ssh_user = "zura";
$ssh_pass = "Pls4mail";
$dbname = "MPLS_NEW";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); }

    snmp_set_oid_numeric_print(TRUE);
    snmp_get_quick_print();
    snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
    snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

?>