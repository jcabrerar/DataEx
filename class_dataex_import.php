<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'Classes/PHPExcel.php';
require_once 'Classes/PHPExcel/IOFactory.php';

class Fichero {

    protected $cabeceras_en_fila;
    protected $datos_desde_fila;
    public $separador = "\t";
    private $ficheros_temporales=0;
    private $nombre_fichero="";

    protected $handle;
    protected $id_encoding;

    function __construct($id_encoding, $cabeceras_en_fila, $datos_desde_fila) {
		$this->id_encoding = $id_encoding;
        $this->cabeceras_en_fila = $cabeceras_en_fila;
        $this->datos_desde_fila = $datos_desde_fila;
    }

    protected function mover_a_cabecera() {
        // Sólo se puede llamar a esta función una vez, después de abrir el fichero y esincompatible con la llamada a mover_a_datos()
        for ($i = 1; $i < $this->cabeceras_en_fila; $i++)
        {
            linea($this->handle);
        }
    }

    protected function mover_a_datos() {
        for ($i = 1; $i < $this->datos_desde_fila; $i++){
            linea($this->handle);
        }
    }

    protected function abre_fichero($fichero) {
	$this->nombre_fichero=$fichero;
        $this->handle = AbreFichero($fichero, "r");
    }

    public function retorna_campos($fichero) {
        $this->abre_fichero($fichero);
        $this->mover_a_cabecera();
        $campos = $this->lee_registro();
        $campos_unicos = array();
        $campos_unicos_M = array();
        foreach ($campos as $campo) {
            $sufijo = "";
            $campo = trim($campo); //2014/04/03 Los campos con espacios antes o después del nombre del campo provocan errores en Mysql
            if ($this->cabeceras_en_fila == 0 || $campo == "") {
                $campo = "Campo";
                $sufijo = "1";
            }
            $campo_M = strtoupper($campo);
            while (in_array($campo_M . $sufijo, $campos_unicos_M)) {
                if ($sufijo == ""){
                    $sufijo = "1";
                }
                else{
                    $sufijo = $sufijo + 1;
                }
            }
            array_push($campos_unicos, $campo . $sufijo);
            array_push($campos_unicos_M, $campo_M . $sufijo);
        }
        $this->cierra_fichero();
        return $campos_unicos;
    }

    protected function lee_registro() {
        
    }

    protected function cierra_fichero() {
        
    }

    protected function final_fichero() {
        
    }

    
    protected function filename_tmp0($indice) {
	return dirname($this->nombre_fichero) . "/tmp" . $indice . "_" . basename($this->nombre_fichero);
    }
    
    protected function filename_tmp() {
	$this->ficheros_temporales++;
	return  $this->filename_tmp0($this->ficheros_temporales);
    }
    
    public function convertir_delim($fichero_in) {
	$fichero_out = $this->filename_tmp();
	
	$this->fichero_temporal_delim = $fichero_out;
	
        $this->abre_fichero($fichero_in);
        $this->mover_a_datos();
        $handle_out = AbreFichero($fichero_out, "w");
        $lineas_importadas = 0;
        while (!$this->final_fichero()) {
            $registro = $this->lee_registro();
            if (!fwrite($handle_out, implode($this->separador, $registro) . "\r\n"))
                break;
            $lineas_importadas++;
        }
        $this->cierra_fichero();
        fclose($handle_out);
        //return $lineas_importadas;
        return $fichero_out;
    }
    
    protected function convertir_utf8($fichero_in) {
	if ($this->id_encoding =="UTF-8") 
	{
	  $fichero_out = $fichero_in;
	}
	else
	{
	  $fichero_out = $this->filename_tmp();
	  echo "\nConvirtiendo desde " . $this->id_encoding  . " a UTF-8 \n";
	  echo "Origen : " .  $fichero_in ."\n";
	  echo "Destino: " .  $fichero_out ."\n";
	  $salida=shell_exec("iconv -f " . $this->id_encoding . " -t UTF-8 '" . $fichero_in . "' -o '" . $fichero_out . "'");
	  echo "Conversión finalizada\n";
	 }
	return $fichero_out;
    }

