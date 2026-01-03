<?php
use \FileRun\Perms;
use \FileRun\Files;

class custom_csv_editor extends Files\Plugin {

	public $weblinksCompatible = true;
	static $localeSection = "Custom Actions: CSV Editor";
	static $publicMethods = ['saveChanges', 'openInBrowser', 'thumb'];

	function init() {

		$this->JSconfig = [
			"title" => self::t('CSV Editor'),
			'iconCls' => 'fa-file-csv',
			'extensions' => ['csv', 'tsv'],
			"popup" => true,
			"createNew" => [
				"title" => self::t('CSV File'),
				'defaultFileName' => self::t('data.csv'),
				'iconCls' => 'fa-file-csv'
			],
			"requiredUserPerms" => ["download"],
			'requires' => ['download']
		];
	}

	function run() {
		global $settings, $config;
		if ($this->isLimitedPreview()) {
			$this->centeredThumb();
			return;
		}
		$isEditable = false;
		if (Perms::check('upload')) {
			if (!$this->data['shareInfo'] || ($this->data['shareInfo'] && $this->data['shareInfo']['perms_upload'])) {
				$isEditable = true;
			}
		}
		if ($this->data['weblink'] && $isEditable) {
			$isEditable = \FileRun\WebLinks::verifyAllowEditing($this->data['weblink']['linkInfo']);
		}
		$isClosable = isset($_REQUEST['_popup_id']);
		$extension = \FM::getExtension($this->data['fileName']);

		$vars = json_encode([
			'isEditable' => $isEditable,
			'isClosable' => $isClosable,
			'URLRoot' => $config['url']['root'],
			'actionURL' => $this->actionURL,
			'fileURL' => $this->actionURL.'&method=openInBrowser',
			'path' => $this->data['relativePath'],
			'filename' => $this->data['fileName'],
			'windowId' => \S::fromHTML($_REQUEST['_popup_id']),
			'theme' => $settings->ui_theme,
			'delimiter' => ($extension == 'tsv' ? "\t" : ',')
		]);
		require $this->path."/display.php";
	}

	function saveChanges($opts = []) {
		$data = $this->prepareWrite($opts);
		if ($data['folder']) {return false;}
		$data['logging'] = $this->prepareLoggingDetails();
		$rs = Files\Actions\Write\Write::onBeforeWrite($data, 'string');
		if (!$rs) {
			self::outputError(Files\Actions\Write\Write::getError()['public']);
			return false;
		}
		$fp = fopen($data['fullPath'], 'wb');
		if ($_POST['csvHeaders']) {
			$headers = json_decode($_POST['csvHeaders'], true);
			fputcsv($fp, $headers);
		}
		$extension = \FM::getExtension($this->data['fileName']);
		if ($_POST['textContents']) {
			$rows = json_decode($_POST['textContents'], true);
		} else {
			$rows = [];
		}
		foreach ($rows as $row) {
			fputcsv($fp, $row, $extension == 'tsv' ? "\t":',');
		}
		fclose($fp);
		Files\Actions\Write\Write::onAfterWrite($data);
		jsonFeedback(true, 'File successfully saved');
	}

	function createBlankFile() {
		$_POST['csvHeaders'] = json_encode(['A', 'B']);
		$_POST['textContents'] = json_encode([['','']]);
		$fileName = \S::fromHTML($_POST['fileName']);
		$this->data['relativePath'] = gluePath($this->data['relativePath'], $fileName);
		$this->saveChanges(['preventOverwrite' => true]);
	}
}