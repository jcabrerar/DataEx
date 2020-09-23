<?php
  // primero hay que incluir la clase phpmailer para poder instanciar
  //un objeto de la misma
  require "includes/class.smtp.php";
  require "class.phpmailer.php";

  //instanciamos un objeto de la clase phpmailer al que llamamos
  //por ejemplo mail
  
  function send_mail($mail_from,$mail_from_name,$mail_to,$mail_cc,$mail_bcc,$subject,$body,$attach,$is_html,$priority )
  {
	if (empty($body)) $body="\n";
	if (empty($mail_to) || empty($mail_from)) return 0;
	
	$mail = new phpmailer();
	$mail->CharSet = 'UTF-8';
	
	if (!empty($is_html)) $mail->IsHTML(true);
	
	$mail->PluginDir = "includes/";
	$mail->Mailer = "smtp";
	$mail->Host = "10.28.5.140";
	$mail->From = $mail_from;
	$mail->FromName = $mail_from_name;

	//el valor por defecto 10 de Timeout es un poco escaso dado que voy a usar
	//una cuenta gratuita, por tanto lo pongo a 30
	$mail->Timeout=30;
	
	if (!empty($attach) ) 
	foreach (explode(";",$attach) as $attach_0) 
	{ 
		if (!empty($attach_0))
		{
			if (file_exists ($attach_0 ))
				$mail->AddAttachment($attach_0);
			else
			{
				echo ("ERROR: No se ha encontrado el fichero '$attach_0'\n");
				return 0;
			}
		}	
	}

	if ($priority==1 )
	{
		$mail->priority=1;
		$mail->HeaderLine('X-Priority', $mail->Priority);
		$mail->AddCustomHeader("X-MSMail-Priority: High");
		// Not sure if Priority will also set the Importance header:
		$mail->AddCustomHeader("Importance: High");
	}

	if ($priority==5)
	{
		$mail->priority=$priority;
		$mail->AddCustomHeader("X-MSMail-Priority: Low");
		// Not sure if Priority will also set the Importance header:
		$mail->AddCustomHeader("Importance: Low");
	}

	//Indicamos cual es la dirección de destino del correo
	foreach (explode(";",$mail_to) as $mail_to_0) 
	{ 
		if (!empty($mail_to_0))
			$mail->AddAddress($mail_to_0);
	}

  //Indicamos cual es la dirección de destino del correo
	foreach (explode(";",$mail_cc) as $mail_cc_0) 
	{ 
		if (!empty($mail_cc_0))
			$mail->AddCC($mail_cc_0);
	}
	
	foreach (explode(";",$mail_bcc) as $mail_bcc_0) 
	{ 
		if (!empty($mail_bcc_0))
			$mail->AddBCC($mail_bcc_0);
	}
	
	//$mail->Subject = utf8_decode($subject); 
	//$mail->Body = utf8_decode($body);
	$mail->Subject = $subject; 
	$mail->Body = $body;

	//Definimos AltBody por si el destinatario del correo no admite email con formato html
	$mail->AltBody = "";

	//se envia el mensaje, si no ha habido problemas
	//la variable $exito tendra el valor true
	$exito = $mail->Send();

	//Si el mensaje no ha podido ser enviado se realizaran 4 intentos mas como mucho
	//para intentar enviar el mensaje, cada intento se hara 5 segundos despues
	//del anterior, para ello se usa la funcion sleep
	$intentos=1;
	while ((!$exito) && ($intentos < 5)) 
	{
		sleep(5);
		//echo $mail->ErrorInfo;
		$exito = $mail->Send();
		$intentos=$intentos+1;
	}

	if(!$exito)
	{
		echo ("ERROR: No se ha podido enviar el correo después de $intentos intentos\n");
		echo ("Parámetros:\n");
		echo ("mail_from      : $mail_from\n");
		echo ("mail_from_name : $mail_from_name\n");
		echo ("mail_to        : $mail_to\n");
		echo ("subject        : $subject\n");
		echo ("body           : $body\n");
		echo ("attach         : $attach\n");
		echo ("is_html         : $is_html\n");
		return 0;
	}
	else
	{
		return 1;
	}

	return 0;
}
?>