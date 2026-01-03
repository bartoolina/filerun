<?php

class custom_msoffice extends \FileRun\Files\Plugin {

	var $online = true;
	static $localeSection = "Custom Actions: Office";

	var $ext = [
		'word' => ["doc", "docx", "docm", "dotm", "dotx", "odt"],
		'excel' => ["xls", "xlsx", "xlsb", "xls", "xlsm", "ods"],
		'powerpoint' => ["ppt", "pptx", "ppsx", "pps", "pptm", "potm", "ppam", "potx", "ppsm", "odp"],
		'project' => ['mpp'],
		'visio' => ['vsd', 'vsdx', 'vss', 'vst', 'vdx', 'vsx', 'vtx']
	];
	function init() {
		global $config;
		$postURL = $config['url']['root'].'/?module=custom_actions&action=msoffice&method=run';
		$this->JSconfig = [
			"title" => self::t("Office"),
			"icon" => 'images/icons/office.png',
			"extensions" => call_user_func_array('array_merge', $this->ext),
			"requiredUserPerms" => ["download"],
			"requires" => ["download"],
			"fn" => "FR.UI.backgroundPost(false, '".\S::safeJS($postURL)."')"
			/*'replaceDoubleClickAction' => true*/
		];
	}
	function run() {
		//Allowed URIs must conform to the standards proposed in RFC 3987 – Internationalized Resource Identifiers (IRIs)
		//Characters identified as reserved in RFC 3986 should not be percent encoded.
		//Filenames must not contain any of the following characters: \ / : ? < > | " or *.

		$extension = \FM::getExtension($this->data['fileName']);
		$type = false;
		foreach ($this->ext as $k => $extList) {
			if (in_array($extension, $extList)) {
				$type = $k;
				break;
			}
		}
		if (!$type) {return false;}

		global $config;
		$url = gluePath($config['url']['root'], '/dav.php/', \FileRun\Files\Utils::webDavPathFromReadPrepare($this->data));

		$redirectTo = 'ms-'.$type.':ofe|u|'.$url;
		header('Location: '.$redirectTo, true, 303);
		$this->logAction();
		exit();
	}
}