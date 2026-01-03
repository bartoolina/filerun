<?php
use \FileRun\Files;
use \setasign\Fpdi;

class custom_pdf_merge extends Files\Plugin {

	static $localeSection = 'Custom Actions: Merge PDF files';

	function init() {
		$this->JSconfig = [
			'title' => self::t('Merge PDF files'),
			'iconCls' => 'fa-copy',
			"ajax" => true,
			"requiredUserPerms" => ["download", "upload"],
			'requires' => ['multiple', 'download', 'create', 'alter', 'pdfs']
		];
	}

	function run() {
		$targetFolderRelativePath = \S::fromHTML($_POST['currentPath']);
		$pathInfo = Files\Utils::parsePath($targetFolderRelativePath);
		if (!in_array($pathInfo['type'], ['home', 'shared'])) {
			$targetFolderRelativePath = '/ROOT/HOME';
		}

		$fileName = [];
		foreach ($this->data as $readFiledata) {
			$fileName[] = '('.\FM::stripExtension($readFiledata['fileName']).')';
		}
		$fileName = implode(' ', $fileName);
		$targetFileData = $this->prepareWrite(['relativePath' => gluePath($targetFolderRelativePath, $fileName.'.pdf')]);

		$i = 0;
		while ($targetFileData['exists']) {
			$i++;
			$fileName .= ' ('.$i.')';
			$targetFileData = $this->prepareWrite(['relativePath' => gluePath($targetFolderRelativePath, $fileName.'.pdf')]);
		}

		$pdf = new Fpdi\Tfpdf\Fpdi();

		foreach ($this->data as $readFiledata) {
			try {
				$pageCount = $pdf->setSourceFile($readFiledata['fullPath']);
			} catch (Exception $e) {
				jsonFeedback(false, $readFiledata['fileName'].': '.$e->getMessage());
			}
			for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
				// import a page
				$templateId = $pdf->importPage($pageNo);
				// get the size of the imported page
				$size = $pdf->getTemplateSize($templateId);

				// add a page with the same orientation and size
				$pdf->AddPage($size['orientation'], $size);
				// use the imported page
				$pdf->useTemplate($templateId);
			}
		}
		$this->writeFile([
			'preparedData' => $targetFileData,
			'source' => 'string',
			'contents' => $pdf->Output('S')
		]);
		jsonOutput([
			'success' => true,
			'msg' => self::t('PDF file successfully created!'),
			'updates' => [
				 [
					'path' => $targetFolderRelativePath,
					'updates' => ['new_file' => $targetFileData['fileName']]
				 ]
			]
		]);
	}
}
