
<?php

require("c.php");

require("class_dataex_import.php");

require("class_dataex_export.php");

require("class_dataex_log.php");

require("class_mail.php");

function exec_op($mysqli, $log_id_sched, $parametros )
{
    $r=1;
    if ($parametros["action_type"]=="SHELL")
    {
		$shell=Sustituye($parametros["action"],"[","]",$parametros);
		log_anota_traza($mysqli, $log_id_sched, $shell, "I");
		$salida=shell_exec($shell);
		if ($salida<>"") log_anota_traza($mysqli, $log_id_sched, $salida, "I");
    }
    elseif($parametros["action_type"]=="PHP")
    {
        $funcion_php=$parametros["action"];
        log_anota_traza($mysqli, $log_id_sched, "Realizando llamada PHP a $funcion_php", "I");
		$r=call_user_func($funcion_php,$mysqli,$log_id_sched, $parametros);
    }
    return $r;
}

function exec_sql($mysqli, $log_id_sched, $parametros)
{
    $r = 0;
    if ($parametros["sentencia_sql"] != null && $parametros["sentencia_sql"] != "") 
	{
		foreach (explode(";", $parametros["sentencia_sql"]) as $sql) 
		{
			if ($sql != null && $sql != "" && str_replace("\n", "", $sql) != "") 
			{
				log_anota_traza($mysqli, $log_id_sched, $sql);
				$r = EjecutaQuery($mysqli, $sql,1);
			}
		}
    }
    return $r;
}

function php_content($mysqli, $log_id_sched, $parametros)
{
	if (NZ($parametros["php_content"],"")!="")
	{
			$salida=shell_exec("php " & $parametros["php_content"]);
			//$parametros["mail_body"]=
	}
	return $parametros;
}


function exec_email0($mysqli, $log_id_sched, $parametros)
{
	return send_mail(
		$parametros["mail_from"],
		$parametros["mail_from_name"],
		$parametros["mail_to"],
		NZ($parametros["mail_cc"],""),
		NZ($parametros["mail_bcc"],""),
		$parametros["mail_subject"],
		$parametros["mail_body"],
		$parametros["mail_attach"],
		$parametros["mail_is_html"],
		$parametros["mail_priority"]
	);
}

function exec_email($mysqli, $log_id_sched, $parametros)
{
    $r = 0;
	if (empty($parametros["sentencia_sql"])) return exec_email0($mysqli, $log_id_sched, $parametros);
	else
	{
		$resultado = EjecutaQuery($mysqli , $parametros["sentencia_sql"] , 1);
		while ($fila = $resultado->fetch_assoc()) 
		{
			$parametros_s=Sustituye_array($parametros,"[","]",$fila);
			$r=exec_email0($mysqli, $log_id_sched, $parametros_s);
			unset($parametros_s);
		}
	}
    return $r;
}


function exec_sql_unica($mysqli, $log_id_sched, $parametros)
{
   if ($parametros["sentencia_sql"] != null && $parametros["sentencia_sql"] != "") {
        $sql = $parametros["sentencia_sql"];
	if ($sql != null && $sql != "" && str_replace("\n", "", $sql) != "") {
	    $r = EjecutaQuery($mysqli, $sql,1);
            log_anota_traza($mysqli, $log_id_sched, $sql);
	}
    }
    return $r;
}

