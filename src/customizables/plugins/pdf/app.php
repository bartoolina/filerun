<?php
use FileRun\Files;

class custom_pdf extends Files\Plugin {

	public $weblinksCompatible = true;
	static $localeSection = 'Custom Actions: PDF Viewer';
	static $publicMethods = ['openInBrowser', 'openPDFInBrowser', 'thumb'];

	function init() {
		$this->JSconfig = [
			"title" => self::t('PDF Viewer'),
			'iconCls' => 'fa-file',
			'extensions' => ['pdf'],
			'popup' => true,
			'requires' => ['preview'],
			'loadingMsg' => self::t('Loading...')
		];
		$this->settings = [
			[
				'key' => 'allow_without_download',
				'title' => self::t('Allow opening PDF files without download permission'),
				'type' => 'checkbox',
				'helpText' => self::t('Use this option only if your documents are not confidential.')
			]
		];
	}

	function run($url = null) {
		$isLimitedPreview = $this->isLimitedPreview();
		$allowPDFwithoutDownload = $this->getSetting('allow_without_download');

		if (!$url) {
			if ($isLimitedPreview) {
				if ($allowPDFwithoutDownload) {
					$url = $this->actionURL.'&method=openPDFInBrowser#toolbar=0';
				} else {
					$this->centeredThumb();
					return;
				}
			} else {
				$url = $this->actionURL.'&method=openInBrowser';
			}
		}
		require $this->path."/display.php";
	}

	function openPDFInBrowser() {
		$extension = \FM::getExtension($this->data['fileName']);
		if ($extension != 'pdf') {return false;}
		$this->streamFileForPreview();
	}
}