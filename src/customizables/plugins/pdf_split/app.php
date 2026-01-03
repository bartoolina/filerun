<?php
use \setasign\Fpdi\Tfpdf\Fpdi;
use \FileRun\Files\Actions\Write;

class custom_pdf_split extends \FileRun\Files\Plugin {

	static $localeSection = 'Custom Actions: Extract PDF pages';
	static $publicMethods = ['split'];

	function init() {
		$this->JSconfig = [
			'title' => self::t('Extract PDF pages'),
			'iconCls' => 'fa-page-break',
			'extensions' => ['pdf'],
			"requiredUserPerms" => ["download", "upload"],
			'requires' => ['download', 'create', 'alter'],
			'fn' => 'FR.customActions.pdf_split.run()'
		];
	}

	function run() {}

	function JSinclude() {
		include gluePath($this->path, "include.js.php");
	}

	function split() {
		$postPages = \S::fromHTML($_POST['pages']);
		$split = \S::fromHTML($_POST['split']);
		$extension = \FM::getExtension($this->data[0]['fileName']);
		if ($extension != 'pdf') {
			jsonFeedback(false, 'The selected file must be a PDF file.');
		}

		$fh = fopen($this->data[0]['fullPath'], 'rb');
		$pdf = new Fpdi();
		try {
			$pageCount = $pdf->setSourceFile($fh);
		} catch (Exception $e) {
			jsonFeedback(false, $this->data[0]['fileName'].': '.$e->getMessage());
		}
		//echo ' ['.\FM::formatFileSize(memory_get_usage()).'] ';

		$pages = str_replace(' ', '', $postPages);
		$parts = explode(',', $pages);
		$pages = [];
		foreach ($parts as $part) {
			if (strpos($part, '-') !== false) {
				$subparts = trim_array(explode('-', $part));
				if (count($subparts) < 2) {continue;}
				if ($subparts[0] > $subparts[1]) {
					for ($i = $subparts[0]; $i >= $subparts[1]; $i--) {
						$pageNo = $i;
						if (!$pageNo || $pageNo > $pageCount) {continue;}
						if ($pageNo > $pageCount) {
							jsonFeedback(false, self::t('The page %1 was not found in the document!', [$pageNo]));
						}
						$pages[] = $pageNo;
					}
				} else {
					for ($i = $subparts[0]; $i <= $subparts[1]; $i++) {
						$pageNo = $i;
						if (!$pageNo || $pageNo > $pageCount) {continue;}
						if ($pageNo > $pageCount) {
							jsonFeedback(false, self::t('The page %1 was not found in the document!', [$pageNo]));
						}
						$pages[] = $pageNo;
					}
				}
			} else {
				$pageNo = $part;
				if (!$pageNo) {continue;}
				if ($pageNo > $pageCount) {
					jsonFeedback(false, self::t('The page %1 was not found in the document!', [$pageNo]));
				}
				$pages[] = $pageNo;
			}
		}

		$sourceFolderRelativePath = \FM::dirname($this->data[0]['relativePath']);
		$originalFileNameWithoutExt = \FM::stripExtension($this->data[0]['fileName']);

		$targetFolder = Write\Prepare::prepareDestinationFolder($sourceFolderRelativePath);
		if (!$targetFolder) {
			self::outputError(Write\Prepare::getError()['public']);
		}

		$resultingFileNames = [];

		foreach ($pages as $pageNo) {
			if ($split) {
				$pdf = new Fpdi();
				$pageCount = $pdf->setSourceFile($fh);
			}
			$pdf->AddPage();
			try {
				$pdf->useTemplate($pdf->importPage($pageNo), ['adjustPageSize' => true]);
			} catch (Exception $e) {
				jsonFeedback(false, $e->getMessage());
			}
			//echo ' ['.\FM::formatFileSize(memory_get_usage()).'] ';
			if ($split) {
				$targetFileName = self::t('Page %1 from ', [\FM::stripIlegalCharacters($pageNo)]).$originalFileNameWithoutExt.'.pdf';
				$targetFileRelativePath = gluePath($targetFolder['relativePath'], $targetFileName);
				$this->writeFile([
					'relativePath' => $targetFileRelativePath,
					'source' => 'string',
					'contents' => $pdf->Output('S')
				]);
				$resultingFileNames[] = $targetFileName;
			}
		}
		if (!$split) {
			$targetFileName = self::t('Pages %1 from ', [\FM::stripIlegalCharacters($postPages)]).$originalFileNameWithoutExt.'.pdf';
			$targetFileRelativePath = gluePath($targetFolder['relativePath'], $targetFileName);
			$this->data = [];
			$this->writeFile([
				'relativePath' => $targetFileRelativePath,
				'source' => 'string',
				'contents' => $pdf->Output('S')
			]);
			$resultingFileNames[] = $targetFileName;
		}

		jsonOutput([
			'success' => true,
			'msg' => self::t('PDF file successfully created!'),
			'updates' => [
				[
					'path' => $sourceFolderRelativePath,
					'updates' => ['new_file' => $resultingFileNames]
				]
			]
		]);
	}
}