function exec_sql_grupo($mysqli, $log_id_sched, $parametros)
{
	
    $rr = EjecutaQuery($mysqli, "SELECT sentencia_sql FROM dex_sql WHERE grupo='" . $parametros["id_grupo"] . "' order by grupo,tipo,orden");
    $log_id_grupo_sql = ReportLog($mysqli, "GRUPO_SQL", "SELECT sentencia_sql FROM dex_sql WHERE grupo='" . $parametros["id_grupo"] . "' order by grupo,tipo,orden");
    $n_sqls = 0;
    while ($ffila = $rr->fetch_assoc()) 
	{
		if ($ffila["sentencia_sql"] != null && $ffila["sentencia_sql"] != "") 
		{
			$n_sqls++;
			foreach (explode(";", $ffila["sentencia_sql"]) as $sql) 
			{
				if ($sql != null && $sql != "" && str_replace("\n", "", $sql) != "" && $sql != " ") 
				{
					$r = EjecutaQuery($mysqli, $sql,1);
                    log_anota_traza($mysqli, $log_id_sched, $sql);
				}
			}
		}
    }
	
    AddLog($mysqli, $log_id_grupo_sql, "", 0, $n_sqls);
    $r = $n_sqls;
    $rr->close();
    return $r;
}

function exec_export_txt_sql($mysqli, $log_id_sched, $parametros)
{
    return export_txt($mysqli, $log_id_sched, $parametros["nombre_fichero"], $parametros["sentencia_sql"],$parametros["export_cabecera"]);
}

function exec_copy($mysqli, $log_id_sched, $parametros)
{
    return Importa($mysqli, $parametros["fichero"], $parametros["id_modelo"], $parametros["tabla_destino"], "COPY");
}

function exec_update($mysqli, $log_id_sched, $parametros)
{
    return Importa($mysqli, $parametros["fichero"], $parametros["id_modelo"], $parametros["tabla_destino"], "UPDATE");
}

function exec_insert($mysqli, $log_id_sched, $parametros)
{
    return Importa($mysqli, $parametros["fichero"], $parametros["id_modelo"], $parametros["tabla_destino"],"INSERT");
}


function EjecutaPlan($mysqli, $id_plan, $id_step = -1, $run_id = -1) 
{
    $r = -1;
    $log_id_sched = -1;
    $errores = 0;
	
    $resultado = EjecutaQuery($mysqli, "SELECT a.*, b.id_modelo, b.carpeta, b.carpeta_salida, b.wildcar, b.tipo, a.sentencia_sql, a.id_grupo, a.shell_cmd, c.action_type, c.action, c.tipo FROM dex_steps a LEFT JOIN dex_cola b on (a.id_cola=b.id_cola) LEFT JOIN dex_operacion c on (a.id_op=c.id_op) WHERE (a.id_step=" . $id_step . (($id_step==-1)?" OR 1 ":"") . ") and now() between validez_desde and validez_hasta and a.id_plan='" . $id_plan . "' order by a.orden, a.id_step;",1);
    $num_rows = $resultado->num_rows;
    while ($fila = $resultado->fetch_assoc()) 
	{
		$log_id_plan = ReportLog($mysqli, "DEBUG", "EjecutaPlan($id_plan," . $fila["id_step"] . "):" . $fila["id_op"] . "(" . $fila["id_modelo"] . ")");
		$log_id_sched = log_crea_entrada($mysqli, $run_id, $fila["id_step"]);   
		$r = 0;
		$errores = 0;
		$tiempo_inicio = microtime(true);
        
		if ($fila["tipo"] == "I") 
		{
			$log_id = ReportLog($mysqli, "USER", $fila["id_op"]);
			$r = exec_op($mysqli, $log_id_sched, $fila);
			$tiempo_fin = microtime(true);
			$t = ($tiempo_fin - $tiempo_inicio) * 1000;
			AddLog($mysqli, $log_id, "\n" . $fila["id_op"] . "\n" . "Tiempo\t:\t" . $t . " ms ", 0, 0);
		} 
		else
			foreach (glob($fila["carpeta"] . $fila["wildcar"]) as $fichero) 
			{
				$log_id = ReportLog($mysqli, "USER", "Fichero: " . $fichero);
				$fila["fichero"]=$fichero;
				$r = exec_op($mysqli, $log_id_sched, $fila);
			
				echo("\nArchivando '" . $fichero . "' en '" . $fila["carpeta_salida"] . basename($fichero) . "' ... ");
				$err=rename($fichero, $fila["carpeta_salida"] . basename($fichero));
				echo($err?"OK\n":"ERROR\n");
		
				$tiempo_fin = microtime(true);
				$media = ($r == 0) ? "-" : ($tiempo_fin - $tiempo_inicio) / $r * 1000;
				AddLog($mysqli, $log_id, "\n" . $fila["tabla_destino"] . "\n" . "Media\t:\t" . $media . " ms / reg", 0, $r);

				exec_sql($mysqli, $log_id_sched, $fila);
              
				echo "\n";
				echo "Fichero : <b>" . $fichero . "</b><br>\n";
				echo "Tabla :" . $fila["tabla_destino"] . "<br>\n";
				echo "Registros\t:\t" . $r . "<br>\n";
				echo "Tiempo\t:\t" . ($tiempo_fin - $tiempo_inicio) . "<br>\n";
				echo "Media\t:\t" . $media . " ms / reg<br><br>\n";
			}
			log_cierra_entrada($mysqli, $log_id_sched, $r, $errores);
    }
$resultado->close();
return $num_rows;
}

