<?php

use FileRun\Perms;

class custom_code_editor extends \FileRun\Files\Plugin {

	public $weblinksCompatible = true;
	static $localeSection = "Custom Actions: Text Editor";
	static $publicMethods = ['saveChanges', 'thumb'];

	function init() {

		$this->JSconfig = [
			"title" => self::t("Text Editor"),
			'iconCls' => 'fa-file-text-o',
			'useWith' => ['code', 'txt', 'noext'],
			"popup" => true,
			"createNew" => [
				"title" => self::t("Text File"),
				"options" => [
					[
						'fileName' => self::t('New Text File.txt'),
						'title' => self::t('Plain Text'),
						'iconCls' => 'fa-file-text-o'
					],
					[
						'fileName' => 'readme.md',
						'title' => self::t('Markdown'),
						'icon' => 'images/icons/markdown.svg'
					],
					[
						'fileName' => '',
						'title' => self::t('Other..'),
						'iconCls' => 'fa-file-text-o'
					]
				]
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
		$this->data['contents'] = $this->readFile();
		$isEditable = false;
		if (Perms::check('upload')) {
			if (!$this->data['shareInfo'] || ($this->data['shareInfo'] && $this->data['shareInfo']['perms_upload'])) {
				$isEditable = true;
			}
		}
		if ($this->data['weblink'] && $isEditable) {
			$isEditable = \FileRun\WebLinks::verifyAllowEditing($this->data['weblink']['linkInfo']);
		}
		$list = [];
		$enc = mb_list_encodings();
		foreach($enc as $e) {
			$list[] = [$e];
		}
		$isClosable = $_REQUEST['_popup_id'] ? true : false;
		$vars = json_encode([
			'isEditable' => (bool) $isEditable,
			'isClosable' => $isClosable,
			'URLRoot' => $config['url']['root'],
			'actionURL' => $this->actionURL,
			'path' => $this->data['relativePath'],
			'filename' => $this->data['fileName'],
			'windowId' => \S::fromHTML($_REQUEST['_popup_id']),
			'charset' => \S::fromHTML($_REQUEST['charset']),
			'theme' => $settings->ui_theme,
			'charsets' => $list,
			'csrf' => self::getCSRFToken()
		]);
		if ($_REQUEST['charset'] && in_array($_REQUEST['charset'], $enc)) {
			$this->data['contents'] = \S::convert2UTF8($this->data['contents'], $_REQUEST['charset']);
		} else {
			$bom = pack("CCC", 0xef, 0xbb, 0xbf);
			if (strncmp($this->data['contents'], $bom, 3) === 0) {
				$this->data['contents'] = substr($this->data['contents'], 3);
			} else {
				$this->data['contents'] = \S::convert2UTF8($this->data['contents']);
			}
		}
		require $this->path."/display.php";
	}

	function saveChanges($opts = []) {
		$contents = \S::fromHTML($_POST['textContents']);
		$charset = \S::fromHTML($_POST['charset']);
		if ($charset != 'UTF-8') {
			$contents = \S::convertEncoding($contents, 'UTF-8', $charset);
		}
		$opts['source'] = 'string';
		$opts['contents'] = $contents;
		$this->writeFile($opts);
		jsonFeedback(true, 'File successfully saved');
	}

	function createBlankFile() {
		$_POST['textContents'] = '';
		$_POST['charset'] = 'UTF-8';
		$fileName = \S::fromHTML($_POST['fileName']);
		$this->data['relativePath'] = gluePath($this->data['relativePath'], $fileName);
		$this->saveChanges(['preventOverwrite' => true]);
	}
}