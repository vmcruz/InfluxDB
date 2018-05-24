<?php
	require_once __DIR__ . "Measurement.php";

	class InfluxDB {
		
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
		
		
		public function setPrecision($precision = false) {
			if($precision) {
				$this->precision = $precision;
			}
		}
		
		
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
		
		
		public function getErrCode() {
			return $this->errno;
		}
		
		
		public function getErrMessage() {
			return $this->errstr;
		}
		
		
		public function getErrType() {
			return $this->errtype;
		}
		
		
		public function lastError() {
			return "[" . self::getErrType() . "] Error #" . self::getErrCode() . ": " . self::getErrMessage();
		}
	}
?>