    public function trata_fichero($fichero) {
	$this->nombre_fichero=$fichero;
	//$this->fichero_temporal = dirname($fichero) . "/delim_" . basename($fichero);
        //$this->convertir($fichero, $this->fichero_temporal);
	
	$fichero_salida=$this->convertir_utf8($fichero);
        
        $fichero_salida=$this->convertir_delim($fichero_salida);
        
        return $fichero_salida;
    }

    public function datos_desde_fila() {
        return 1;
    }

    function __destruct() {
    
    /*
        if ($this->fichero_temporal != "") 
        {
            unlink($this->fichero_temporal);
        }
        
        */
  for ($i=1;$i<=$this->ficheros_temporales;$i++)
        unlink($this->filename_tmp0($i));
        
    }

}

class Fichero_TXT_delim extends Fichero {

    function __construct($id_encoding, $cabeceras_en_fila, $datos_desde_fila, $separador, $lines_terminated_by) {
        parent::__construct($id_encoding, $cabeceras_en_fila, $datos_desde_fila);
        $this->separador = $separador;
        $this->lines_terminated_by=$lines_terminated_by;
    }

    public function convertir_delim($fichero_in) {
    return $fichero_in;
    }



    public function datos_desde_fila() {
        return $this->datos_desde_fila;
    }
    
    public function lines_terminated_by() {
        return $this->lines_terminated_by;
    }

    protected function lee_registro() {
        $linea = linea($this->handle);
        return explode($this->separador, $linea);
    }

}

class Fichero_TXT_fixed extends Fichero {

    private $anchos;

    function __construct($id_encoding, $cabeceras_en_fila, $datos_desde_fila, $anchos, $lines_terminated_by) {
        parent::__construct($id_encoding, $cabeceras_en_fila, $datos_desde_fila);
        $this->anchos = explode(",", $anchos);
		$this->lines_terminated_by=$lines_terminated_by;
    }

    public function lines_terminated_by() {
        return $this->lines_terminated_by;
    }
	
    protected function lee_registro() {
        $linea = linea($this->handle);
        $posicion = 0;
        $valores = array();
        foreach ($this->anchos as $ancho) {
            $valor = trim(substr($linea, $posicion, $ancho));
            array_push($valores, $valor);
            $posicion = $posicion + $ancho;
        }
        return $valores;
    }

    protected function cierra_fichero() {
        fclose($this->handle);
    }

    protected function final_fichero() {
        return feof($this->handle);
    }

}

class Fichero_XLS extends Fichero {

    private $XLS_tab;
    private $XLS_Datos_desde_columna;
    private $XLS_Columnas;
    private $highestRow;
    private $objPHPExcel;
    private $objReader;
    private $ws;
    private $linea;

    function __construct($id_encoding, $cabeceras_en_fila, $datos_desde_fila, $XLS_tab, $XLS_Datos_desde_columna, $XLS_Columnas) {
        parent::__construct($id_encoding, $cabeceras_en_fila, $datos_desde_fila);
        $this->XLS_tab = $XLS_tab;
        $this->XLS_Datos_desde_columna = $XLS_Datos_desde_columna;
        $this->XLS_Columnas = $XLS_Columnas;
    }

    protected function mover_a_cabecera() {
        $this->linea = $this->cabeceras_en_fila;
    }

    protected function mover_a_datos() {
        $this->linea = $this->datos_desde_fila;
    }

    protected function abre_fichero($fichero) {
        $inputFileType = PHPExcel_IOFactory::identify($fichero);
        $this->objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $this->objReader->setReadDataOnly(true);
        if ($this->XLS_tab != null && $this->XLS_tab != "")
        {
            $this->objReader->setLoadSheetsOnly($this->XLS_tab);
        }
        $this->objPHPExcel = $this->objReader->load($fichero);
        $this->ws = $this->objPHPExcel->getSheetByName($this->XLS_tab);
        $this->highestRow = $this->ws->getHighestRow();
    }

