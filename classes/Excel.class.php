<?php

class Excel {

	function __construct($filename = Null) {
		if ($filename !== Null) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename='.$filename);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		}
		
		while (ob_get_length())
			ob_end_clean();
		
		$this->output = fopen('php://output', 'w');
		fwrite($this->output, pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0));
		$this->rowNo = 0;
	}
	
	function header($data) {
		$this->add($data);
	}
	
	function add($data) {
		$colNo = 0;
		foreach($data as $field) {
			if(is_numeric($field)) {
				fwrite($this->output, $this->numFormat($this->rowNo, $colNo, $field));
			} else {
				fwrite($this->output, $this->textFormat($this->rowNo, $colNo, $field));
			}
			$colNo++;
		}
		$this->rowNo++;
	}

	private function textFormat($row, $col, $data) {
		$data = utf8_decode($data);
		$length = strlen($data);
		$field = pack("ssssss", 0x204, 8 + $length, $row, $col, 0x0, $length);
		$field .= $data;
		return $field . $data; 
	}

	private function numFormat($row, $col, $data) {
		$field = pack("sssss", 0x203, 14, $row, $col, 0x0);
		return $field . pack("d", $data);
	}

	function end() {
		fwrite($this->output, pack("ss", 0x0A, 0x00));
		fclose($this->output);
		exit();
	}

}
