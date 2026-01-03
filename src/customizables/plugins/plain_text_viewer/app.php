<?php

class custom_plain_text_viewer extends \FileRun\Files\Plugin {

	public $weblinksCompatible = true;
	static $localeSection = 'Custom Actions: Text Viewer';
	static $publicMethods = ['thumb'];

	function init() {
		$this->JSconfig = [
			"title" => self::t("Text Viewer"),
			'iconCls' => 'fa-file-text-o',
			'useWith' => ['txt', 'noext'],
			"popup" => true,
			"requiredUserPerms" => ["preview"],
			"requires" => ["preview"]
		];
	}

	function run() {
		if ($this->isLimitedPreview()) {
			$this->centeredThumb();
			return;
		}
		$this->data['contents'] = $this->readFile(['errorHandling' => 'html']);

		header("X-Content-Type-Options: nosniff");

		$nonce = (new \PassPolicy())->generate(['min_length' => 20, 'requires_special' => false]);
		$csp = "default-src 'none'; base-uri 'self'; form-action 'none';".
			"script-src 'none'; " .
			"font-src 'nonce-".$nonce."'; " .
			"connect-src 'none'; ".
			"img-src data:; ".
			"style-src 'self' 'nonce-".$nonce."' 'unsafe-inline';";
		header('Content-Security-Policy: '.$csp);
?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title><?php echo \S::safeHTML(\S::forHTML($this->data['fileName']));?></title>
		<?php \FileRun\UI\CSS::insertLink([
				'absoluteURLs' => true,
				'basic' => true,
				'plain' => true
		]);?>
	</head>
	<body><?php echo \S::safeForHtml(\S::convert2UTF8($this->data['contents']));?></body>
	</html>
<?php
	}
}