    protected function lee_registro() {
        $valores = array();
        for ($c = $this->XLS_Datos_desde_columna - 1; $c < $this->XLS_Datos_desde_columna + $this->XLS_Columnas - 1; $c++) {
            $value = $this->ws->getCellByColumnAndRow($c, $this->linea)->GetCalculatedValue();
            $value = trim($value);
            array_push($valores, $value);
        }
        $this->linea++;
        return $valores;
    }

    protected function cierra_fichero() {
        unset($this->objPHPExcel);
        unset($this->objReader);
    }

    protected function final_fichero() {
        return $this->linea > $this->highestRow;
    }

    public function datos_desde_fila() {
        return 1;
    }

}

class DetalleColumna {

    const TIPO_AUX = "blob";

    //const TIPO_AUX="varchar (100)";
    public $nombre_campo;
    private $tipo;
    private $es_clave;
    private $id_tratamiento;
    public $es_significativo = 1;
    public $es_importable = 1;
    public $pertenece_a_idx_A = 0;
    public $pertenece_a_idx_B = 0;

    function __construct($nombre_campo, $tipo, $id_tratamiento, $es_clave, $es_significativo = 1, $es_importable = 1, $pertenece_a_idx_A = 0, $pertenece_a_idx_B = 0) {
        $this->nombre_campo = $nombre_campo;
        $this->tipo = $tipo;
        $this->id_tratamiento = $id_tratamiento;
        $this->es_clave = $es_clave;
        $this->es_significativo = $es_significativo;
        $this->es_importable = $es_importable;
        $this->pertenece_a_idx_A = $pertenece_a_idx_A;
        $this->pertenece_a_idx_B = $pertenece_a_idx_B;
    }

    public function es_clave() {
        return $this->es_clave;echo("Archivando '" . $fichero . "' en '" . $fila["carpeta_salida"] . basename($fichero) . "'\n");
    }

    public function marca_clave() {
        $this->es_clave = 1;
    }

    public function desmarca_clave() {
        $this->es_clave = 0;
    }

    public function guardar_definicion_columna($mysqli, $id_modelo) {
        EjecutaQuery($mysqli, "INSERT INTO dex_modelo_detalle(id_modelo, nombre_campo, tipo, id_tratamiento,es_clave, es_significativo) VALUES('$id_modelo', '$this->nombre_campo', '$this->tipo', '$this->id_tratamiento','$this->es_clave','$this->es_significativo')");
    }

    public function definicion_create() {
        if ($this->es_significativo == "0")
            return "";
        return "`" . $this->nombre_campo . "` " . $this->tipo;
    }

    public function definicion_create_st() {
        //return "`" . $this->nombre_campo . "` blob";
        return "`" . $this->nombre_campo . "` " . self::TIPO_AUX;
    }

    public function definicion_tratamiento_previo() {
        return "`" . $this->nombre_campo . "`=trim(replace (`" . $this->nombre_campo . "`,char(13),''))";
    }

    public function definicion_tratamiento($tratamientos, $prefijo="") {
        if ($this->es_significativo == "0" || $this->es_importable == "0")
            return "";
        return str_replace("[campo]", $prefijo . "`" . $this->nombre_campo . "`", $tratamientos[$this->id_tratamiento]);
    }

    public function definicion_select() {
        if ($this->es_significativo == "0" || $this->es_importable == "0")
            return "";
        return "`" . $this->nombre_campo . "`";
    }

    public function definicion_clave() {
        if ($this->es_significativo == "0")
            return "";
        if ($this->es_clave == 1)
            return "`" . $this->nombre_campo . "`";
        return "";
    }

    public function definicion_idxA() {
        if ($this->es_significativo == "0")
            return "";
        if ($this->pertenece_a_idx_A == "1")
            return "`" . $this->nombre_campo . "` ASC";
        return "";
    }

