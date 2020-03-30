<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2014 David PHAM-VAN
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

// $ composer require nimmneun/onesheet
use OneSheet\CellBuilder;
use OneSheet\Sheet;
use OneSheet\SheetFile;
use OneSheet\Size\SizeCalculator;
use OneSheet\Style\Style;
use OneSheet\Style\Styler;
use OneSheet\Xml\DefaultXml;
use OneSheet\Xml\SheetXml;
use RuntimeException;
use ZipArchive;


class ExcelExport extends BaseExport {
	private $sheetFile;
	private $styler;
	private $sheet;
	private $dataStyle;
	private $headStyle;
	private $zip;


	/**
	 * ExcelExport constructor.
	 * @throws \Exception
	 */
	protected function start() {
		$this->sheetFile = new SheetFile();
		$this->sheetFile->fwrite(str_repeat(' ', 1024 * 1024) . '<sheetData>');
		$this->styler = new Styler();
		$this->sheet = new Sheet(new CellBuilder(), new SizeCalculator(null));
		$this->sheet->enableCellAutosizing();
		$this->sheet->setFreezePaneCellId('A2');
		$this->dataStyle = $this->styler->getDefaultStyle();
		$this->styler->addStyle($this->dataStyle);
		$this->headStyle = new Style();
		$this->headStyle->setFontSize(13)->setFontBold()->setFontColor('FFFFFF')->setFillColor('777777');
		$this->styler->addStyle($this->headStyle);
	}


	/**
	 * @param array $data
	 */
	public function header(array $data) {
		if (!empty($data)) {
			$this->sheetFile->fwrite($this->sheet->addRow($data, $this->headStyle));
		}
	}


	/**
	 * @param array $data
	 */
	public function add(array $data) {
		if (!empty($data)) {
			$this->sheetFile->fwrite($this->sheet->addRow($data, $this->dataStyle));
		}
	}


	/**
	 * @return string
	 */
	public function mimeType() {
		return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
	}


	/**
	 * @return string
	 */
	public function fileExtension() {
		return ".xlsx";
	}


	/**
	 *
	 */
	public function end() {
		$this->zip = new ZipArchive();
		$zipFileUrl = sys_get_temp_dir() . '/' . uniqid();
		$this->zip->open($zipFileUrl, ZipArchive::CREATE);
		$this->finalizeSheet();
		$this->finalizeStyles();
		$this->finalizeDefaultXmls();
		if (!$this->zip->close()) {
			throw new RuntimeException('Failed to close zip file!');
		}
		$zipFilePointer = fopen($zipFileUrl, 'r');
		if (!stream_copy_to_stream($zipFilePointer, $this->stream)
			|| !fclose($zipFilePointer)
			|| !unlink($zipFileUrl)
		) {
			throw new RuntimeException("Failed to copy stream and clean up!");
		}
		return parent::end();
	}


	/**
	 * Wrap up the sheet (write header, column xmls).
	 */
	private function finalizeSheet() {
		$this->sheetFile->fwrite('</sheetData></worksheet>');
		$this->sheetFile->rewind();
		$this->sheetFile->fwrite(SheetXml::HEADER_XML);
		$this->sheetFile->fwrite($this->sheet->getDimensionXml());
		$this->sheetFile->fwrite($this->sheet->getSheetViewsXml());
		$this->sheetFile->fwrite($this->sheet->getColsXml());
		$this->zip->addFile($this->sheetFile->getFilePath(), 'xl/worksheets/sheet1.xml');
	}


	/**
	 * Write style xml file.
	 */
	private function finalizeStyles() {
		$this->zip->addFromString('xl/styles.xml', $this->styler->getStyleSheetXml());
	}


	/**
	 * Add default xmls to zip archive.
	 */
	private function finalizeDefaultXmls() {
		$this->zip->addFromString('[Content_Types].xml', DefaultXml::CONTENT_TYPES);
		$this->zip->addFromString('docProps/core.xml',
			sprintf(DefaultXml::DOCPROPS_CORE, date(DATE_ISO8601), date(DATE_ISO8601)));
		$this->zip->addFromString('docProps/app.xml', DefaultXml::DOCPROPS_APP);
		$this->zip->addFromString('_rels/.rels', DefaultXml::RELS_RELS);
		$this->zip->addFromString('xl/_rels/workbook.xml.rels', DefaultXml::XL_RELS_WORKBOOK);
		$this->zip->addFromString('xl/workbook.xml', DefaultXml::XL_WORKBOOK);
	}

}
