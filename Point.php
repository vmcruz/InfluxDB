<?php
	class Point {
		private $timestamp;
		private $value;
		private $tags;
		
		public function __construct($tags = array(), $value = 0, $timestamp = false) {
			if($timestamp)
				$this->timestamp = $timestamp;
			else
				$this->timestamp = time();
			
			$this->value = $value;
			$this->tags = $tags;
		}
		
		public function setTimestamp($timestamp = false) {
			if($timestamp)
				$this->timestamp = $timestamp;
		}
		
		public function setValue($value = false) {
			if($value)
				$this->value = $value;
		}
		
		public function addTag($tagName = false, $tagValue = false) {
			$this->tags += array($tagName => $tagValue);
		}
		
		public function setTags($tags = array()) {
			if(count($tags) > 0)
				$this->tags = $tags;
		}
		
		public function getTimestamp() {
			return $this->timestamp;
		}
		
		public function getValue() {
			return $this->value;
		}
		
		public function getTags() {
			return $this->tags;
		}
	}
?>