    public function definicion_idxB() {
        if ($this->es_significativo == "0")
            return "";
        if ($this->pertenece_a_idx_B == "1")
            return "`" . $this->nombre_campo . "` ASC";
        return "";
    }

    public function analiza_tipo($mysqli, $tabla) {
        $largo = 255;
        $multiplo = 10;

        $tipo = "varchar ([largo])";
        $tratamiento = "";
        if ($respuesta = EjecutaQuery($mysqli, "SELECT * FROM dex_patron ORDER BY orden")) {
            while ($f = $respuesta->fetch_assoc()) {
                $fila = EjecutaQueryFila($mysqli, "SELECT count(*) regs,sum(`" . $this->nombre_campo . "` regexp '" . $f["patron"] . "') `coincidencias`, max(length(`" . $this->nombre_campo . "`))  largo FROM `$tabla`");
                //echo "SELECT count(*) regs,sum(`" . $this->nombre_campo . "` regexp '" . $f["patron"]. "') `coincidencias`, max(length(`" . $this->nombre_campo . "`))  largo FROM $tabla :" . $fila["regs"] . "/" . $fila["coincidencias"]  ."\n";
                if ($fila["regs"] == $fila["coincidencias"] && ( $fila["largo"] >= $f["entre_largo"] && $fila["largo"] <= $f["hasta_largo"] )) {
                    $tipo = $f["tipo"];
                    $tratamiento = $f["id_tratamiento"];
                    $largo = ceil($fila["largo"] / 10) * 10 + 10;
                    $this->tipo = str_replace("[largo]", $largo, $tipo);
                    $this->id_tratamiento = $tratamiento;
                    break;
                }
            }
        }
        echo "( " . $this->tipo . " , " . $this->id_tratamiento . " )\n";
        return str_replace("[largo]", $largo, $tipo) . "," . $tratamiento;
    }

}

class DetalleColumnas {

    private $columnas;
    private $tratamientos;
    private $id_modelo;

    function __construct($mysqli, $id_modelo) {
        $this->columnas = array();
        $this->id_modelo = $id_modelo;
        $result = EjecutaQuery($mysqli, "SELECT a.*,b.tratamiento FROM dex_modelo_detalle a,dex_tratamiento b WHERE a.id_tratamiento=b.id_tratamiento and a.ID_modelo='$id_modelo' ORDER by a.orden,a.id_columna");
        while ($row = $result->fetch_assoc()) {
            $col = new DetalleColumna($row["nombre_campo"], $row["tipo"], $row["id_tratamiento"], $row["es_clave"], $row["es_significativo"], $row["es_importable"], $row["pertenece_a_idx_A"], $row["pertenece_a_idx_B"]);
            array_push($this->columnas, $col);
        }
        $result->close();
        $this->tratamientos = array();
        $result = EjecutaQuery($mysqli, "SELECT * FROM dex_tratamiento");
        while ($row = $result->fetch_assoc()) {
            $this->tratamientos[$row["id_tratamiento"]] = $row["tratamiento"];
        }
        $result->close();
    }

    public function corrige_especificacion($mysqli, $tabla) {
        foreach ($this->columnas as $columna) {
            echo "Analizando " . $columna->definicion_select() . ": ";
            $columna->analiza_tipo($mysqli, $tabla);
        }
    }

    private function registros_agrupados_por_clave($mysqli, $tabla, $campo) {
        if ($campo == "")
            $sql = "SELECT count(*) total_regs from `$tabla` ;";
        else {
            $campos_clave = $this->lista_campos_clave();
            if ($campos_clave == "")
                $sql = "SELECT count(*) total_regs from (SELECT `$campo`, count(*) FROM `$tabla` GROUP BY `$campo`) a;";
            else
                $sql = "SELECT count(*) total_regs from (SELECT $campos_clave ,`$campo`, count(*) FROM `$tabla` GROUP BY $campos_clave ,`$campo`) a;";
        }
        $f = EjecutaQueryFila($mysqli, $sql);

        return $f["total_regs"];
    }

    private function desmarca_claves() {
        for ($j = 0; $j < count($this->columnas); $j++)
            $this->columnas[$j]->desmarca_clave();
    }

