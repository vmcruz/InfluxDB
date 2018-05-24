<?php
	require_once __DIR__ . "/Point.php";

	class Measurement {
		private $points;
		private $name;

		public function __construct($name = false) {
			if($name) {
				$this->points = array();
				$this->name = $name;
			}
		}

		public function addPoint($point) {
			if($point instanceof Point)
				$this->points[] = $point;
		}

		public function getPoints() {
			return $this->points;
		}

		public function getName() {
			return $this->name;
		}
	}
?>
