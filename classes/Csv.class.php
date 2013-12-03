<?php

class Csv {

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
		fwrite($this->output, "\xEF\xBB\xBF");
	}
	
	function header($data) {
		$this->add($data);
	}
	
	function add($data) {
		$buffer = fopen('php://temp', 'r+');
		fputcsv($buffer, $data, ';', '"');
		rewind($buffer);
		$csv = fgets($buffer);
		fclose($buffer);
		$csv = substr($csv, 0, -1) . "\r\n";
		fwrite($this->output, $csv);
	}

	function end() {
		fclose($this->output);
		exit();
	}

}
