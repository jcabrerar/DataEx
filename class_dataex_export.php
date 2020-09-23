<?php
/*
function export_txt($mysqli, $log_id_sched, $nombre_fichero, $sql) {
    log_anota_traza($mysqli, $log_id_sched, "export_txt(\$mysqli, $log_id_sched, $nombre_fichero, $sql);", "I");    
    $separador = chr(9);
    log_anota_traza($mysqli, $log_id_sched, "Fichero salida: \n$nombre_fichero\n");
	$f = 0;
    $DescriptorFichero = fopen($nombre_fichero, "w");
    
    if (!$DescriptorFichero)
    {
	log_anota_traza($mysqli, $log_id_sched, "ERROR: No se ha podido abrir el fichero para escritura","E");
    }
    else
    {
    log_anota_traza($mysqli, $log_id_sched, "Ejecutando SQL: \n$sql\n");
    $resultado = EjecutaQuery($mysqli, $sql);
    
    if ($resultado) {
	if ($log_id_sched <> -1) log_anota_traza($mysqli, $log_id_sched, "Sentencia ejecutada con éxito\n");
	$info_campo = $resultado->fetch_fields();
	$linea = "";
	foreach ($info_campo as $valor)
	    $linea = $linea . $valor->name . $separador;
	$linea = substr($linea, 0, strlen($linea) - 1);

	fputs($DescriptorFichero, utf8_decode($linea). "\n");

	$f = 1;
	while ($fila = $resultado->fetch_assoc()) {
	    $c = 0;
	    $linea = "";
	    foreach ($fila as $campo => $valor) {
		
		switch ($info_campo[$c]->type) {
		    case 0:
		    case 1:
		    case 2:
		    case 3:
			$linea = $linea . round($valor, 8) . $separador;
			break;
		    case 4:
		    case 5:
			//$numero=str_replace(".", ",", $valor);
			//$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow ($c,$f,(double) $valor);
//  fputs($DescriptorFichero, "	    			<Cell><Data ss:Type=\"Number\">" . round($valor, 8) . "</Data></Cell>\n");
			$linea = $linea . round($valor, 8) . $separador;
			break;
		    default:
			//$objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($c,$f,$valor, PHPExcel_Cell_DataType::TYPE_STRING);
//  fputs($DescriptorFichero, "	    			<Cell><Data ss:Type=\"String\">" . $valor . "</Data></Cell>\n");
			$linea = $linea . $valor . $separador;
			break;
		}
		$c++;
	    }
	    $linea = substr($linea, 0, strlen($linea) - 1);
	    fputs($DescriptorFichero, utf8_decode ($linea) . "\n");

	    if ($f % 10000 == 0) {
		if ($log_id_sched <> -1)
		    log_anota_traza($mysqli, $log_id_sched, "Grabada fila : " . $f . "\n");
	    }
	    $f++;
	}
	$resultado->close();
    }
    else
    {
	if ($log_id_sched <> -1) log_anota_traza($mysqli, $log_id_sched, "La Sentencia falló\n");
    
    }
    fclose($DescriptorFichero);
    }
return $f;
}
*/

