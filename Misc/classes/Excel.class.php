<?php

class Excel {
	private $xf;


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
		fwrite($this->output, pack('v4', 0x809, 0x0004, 0x0600, 0x10));
		$this->rowNo = 0;
		$this->xf = 0;
	}


	function header($data) {
		$this->add($data);
	}

/*
	public function newStyle() {
		fwrite($this->output, pack("vvvvvCCCCVVv", 0xE0, 0x14, $font, $format, 0));
		return 0;
	}
*/

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
		$data = mb_convert_encoding($data, "UTF-16LE", "UTF-8");
    $len = mb_strlen($data, "UTF-16LE");
    return  pack('s6C', 0x204, 9+2*$len, $row, $col, $this->xf, $len, 0x1).$data;
	}


	private function numFormat($row, $col, $data) {
		return pack('s5d', 0x203, 14, $row, $col, $this->xf, $data);
	}


	function end() {
		fwrite($this->output, pack('ss', 0x0A, 0x00));
		fclose($this->output);
		exit();
	}

}
