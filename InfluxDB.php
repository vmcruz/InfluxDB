<?php
	require_once __DIR__ . "Measurement.php";

	class InfluxDB {
		/**
			Permite crear una nueva instancia de InfluxDB
			@class InfluxDB
			
			@prop {private String} host - Servidor al que InfluxDB debe conectarse (dirección ip o nombre de dominio)
			@prop {private Integer} port - Puerto al que se ha de conectar
			@prop {private String} user - Usuario que se usa para la conexión
			@prop {private String} password - Contraseña de acceso
			@prop {private String} db - Base de datos a la que InfluxDB se conecta
			@prop {private Integer} errno - Indica el código de error generado, en caso de que lo hubiera
			@prop {private String} errstr - Indica el mensaje de error generado, en caso de que lo hubiera
			@prop {private String} errtype - Indica el tipo de error generado (librería, curl o servidor), en caso de que lo hubiera
			@prop {private String} precision - Almacena la precisión con la que InfluxDB representará el tiempo (timestamp)			
			@param {String} db - Base de datos a la que se desea conectar de InfluxDB
			@param {String} user - Usuario de la base de datos
			@param {String} password - Contraseña del usuario de la base de datos
			@param {String} host - Servidor donde se encuentra InfluxDB. Opcional, por defecto "localhost"
			@param {String} port - Puerto de la API HTTP de InfluxDB. Opcional, por defecto "8086"
			@example Ej. Nueva instancia de InfluxDB ~ $influx = <b>new InfluxDB("umidb", "umidev", "desarrollo123", "192.168.1.99")</b>;
			@version 0.3.0b
			@created 22/04/2016
			@updated 27/04/2016
			@sourcecode {sourcecode:31,54}
			
		*/
		private $host;
		private $port;
		private $user;
		private $password;
		private $db;
		private $errno;
		private $errstr;
		private $errtype;
		private $precision;
		
		public function __construct($db = false, $user = "", $password = "", $host = "localhost", $port = "8086")  {
			if($db) {
				$this->host = $host;
				$this->port = $port;
				$this->user = $user;
				$this->password = $password;
				$this->db = $db;
				$this->precision = false;
			} else {
				$this->errno = 0;
				$this->errstr = "No database selected";
				$this->errtype = "lib";
			}
		}
		
		/**
			Especifica la precisión en la que InfluxDB devolverá el timestamp en los queries. Por defecto, InfluxDB devolverá el timestamp en nanosegundos.
			Para especificar una precisión diferente consultar la documentación de InfluxDB: <a href="https://docs.influxdata.com/influxdb/v0.12/guides/querying_data/#other-options-when-querying-data" class="fancy-goto" target="_blank">InfluxDB - Other options when querying data</a>
			@method InfluxDB.setPrecision
			@exposure public
			@param {String} precision - La precisión que se desea utilizar en los queries
			@example Ej. Especificar precisión en segundos ~ $influx = new InfluxDB("estaciones", "umidev", "desarrollo123", "localhost");
			<b>$influx->setPrecision("s")</b>;
			@sourcecode {sourcecode:66,70}
		*/
		public function setPrecision($precision = false) {
			if($precision) {
				$this->precision = $precision;
			}
		}
		
		/**
			Ejecuta un query en InfluxDB y devuelve el resultado del query
			@method InfluxDB.query
			@exposure public
			@param {String} query - El query que se desea ejecutar
			@param {Boolean} pretty - Especifica si el resultado se devuelve en un json para debuggeo o no. Opcional, por defecto "false"
			@example Ej. Seleccionar todos los datos de la variable "cpu" ~ $result = <b>$influx->query("select * from cpu")</b>;
			@returns {Boolean} <i>False</i> Cuando un error ocurre
			@returns {Array[]} Arreglo asociativo de los valores devueltos por InfluxDB
			@sourcecode {sourcecode:83,125}
		*/
		public function query($query = false, $pretty = false) {
			if($query) {
				$curl_handler = curl_init();
				$fullUrl = array (
								"pretty" => $pretty,
								"db" => $this->db,
								"u" => $this->user,
								"p" => $this->password,
								"q" => $query,
								"epoch" => $this->precision
							);
				curl_setopt($curl_handler, CURLOPT_URL, "http://{$this->host}:{$this->port}/query?" . http_build_query($fullUrl));
				curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, TRUE);
				$response = curl_exec($curl_handler);
				$errno = curl_errno($curl_handler);
				
				if(!$errno) {
					$httpCode = curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);
					$response = json_decode($response, true);
					
					if($httpCode == "200") {
						curl_close($curl_handler);
						return $response;
					}
					else {
						$this->errno = $httpCode;
						$this->errstr = $response["error"];
						$this->errtype = "influxdb";
						return false;
					}
				} else {
					$this->errno = $errno;
					$this->errstr = "curl error";
					$this->errtype = "curl";
					return false;
				}
			} else {
				$this->errno = 1;
				$this->errstr = "Undefined query";
				$this->errtype = "lib";
				return false;
			}
		}
		
		/**
			Parsea el resultado de un {goto:InfluxDB.query} y devuelve los puntos de las tablas seleccionadas
			@method InfluxDB.getPoints
			@exposure public
			@param {InfluxDBQuery} result - El resultado de un query ejecutado
			@example Ej. Seleccionar todos los datos de la variable "cpu" ~ $result = $influx->query("select * from cpu, cpu2");
			if($result) {
				$points = <b>$influx->getPoints($result)</b>;
				$cpuPoints = $points["cpu"]; //array ([0] => array("time" => "...", "value" => "..."), [1] => array(...), ...);
			} else {
				
				echo "Error " . $influx->getErrCode() . ": " . $influx->getErrMessage();
			}
			@returns {Boolean} <i>False</i> Cuando un error ocurre
			@returns {Array[]} Arreglo asociativo con los datos de los puntos de todas las medidas seleccionadas
			@sourcecode {sourcecode:144,180}
		*/
		public function getPoints($result = false) {
			if($result) {
				$series = isset($result["results"][0]["series"]) ? $result["results"][0]["series"] : false;
				if($series) {
					$points = array();
					foreach($series as $serie) {
						$measurement = $serie["name"];
						$columns = $serie["columns"];
						if(!isset($points[$measurement]))
							$points[$measurement] = array();
						
						for($i = 0; $i < count($serie["values"]); $i++) {
							$dataPoint = array();
							for($j = 0; $j < count($serie["values"][$i]); $j++)
								$dataPoint[$columns[$j]] = $serie["values"][$i][$j];
							
							if(isset($serie["tags"]))
								$dataPoint = array_merge($dataPoint, $serie["tags"]);
							
							$points[$measurement][] = $dataPoint;
						}
					}
					
					return $points;
				} else {
					$this->errno = 4;
					$this->errstr = "No series found";
					$this->errtype = "lib";
					return false;
				}
			} else {
				$this->errno = 3;
				$this->errstr = "Undefined result set";
				$this->errtype = "lib";
				return false;
			}
		}
		
		/**
			Escribe en la base de datos la información de una medida especificada
			@method InfluxDB.write
			@exposure public
			@returns {Boolean} "False" Cuando un error ocurre
			@returns {Boolean} "True" Si se realizó la inserción correctamente
			@sourcecode {sourcecode:190,239}
		*/
		public function write($measurement) {
			if($measurement instanceof Measurement) {
				$points = $measurement->getPoints();
				$insertData = "";
				for($i = 0; $i < count($points); $i++) {
					$data = $measurement->getName() . "," . http_build_query($points[$i]->getTags(), "", ",") . " value=" . $points[$i]->getValue() . " " . $points[$i]->getTimestamp();
					$insertData .= $data . "\n";
				}
				
				$curl_handler = curl_init();
				$fullUrl = array (
								"db" => $this->db,
								"u" => $this->user,
								"p" => $this->password
							);
				curl_setopt($curl_handler, CURLOPT_URL, "http://{$this->host}:{$this->port}/write?" . http_build_query($fullUrl));
				curl_setopt($curl_handler, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($curl_handler, CURLOPT_BINARYTRANSFER, TRUE);
				curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $insertData);
				$response = curl_exec($curl_handler);
				$errno = curl_errno($curl_handler);
				
				if(!$errno) {
					$httpCode = curl_getinfo($curl_handler, CURLINFO_HTTP_CODE);
					$response = json_decode($response, true);
					
					if($httpCode == "204") {
						curl_close($curl_handler);
						return true;
					} else {
						$this->errno = $httpCode;
						$this->errstr = $response["error"];
						$this->errtype = "influxdb";
						return false;
					}
				} else {
					$this->errno = $errno;
					$this->errstr = "curl error";
					$this->errtype = "curl";
					return false;
				}
			} else {
				$this->errno = 2;
				$this->errstr = "Undefined measurement or data incomplete";
				$this->errtype = "lib";
				return false;
			}
		}
		
		/**
			Devuelve el código del último error
			@method InfluxDB.getErrCode
			@exposure public
			@returns {Integer} El código del error
			@sourcecode {sourcecode:248,250}
		*/
		public function getErrCode() {
			return $this->errno;
		}
		
		/**
			Devuelve el mensaje del último error
			@method InfluxDB.getErrMessage
			@exposure public
			@returns {String} El mensaje del error
			@sourcecode {sourcecode:259,261}
		*/
		public function getErrMessage() {
			return $this->errstr;
		}
		
		/**
			Devuelve el tipo del último error
			@method InfluxDB.getErrType
			@exposure public
			@returns {String} El tipo del error. Los tipos devueltos son: "lib", propio de la librería; "curl", error de ejecución de curl; "influxdb", error de InfluxDB
			@sourcecode {sourcecode:270,272}
		*/
		public function getErrType() {
			return $this->errtype;
		}
		
		/**
			Devuelve la cadena en <span class='param-type param-type-string'>String</span> del último error generado
			@method InfluxDB.lastError
			@exposure public
			@returns {String} Mensaje de error generado
			@sourcecode {sourcecode:281,283}
		*/
		public function lastError() {
			return "[" . self::getErrType() . "] Error #" . self::getErrCode() . ": " . self::getErrMessage();
		}
	}
?>
