<?php

function ConexionSistema0($host,$user,$password,$database,$puerto)
{
	$conn=new mysqli($host, $user, $password, $database, $puerto);
	if ($conn->connect_errno) { echo "Falló la conexión a MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error; }
	$charset="utf8";
	if (!$conn->set_charset($charset)) { printf("Error cargando el conjunto de caracteres $charset: %s\n", $conn->error); }  
	return $conn;
}


function ReportLog($mysqli,$logType,$comments)
{
	$mysqli->query("INSERT INTO dex_log(tipo_log,`pid`,started,comments) VALUES('" . $logType . "'," . getmypid() . ",now(),'" . mysqli_real_escape_string($mysqli,$comments) . "');" );
	$id_log=$mysqli->insert_id;
	return $id_log;
}


function AddLog($mysqli,$id_log,$comments,$err, $registros)
{
	$mysqli->query("UPDATE dex_log SET finished=now(), comments=concat(comments,'" . mysqli_real_escape_string($mysqli,$comments) . "'),errors=" . $err . ", records=" . $registros . " WHERE id_log=" . $id_log . ";");
	return 0;
}



function EjecutaQuery($mysqli,$queryForm,$log=0)
{
	$err=0;
	if ($log==1) $log_id=ReportLog($mysqli,"SQL",$queryForm);
	
	$resultado = $mysqli->query($queryForm);
	$registros=$mysqli->affected_rows;

	if (!$resultado) {
		$log=$mysqli->error . "\nSQL: " . $queryForm;
		$err=1;
		ReportLog($mysqli,"ERROR",$log);
		echo "\nERROR: " . $mysqli->error . "\n";
		echo "SQL: " . $queryForm;
	}
	if ($log==1) AddLog($mysqli,$log_id,"",$err,$registros);
return $resultado;
}

function EjecutaQueryFila($mysqli,$sql,$log=0)
{
	$resultado = EjecutaQuery($mysqli,$sql,$log);
	$fila = $resultado->fetch_assoc();
	$resultado->close();
	return $fila;
}

function EjecutaQueryFila_campos($mysqli,$sql,$log=1)
{
	$resultado = EjecutaQuery($mysqli,$sql,$log);
	$campos=array();
	$definicion_campos = $resultado->fetch_fields();
	foreach($definicion_campos as $definicion_campo)
	{
		array_push($campos,$definicion_campo->name);
	}
	$resultado->close();
	return $campos;
}


function Sustituye ($texto,$delim_i,$delim_d,$asignaciones)
{
	foreach ($asignaciones as $key => $value)
	{
		if($value==null) { $value="";}
		$texto=str_replace($delim_i . $key . $delim_d, $value, $texto);
	}
return $texto;
}


function Sustituye_array ($array_origen,$delim_i,$delim_d,$asignaciones)
{
	$asignacion=array();
	foreach ($array_origen as $key => $value)
	{
		$asignacion[$key]=Sustituye($value,$delim_i,$delim_d,$asignaciones);
	}
return $asignacion;
}



function Sustituye_s ($mysqli,$texto,$delim_i,$delim_d,$asignaciones)
{
	foreach ($asignaciones as $key => $value)
	{
		if($value==null) { $value="";}
		$texto=str_replace($delim_i . $key . $delim_d, mysqli_real_escape_string($mysqli, $value), $texto);
	}
return $texto;
}

function NZ($valor,$valor_null)
{
	return (is_null($valor))?$valor_null:$valor;
}


function Conexion() {return ConexionSistema0("hostingmysql292","MFDR22_root","Masquefa88","adveocomercial_com_plan_marketing",3306);}


function linea($handle)
{
	$s=str_replace("\r\n","",fgets($handle));
	$s=str_replace("\n","",$s);
	return $s;
}

function AbreFichero($fichero,$modo="r")
{
if($modo=="r" && !file_exists($fichero)) die("Fichero $fichero no encontrado");
else
  {
	$handle = fopen($fichero, $modo);
	if (!$handle)
	{
		die("Error al abrir fichero $fichero.");
	}
	return $handle; 
}
return 0;
}
function Last_Char($fichero)
{
	return shell_exec("tail -c 1 \"" . $fichero . "\" | hexdump -v -e '1/1 \"x%02X\" '");
}


?>