    public function calcula_claves($mysqli, $tabla) {
        /* Únicamente se puede ejecutar para tablas para las que ya han sido calculado el tipo óptimo, en caso contrario el rendimiento puede ser muy bajo) */
        echo "\n\nCalculando claves para la tabla $tabla\n\n";
        $total_registros = $this->registros_agrupados_por_clave($mysqli, $tabla, "");
        echo "Total registros tabla $tabla: " . $total_registros . "\n";
        $max_registros = 0;
        for ($i = 0; $i < count($this->columnas) && $max_registros < $total_registros; $i++) {
            echo "\nPasada " . $i . "\n";
            $campo_candidato = -1;
            for ($j = 0; $j < count($this->columnas); $j++) {
                if ($this->columnas[$j]->es_clave() == 0) {
                    $regs = $this->registros_agrupados_por_clave($mysqli, $tabla, $this->columnas[$j]->nombre_campo);
                    echo $this->columnas[$j]->nombre_campo . ": " . $regs . " / " . $max_registros . " / " . $total_registros . "\n";

                    if ($i == 0 && $regs == 1) {
                        $this->columnas[$j]->es_significativo = 0;
                        echo "Campo irrelevante identificado: " . $this->columnas[$j]->nombre_campo . "\n";
                    }
                    if ($regs > $max_registros) {
                        $max_registros = $regs;
                        $campo_candidato = $j;
                        if ($max_registros == $total_registros)
                            break;
                    }
                }
            }
            if ($campo_candidato != -1) {
                $this->columnas[$campo_candidato]->marca_clave();
                echo "Clave identificada: " . $this->lista_campos_clave() . "\n";
                if ($max_registros == $total_registros)
                    echo "La clave ya está completa: " . $this->lista_campos_clave() . "\n";
            }
            else {
                echo "No se han identificado más campos clave\n";
                break;
            }
        }
        if ($max_registros < $total_registros) {
            echo "Advertencia, la clave es incompleta: " . $this->lista_campos_clave() . "\n";
            echo "Se desmarcan las claves encontradas por no ser válidas\n";
            $this->desmarca_claves();
        }
        return 0;
    }

    public function guarda_detalle($mysqli) {
        EjecutaQuery($mysqli, "DELETE FROM dex_modelo_detalle where ID_modelo='$this->id_modelo'");
        foreach ($this->columnas as $columna)
            $columna->guardar_definicion_columna($mysqli, $this->id_modelo);
    }

    function carga_basica($array_campos) {
        foreach ($array_campos as $campo)
            array_push($this->columnas, new DetalleColumna($campo, DetalleColumna::TIPO_AUX, "1", "0", "1"));
    }

    public function lista_campos_create() {
        $aux = array();
        $aux_claves = array();
        $aux_idx_A = array();
        $aux_idx_B = array();
        foreach ($this->columnas as $col) {
            if ($col->definicion_create() != "")
                array_push($aux, $col->definicion_create());
            if ($col->definicion_clave() != "")
                array_push($aux_claves, $col->definicion_clave());
            if ($col->definicion_idxA() != "")
                array_push($aux_idx_A, $col->definicion_idxA());
            if ($col->definicion_idxB() != "")
                array_push($aux_idx_B, $col->definicion_idxB());
        }
        if (count($aux_claves) > 0)
            array_push($aux, "PRIMARY KEY(" . implode(",", $aux_claves) . ")");

        if (count($aux_idx_A) > 0)
            array_push($aux, "INDEX idxA(" . implode(",", $aux_idx_A) . ")");

        if (count($aux_idx_B) > 0)
            array_push($aux, "INDEX idxB(" . implode(",", $aux_idx_B) . ")");

        return implode(",", $aux);
    }

    public function lista_campos_create_st() {
        $aux = array();
        foreach ($this->columnas as $col) {
            array_push($aux, $col->definicion_create_st()); //en este caso siempre se emplearán todos los campos
        }
        return implode(",", $aux);
    }

