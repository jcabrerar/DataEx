<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function log_crea_entrada($mysqli, $run_id, $step_id)
{
 if ($run_id<>-1)
    {
    $mysqli->query("INSERT INTO dex_scheduler_log (run_id,id_step,started,comments) select ". $run_id . " run_id, id_step ,now() started,descripcion comments from dex_steps WHERE id_step=" . $step_id);
    return $mysqli->insert_id;
   }
return -1;
}

function log_cierra_entrada($mysqli, $log_id, $registros , $errores = 0)
{
    if ($log_id <> -1)
    $mysqli->query("UPDATE dex_scheduler_log set finished=now(), records=" . $registros .", errors=" . $errores . " WHERE id_log=" . $log_id);
}

function log_anota_traza($mysqli, $log_id, $message, $tipo_traza="I")
{
        if ($log_id<>-1)
		{
        $mysqli->query("INSERT INTO dex_scheduler_log_detail (id_log,started,message,trace_category) values(".$log_id.",now(),'". mysqli_real_escape_string($mysqli, $message) ."','" . mysqli_real_escape_string($mysqli, $tipo_traza) . "')");
		return $mysqli->insert_id;
		}
    else
        ReportLog($mysqli,$tipo_traza,$message);
	return -1;
}

function log_anota_traza_cierra($mysqli, $log_id)
{
if ($log_id <> -1)
    $mysqli->query("UPDATE dex_scheduler_log_detail set finished=now() WHERE id=" . $log_id);
}

?>