function EjecutaPlanLog($mysqli, $id_plan, $id_step = -1, $run_id = -1) 
{
    $log_id = ReportLog($mysqli, "APP", "EjecutaPlan(mysqli,$id_plan,$id_step);");
    set_time_limit(0);
    $num = EjecutaPlan($mysqli, $id_plan, $id_step, $run_id);
    AddLog($mysqli, $log_id, "Pasos seleccionados en fecha activa:" . $num, 0, 0);
}

function book($mysqli, $run_id) 
{
    $r = 1;
    $sql = "INSERT INTO dex_plan_arbiter ( run_id, server, pid, t_arbitration ) values (" . $run_id . ",'" . gethostname() . "'," . getmypid() . ", now())";

    $mysqli->query($sql);
    if ($mysqli->error != "")
	$r = 0;
    return $r;
}

function ChkSched($mysqli) 
{
    $sql = "select run_id,id_plan from dex_scheduler where status='O' and run_date<now() order by run_date";
    $resultado = $mysqli->query($sql);
    while ($fila = $resultado->fetch_assoc()) 
	{
		if (book($mysqli, $fila["run_id"])) 
		{
			$mysqli->query("UPDATE dex_scheduler SET status='R', start_date=now(),executed_by='" . gethostname() . "',pid=" . getmypid() . " where run_id=" . $fila["run_id"]);
			$mysqli->query("INSERT INTO dex_scheduler (run_date,status,rsched_in,rsched_unit,id_plan) select fn_date_add(  run_date,rsched_in,rsched_unit),'O' status,rsched_in,rsched_unit,id_plan from dex_scheduler where run_id=" . $fila["run_id"]);
			EjecutaPlanLog($mysqli, $fila["id_plan"], -1, $fila["run_id"]);
			$mysqli->query("UPDATE dex_scheduler SET status='F', end_date=now() where run_id=" . $fila["run_id"]);
		} 
		else 
		{
			echo "Conflicto al intentar ejecutar " . $fila["run_id"] . "\n";
		}
    }
}


$id_plan = "";
$id_step = -1;

if (!empty($_GET['id_plan']) && !empty($_GET['id_step'])) 
{
    $id_plan = $_GET['id_plan'];
    $id_step = $_GET['id_step'];
}

if ($argc == 1) 
{
    $mysqli = Conexion();
    ChkSched($mysqli);
    $mysqli->close();
} 
elseif ($argc == 3) 
{
    echo "Número de argumentos: " . $argc . "\n";
    for ($i = 0; $i < $argc; $i++) {
	echo "argumento " . $i . ": " . $argv[$i] . "\n";
}

$id_plan = $argv[1];
$id_step = $argv[2];

}

if ($id_plan != "") 
{
    $mysqli = Conexion();
    EjecutaPlanLog($mysqli, $id_plan, $id_step);
    $mysqli->close();
}
?>
