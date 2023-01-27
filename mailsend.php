<?php

require '/usr/share/php/libphp-phpmailer/src/PHPMailer.php';

require '/usr/share/php/libphp-phpmailer/src/SMTP.php';

 

//Declare the object of PHPMailer

$email = new PHPMailer\PHPMailer\PHPMailer();

//Set up necessary configuration to send email

$email->IsSMTP();

$email->SMTPAuth = true;

$email->SMTPSecure = 'ssl';

$email->Host = "smtp.gmail.com";

$email->Port = 465;

//Set the gmail address that will be used for sending email

$email->Username = "zura.shaishmelashvili@gmail.com";

//Set the valid password for the gmail address

$email->Password = "Pls4mail365";

//Set the sender email address

$email->SetFrom("ura.shaishmelashvili@gmail.com");

//Set the receiver email address

$email->AddAddress("zshaishmelashvili@silknetcom");

//Set the subject

$email->Subject = "Testing Email";

//Set email content

$email->Body = "Hello! use PHPMailer to send email using PHP";


if(!$email->Send()) {

  echo "Error: " . $email->ErrorInfo;

} else {

  echo "Email has been sent.";

}

?>