    public function lista_campos_select($todos = 0) {
        $aux = array();
        foreach ($this->columnas as $col) {
            if ($col->definicion_select($todos) != "")
                array_push($aux, $col->definicion_select($todos));
        }
        return implode(",", $aux);
    }

    public function lista_campos_clave() {
        $aux = array();
        foreach ($this->columnas as $col) {
            if ($col->definicion_clave() != "")
                array_push($aux, $col->definicion_clave());
        }
        return implode(",", $aux);
    }

    public function lista_campos_no_clave() {
        $aux = array();
        foreach ($this->columnas as $col) {
            if ($col->definicion_clave() == "")
                array_push($aux, $col->definicion_select());
        }
        return implode(",", $aux);
    }
    
    public function lista_campos_idxA() {
        $aux = array();
        foreach ($this->columnas as $col) {
            if ($col->definicion_idxA() != "")
                array_push($aux, $col->definicion_clave());
        }
        return implode(",", $aux);
    }

    public function lista_campos_idxB() {
        $aux = array();
        foreach ($this->columnas as $col) {
            if ($col->definicion_idxB() != "")
                array_push($aux, $col->definicion_clave());
        }
        return implode(",", $aux);
    }

    public function lista_tratamiento_previo() {
        $aux = array();
        foreach ($this->columnas as $col) {
            array_push($aux, $col->definicion_tratamiento_previo());
        }
        return implode(",", $aux);
    }

    public function lista_campos_tratamiento($separador_lista=",",$prefijo="") {
        $aux = array();
        foreach ($this->columnas as $col) {
            if ($col->es_significativo == "1" && $col->es_importable == "1")
                array_push($aux, $col->definicion_tratamiento($this->tratamientos,$prefijo));
        }
        return implode($separador_lista, $aux);
    }
}

class Modelo {
    public $columnas;
    public $id_modelo;

    function __construct($mysqli, $id_modelo) {
        $parametros_modelo = EjecutaQueryFila($mysqli, "SELECT * FROM dex_modelofichero where id_modelo='$id_modelo' ");

        $this->id_modelo = $id_modelo;

        $this->columnas = new DetalleColumnas($mysqli, $id_modelo);

        switch ($parametros_modelo["MetodoAcceso"]) {
 
            case "TXT_DELIM":
                $this->acceso_fichero = new Fichero_TXT_delim($parametros_modelo["id_encoding"], $parametros_modelo["Cabeceras_en_fila"], $parametros_modelo["Datos_desde_fila"], $parametros_modelo["TXT_Separador"], $parametros_modelo["TXT_lines_terminated_by"]);
                break;
            case "TXT_FIXED":
                $this->acceso_fichero = new Fichero_TXT_fixed($parametros_modelo["id_encoding"], $parametros_modelo["Cabeceras_en_fila"], $parametros_modelo["Datos_desde_fila"], $parametros_modelo["TXT_Anchos"], $parametros_modelo["TXT_lines_terminated_by"]);
                break;
            case "XLS":
                $this->acceso_fichero = new Fichero_XLS($parametros_modelo["id_encoding"], $parametros_modelo["Cabeceras_en_fila"], $parametros_modelo["Datos_desde_fila"], $parametros_modelo["XLS_tab"], $parametros_modelo["XLS_Datos_desde_columna"], $parametros_modelo["XLS_Columnas"]);
                break;
        }
    }

    private function CreaTabla($mysqli, $tabla, $tipo = "CT") {
        if ($tipo == "ST")
            $lista = $this->columnas->lista_campos_create_st();
        else
            $lista = $this->columnas->lista_campos_create();
        if (!EjecutaQuery($mysqli, "DROP TABLE IF EXISTS `$tabla`; "))
            return -1;
        if (!EjecutaQuery($mysqli, "CREATE TABLE `$tabla` ($lista) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_spanish_ci; "))
            return -1;
        return 1;
    }

