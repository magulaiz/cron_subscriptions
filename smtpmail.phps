#!/usr/bin/php
<?php
/**
 * This example shows settings to use when sending via Google's Gmail servers.
 */

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Europe/Madrid');

require '/usr/share/php/libphp-phpmailer/PHPMailerAutoload.php';

//Create a new PHPMailer instance
$mail = new PHPMailer;

$mail->CharSet = 'UTF-8';

//Tell PHPMailer to use SMTP
$mail->isSMTP();

//Enable SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
$mail->SMTPDebug = 0;

//Ask for HTML-friendly debug output
$mail->Debugoutput = 'html';

//Set the hostname of the mail server
$mail->Host = 'SSL0.OVH.NET';
// use
// $mail->Host = gethostbyname('smtp.gmail.com');
// if your network does not support SMTP over IPv6

//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
$mail->Port = 465;

//Set the encryption system to use - ssl (deprecated) or tls
$mail->SMTPSecure = 'ssl';

//Whether to use SMTP authentication
$mail->SMTPAuth = true;

//Username to use for SMTP authentication - use full email address for gmail
$mail->Username = 'info@bierzo.online';

//Password to use for SMTP authentication
$mail->Password = '1do9re6na5';

//Set who the message is to be sent from
$mail->setFrom('info@bierzo.online', 'formacion.bierzo.online');

//Set an alternative reply-to address
//$mail->addReplyTo('$argv[1]', 'First Last');

//Set who the message is to be sent to
$mail->addAddress($argv[1], $argv[2]);

//Set the subject line
$mail->Subject = $argv[2].': Datos desde formacion.bierzo.online';

//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
//$mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
$body ='
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Datos de Usuario</title>
</head>
<body>
<h2>¡Enhorabuena!<h2>
<h4>Se ha configurado el entorno de trabajo con los siguientes datos de accesso:</h4></br />
Usuario SSH y MySQL: <strong>'.$argv[2].'</strong><br />
Contraseña SSH MySQL: <strong>'.$argv[3].'</strong><br /><br /><br />
Acceso SSH: <strong>#ssh '.$argv[2].'@bierzo.online</strong><br /><br /> 
Acceso a MySQL(PHPMyAdmin): <a href="http://bierzo.online/redil">http://bierzo.online/redil</a></strong><br /><br />
Para la configuración del sitio drupal accede a:<br /> 
<a href="http://'.$argv[4].'">http://'.$argv[4].'</a>
</body>
</html>
';
$mail->msgHTml($body);
//Replace the plain text body with one created manually
$mail->AltBody = 'Mensaje enviado desde formacion.bierzo.online a '.$argv[2].' ('.$argv[1].')';

//Attach an image file
//$mail->addAttachment('images/phpmailer_mini.png');

//send the message, check for errors
if (!$mail->send()) {
    echo "Mailer Error: " . $mail->ErrorInfo.PHP_EOL;
}
?>