function export_txt($mysqli, $log_id_sched, $nombre_fichero, $sql, $cabeceras) {
    log_anota_traza($mysqli, $log_id_sched, "export_txt(\$mysqli, $log_id_sched, $nombre_fichero, $sql);", "I");    
    $separador = chr(9);
    log_anota_traza($mysqli, $log_id_sched, "Fichero salida: \n$nombre_fichero\n");
	$f = 0;
    $DescriptorFichero = fopen($nombre_fichero, "w");
    
    if (!$DescriptorFichero)
    {
	log_anota_traza($mysqli, $log_id_sched, "ERROR: No se ha podido abrir el fichero para escritura","E");
    }
    else
    {
    log_anota_traza($mysqli, $log_id_sched, "Ejecutando SQL: \n$sql\n");
    $resultado = EjecutaQuery($mysqli, $sql);
    
    if ($resultado) {
	if ($log_id_sched <> -1) log_anota_traza($mysqli, $log_id_sched, "Sentencia ejecutada con éxito\n");

	$linea = "";
	$info_campo = $resultado->fetch_fields();
	if ($cabeceras=="1")
	{
		foreach ($info_campo as $valor)
			$linea = $linea . $valor->name . $separador;
		$linea = substr($linea, 0, strlen($linea) - 1);
		fputs($DescriptorFichero, utf8_decode($linea). "\n");
	}

	$f = 1;
	while ($fila = $resultado->fetch_assoc()) {
	    $c = 0;
	    $linea = "";
	    foreach ($fila as $campo => $valor) {
		
		switch ($info_campo[$c]->type) {
		    case 0:
		    case 1:
		    case 2:
		    case 3:
			$linea = $linea . round($valor, 8) . $separador;
			break;
		    case 4:
		    case 5:
			//$numero=str_replace(".", ",", $valor);
			//$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow ($c,$f,(double) $valor);
//  fputs($DescriptorFichero, "	    			<Cell><Data ss:Type=\"Number\">" . round($valor, 8) . "</Data></Cell>\n");
			$linea = $linea . round($valor, 8) . $separador;
			break;
		    default:
			//$objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($c,$f,$valor, PHPExcel_Cell_DataType::TYPE_STRING);
//  fputs($DescriptorFichero, "	    			<Cell><Data ss:Type=\"String\">" . $valor . "</Data></Cell>\n");
			$linea = $linea . $valor . $separador;
			break;
		}
		$c++;
	    }
	    $linea = substr($linea, 0, strlen($linea) - 1);
	    fputs($DescriptorFichero, utf8_decode ($linea) . "\n");

	    if ($f % 10000 == 0) {
		if ($log_id_sched <> -1)
		    log_anota_traza($mysqli, $log_id_sched, "Grabada fila : " . $f . "\n");
	    }
	    $f++;
	}
	$resultado->close();
    }
    else
    {
	if ($log_id_sched <> -1) log_anota_traza($mysqli, $log_id_sched, "La Sentencia falló\n");
    
    }
    fclose($DescriptorFichero);
    }
return $f;
}






function export_txt_informe($mysqli, $log_id_sched, $nombre_fichero, $sql) {
    log_anota_traza($mysqli, $log_id_sched, "export_txt_informe(\$mysqli, $log_id_sched, $nombre_fichero, $sql);", "I");    
//    $separador = chr(9);
    log_anota_traza($mysqli, $log_id_sched, "Fichero salida: \n$nombre_fichero\n");

    $DescriptorFichero = fopen($nombre_fichero, "w");
    
    if (!$DescriptorFichero)
    {
	log_anota_traza($mysqli, $log_id_sched, "ERROR: No se ha podido abrir el fichero para escritura","E");
    }
    else
    {
    log_anota_traza($mysqli, $log_id_sched, "Ejecutando SQL: \n$sql\n");
    $resultado = EjecutaQuery($mysqli, $sql);
    
    if ($resultado) {
	if ($log_id_sched <> -1) log_anota_traza($mysqli, $log_id_sched, "Sentencia ejecutada con éxito\n");
	$info_campo = $resultado->fetch_fields();
	$linea = "";
        /*
	foreach ($info_campo as $valor)
	    $linea = $linea . $valor->name . $separador;
	$linea = substr($linea, 0, strlen($linea) - 1);

	fputs($DescriptorFichero, $linea . "\n");
*/
	$f = 1;
	while ($fila = $resultado->fetch_assoc()) {
	    $c = 0;
	    $linea = "";
	    foreach ($fila as $campo => $valor) {
		$linea=$linea . $info_campo[$c]->name . ": ";
		switch ($info_campo[$c]->type) {
		    case 0:
		    case 1:
		    case 2:
		    case 3:
			$linea = $linea . round($valor, 8) . $separador;
			break;
		    case 4:
		    case 5:
			//$numero=str_replace(".", ",", $valor);
			//$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow ($c,$f,(double) $valor);
//  fputs($DescriptorFichero, "	    			<Cell><Data ss:Type=\"Number\">" . round($valor, 8) . "</Data></Cell>\n");
			$linea = $linea . round($valor, 8) . $separador;
			break;
		    default:
			//$objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($c,$f,$valor, PHPExcel_Cell_DataType::TYPE_STRING);
//  fputs($DescriptorFichero, "	    			<Cell><Data ss:Type=\"String\">" . $valor . "</Data></Cell>\n");
			$linea = $linea . $valor . $separador;
			break;
		}
		$c++;
	    }
	    $linea = substr($linea, 0, strlen($linea) - 1);
	    fputs($DescriptorFichero, $linea . "\n");

	    if ($f % 10000 == 0) {
		if ($log_id_sched <> -1)
		    log_anota_traza($mysqli, $log_id_sched, "Grabada fila : " . $f . "\n");
	    }
	    $f++;
	}
	$resultado->close();
    }
    else
    {
	if ($log_id_sched <> -1) log_anota_traza($mysqli, $log_id_sched, "La Sentencia falló\n");
    
    }
    fclose($DescriptorFichero);
    }
}



?>