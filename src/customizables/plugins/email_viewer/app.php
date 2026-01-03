<?php
/*
sudo apt-get install libemail-outlook-message-perl
sudo apt-get install php-mailparse
sudo apachectl restart
*/

class custom_email_viewer extends \FileRun\Files\Plugin {

	static $localeSection = "Custom Actions: Message Viewer";

	function init() {

		$this->JSconfig = [
			"title" => self::t('Message Viewer'),
			'iconCls' => 'fa fa-fw fa-envelope-o',
			'extensions' => ['msg', 'eml'],
			'popup' => true,
			'requiredUserPerms' => ['download'],
			'requires' => ['download']
		];
	}

	function run() {
		global $config, $settings;
		header('Content-Security-Policy: sandbox;');
		$ext = \FM::getExtension($this->data['fileName']);

		spl_autoload_register(function($class) {
			if (strpos($class, 'PhpMimeMailParser') === 0) {
				$p = gluePath($this->path, str_replace('\\', '/', $class).'.php');
			}
			if (is_file($p)) {
				include $p;
				return true;
			}
		});

		if ($ext != 'eml') {
			$cmd = 'msgconvert --outfile - '.escapeshellarg($this->data['fullPath']);
			$return_text = [];
			$return_code = 0;
			exec($cmd, $return_text, $return_code);
			if ($return_code != 0) {
				exit('Mail format conversion failed!');
			}
			$parser = \PhpMimeMailParser\Parser::fromText(implode("\n", $return_text));
			unset($return_text);
		} else {
			$parser = \PhpMimeMailParser\Parser::fromStream($this->readFile(['returnFilePointer' => true]));
		}


		$headingHtml = '';
		$headers = $parser->getHeaders();
		foreach ($headers as $k => $v) {
			if (in_array($k, ['from', 'to', 'subject', 'date'])) {
				$headingHtml .= '<b>'.ucfirst($k).'</b>: '.$v.'<br>';
			}
		}
		$headingHtml .= '</div>';

		$nonce = base64_encode(random_bytes(20));
		$csp = "default-src 'none'; base-uri 'self'; form-action 'none';".
			"script-src 'nonce-".$nonce."'; " .
			"font-src 'nonce-".$nonce."'; " .
			"connect-src 'none'; " .
			"navigate-to 'none'; " .
			"img-src 'self' data:; style-src 'self' 'unsafe-inline';";
		header('Content-Security-Policy: '.$csp);
		header("X-Content-Type-Options: nosniff");


		require $config['path']['classes'] . '/vendor/HTMLPurifier/HTMLPurifier.auto.php';
		$cfg = HTMLPurifier_Config::createDefault();
		$cfg->set('Cache.SerializerPath', $config['path']['temp'].'/smarty/');
		$cfg->set('URI.AllowedSchemes', ['data' => true]);
		$cfg->set('AutoFormat.RemoveEmpty', true);
		$purifier = new HTMLPurifier($cfg);
		?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title><?php echo \S::safeHTML(\S::forHTML($this->data['fileName']));?></title>
		<link href="<?php echo $this->url;?>/style<?php if ($settings->ui_theme == 'dark') {echo '_dark';}?>.css" rel="stylesheet"  nonce="<?php echo $nonce;?>" />
	</head>
	<body>

	<div class="headers">
	<?php echo $headingHtml;?>
	</div>
	<?php echo $purifier->purify($parser->getHtml());?>

	</body>
	</html>
<?php
	}
}