    private function TratarTabla($mysqli, $tabla, $tratamiento_tabla) {
        switch ($tratamiento_tabla) {
            case "C" : /* Crea Tabla */
                $this->CreaTabla($mysqli, $tabla);
                break;
            case "D" : // Crea Tabla con estructura por defecto
                $this->CreaTabla($mysqli, $tabla, "ST");
                break;
            case "T" : /* TRUNCATE Table */
                EjecutaQuery($mysqli, "TRUNCATE TABLE `$tabla`");
                break;
        }
    }

    private function tratamiento_previo_columnas($mysqli, $tabla) {
    return 1;
        $sql = "UPDATE `$tabla` SET " . $this->columnas->lista_tratamiento_previo();
        if (!EjecutaQuery($mysqli, $sql))
            return -1;
    }

    private function Importa0($mysqli, $fichero, $tabla) {

		$sql = "LOAD DATA INFILE '" . $this->acceso_fichero->trata_fichero($fichero) . "' INTO TABLE `$tabla` FIELDS TERMINATED BY '" . $this->acceso_fichero->separador . "' LINES TERMINATED BY '" . $this->acceso_fichero->lines_terminated_by() . "' IGNORE " . ($this->acceso_fichero->datos_desde_fila() - 1) . " LINES";
        echo($sql ."\n");
        if (!EjecutaQuery($mysqli, $sql))
            return -1;
        return $mysqli->affected_rows;
    }

/*
       private function copia_tabla($mysqli, $tabla_origen, $tabla_destino) {
       $r=EjecutaQuery($mysqli, "INSERT INTO `" . $tabla_destino . "`(" . $this->columnas->lista_campos_select() . ") SELECT " . $this->columnas->lista_campos_tratamiento() . " FROM `" . $tabla_origen . "`");
*/
    private function copia_tabla($mysqli, $tabla_origen, $tabla_destino, $insert_ignore=0) {
        echo "INSERT IGNORE: $insert_ignore\n";
        $sql=(($insert_ignore==0)?"INSERT":"INSERT IGNORE") . " INTO `" . $tabla_destino . "`(" . $this->columnas->lista_campos_select() . ") SELECT " . $this->columnas->lista_campos_tratamiento() . " FROM `" . $tabla_origen . "`";
        echo "$sql\n";
        $r=EjecutaQuery($mysqli, $sql);
//<-
        if (!$r) return -1;
        return $mysqli->affected_rows;
    }
    
    private function update_tabla($mysqli, $tabla_origen, $tabla_destino) {
        $array_campos_clave=explode(",",$this->columnas->lista_campos_clave());
        $array_campos=explode(",",$this->columnas->lista_campos_select());
		$array_campos_tratamiento=explode("|",$this->columnas->lista_campos_tratamiento("|","b."));
        $asignacion_datos=array();
        $comparacion_claves=array();
        
		
		foreach ($array_campos_clave as $campo) {array_push($comparacion_claves, "a." . $campo . "=b." . $campo );}
		
		for ($j = 0; $j < count($array_campos); $j++)
			array_push($asignacion_datos, "a." . $array_campos[$j] . "=" . $array_campos_tratamiento[$j] );
//foreach ($array_campos_datos as $campo) {array_push($asignacion_datos, "a." . $campo . "=b." . $campo );}
        
               
        $sql="UPDATE `" . $tabla_destino . "` a, `" . $tabla_origen . "` b SET " . implode(" , ",$asignacion_datos) . " WHERE " . implode(" AND ",$comparacion_claves);
        echo "\n$sql\n";
        $r=EjecutaQuery($mysqli, $sql);
        if (!$r) return -1;
        return $mysqli->affected_rows;
    }
    
    

    private function mueve_tabla($mysqli, $tabla_origen, $tabla_destino, $insert_ignore=0) {
        $r = $this->copia_tabla($mysqli, $tabla_origen, $tabla_destino, $insert_ignore);
        if ($r<>-1) EjecutaQuery($mysqli, "DROP TABLE `" . $tabla_origen . "`");
        return $r;
    }

    public function Importa($mysqli, $fichero, $tabla, $tratamiento_tabla = "") {
        echo "\n\nTabla $tabla\n";
        $sql = "INSERT INTO dex_log_Ficheros(NombreFichero,Registrado,bytes,md5,id_modelo,last_char) values ('" . mysqli_real_escape_string($mysqli, $fichero) . "',now()," . filesize($fichero) . ",'" . md5_file($fichero) . "','" . $this->id_modelo . "','". Last_Char($fichero) . "')";

        $mysqli->query($sql);
        $id_log_fichero = $mysqli->insert_id;

        if ($this->columnas->lista_campos_create() == "") {
            /* Crea estructura básica */
            echo "\nCreando estructura básica ...\n";
            echo implode(",", $this->acceso_fichero->retorna_campos($fichero)) . "\n";

            $this->columnas->carga_basica($this->acceso_fichero->retorna_campos($fichero));

            /* Importa datos sin tipos y sin tratamiento de columnas */
            echo "\nImportando datos sin tipos y sin tratamiento de columnas ...\n";

            $this->TratarTabla($mysqli, "tmp_" . $tabla, "D");
            $total_registros = $this->Importa0($mysqli, $fichero, "tmp_" . $tabla);
            $this->tratamiento_previo_columnas($mysqli, "tmp_" . $tabla);

            /* Calcula tipos */
            echo "\nCalculando tipos ...\n";
            $this->columnas->corrige_especificacion($mysqli, "tmp_" . $tabla);

            /* Identifica claves y campos descartables en tabla con tipos */
            echo "\nIdentificando campos claves y campos irrelevantes...\n";

            $this->TratarTabla($mysqli, "tmp2_" . $tabla, "C");
            $this->copia_tabla($mysqli, "tmp_" . $tabla, "tmp2_" . $tabla);
            $this->columnas->calcula_claves($mysqli, "tmp2_" . $tabla);
            $this->columnas->guarda_detalle($mysqli);
            EjecutaQuery($mysqli, "DROP TABLE `" . "tmp2_" . $tabla . "`");
        } else {
            /* Importa datos sin tipos y sin tratamiento de columnas */
            echo "\nImportando datos sin tipos y sin tratamiento de columnas ...\n";

            $this->TratarTabla($mysqli, "tmp_" . $tabla, "D");
            $total_registros = $this->Importa0($mysqli, $fichero, "tmp_" . $tabla);
            $this->tratamiento_previo_columnas($mysqli, "tmp_" . $tabla);
        }
        /* Traslada tabla aplicando tratamiento y en los tipos destino correctos */
        echo "\nTrasladando datos a tabla definitiva ...\n";
        echo "TratarTabla(mysqli, $tabla, $tratamiento_tabla);";
		
        switch($tratamiento_tabla)
        {
            case "COPY":
                    $this->TratarTabla($mysqli, $tabla, "C");
                    $registros_importados = $this->mueve_tabla($mysqli, "tmp_" . $tabla, $tabla, 0);
                break;
            case "INSERT":
                    $registros_importados = $this->mueve_tabla($mysqli, "tmp_" . $tabla, $tabla, 1);
                break;
            case "UPDATE":
                    $registros_importados = $this->update_tabla($mysqli, "tmp_" . $tabla, $tabla);
                break;
        }
     
        // Si el tratamiento de tabla está en blanco, entonces utilizar INSERT IGNORE para simplemente añadir registros no repetidos,m según su clave principal
        
        $sql = "UPDATE dex_log_Ficheros SET Procesado=Now(), Registros=$total_registros,Registros_Actualizados=$registros_importados WHERE Id_log_Fichero=$id_log_fichero";
        EjecutaQuery($mysqli, $sql, 1);

        return $registros_importados;
    }
}

function Importa($mysqli, $fichero, $modelo, $tabla, $tratamiento_tabla = "") {

    $m = new Modelo($mysqli, $modelo);
    $r = $m->Importa($mysqli, $fichero, $tabla, $tratamiento_tabla);

    return $r;
}
?>