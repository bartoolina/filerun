<?php
/*
 * Plugin for authenticating FileRun users using simpleSAMLphp
 *
 * */
class customAuth_simplesamlphp {
	var $error, $errorCode, $ssaml, $attributes;
	function pluginDetails() {
		return [
			'name' => 'SimpleSAMLphp v1',
			'description' => 'Authenticate users with <a href="https://simplesamlphp.org/" target="_blank">SimpleSAMLphp</a>.<br>Compatibility has been tested against version 1.',
			'fields' => [
				[
					'name' => 'path',
					'label' => 'Path to simpleSAMLphp',
					'required' => true
				],
				[
					'name' => 'auth_source',
					'label' => 'Authentication source',
					'default' => 'default-sp'
				],
				[
					'name' => 'mapping_username',
					'label' => 'Username attribute mapping',
					'default' => 'urn:oid:0.9.2342.19200300.100.1.1',
					'required' => true
				],
				[
					'name' => 'mapping_name',
					'label' => 'First name attribute mapping',
					'default' => 'urn:oid:2.5.4.42',
					'required' => true
				],
				[
					'name' => 'mapping_name2',
					'label' => 'Last name attribute mapping',
					'default' => 'urn:oid:2.5.4.4'
				],
				[
					'name' => 'mapping_email',
					'label' => 'E-mail attribute mapping',
					'default' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.9'
				],
				[
					'name' => 'mapping_groups',
					'label' => 'Group names attribute mapping',
					'default' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.1'
				],
				[
					'name' => 'groups_to_import',
					'label' => 'Groups to import',
					'default' => '',
					'helpText' => 'Comma separated list of group names that will be created inside FileRun.'
				],
				[
					'name' => 'groups_to_allow_access',
					'label' => 'Groups to allow access to',
					'default' => '',
					'helpText' => 'Comma separated list of group names the users need to be members of in order to be allowed access.<br>The groups need to be included in the list of "groups to import".'
				]
			]
		];
	}

	function pluginTest($opts) {
		$pluginInfo = self::pluginDetails();
		//check required fields
		foreach($pluginInfo['fields'] as $field) {
			if ($field['required'] && !$opts['auth_plugin_simplesamlphp_' . $field['name']]) {
				return 'The field "' . $field['label'] . '" needs to have a value.';
			}
		}
		//check folder existance
		if (!is_dir($opts['auth_plugin_simplesamlphp_path'])) {
			return 'The path you specified does not point to an existing folder.';
		}
		//check that folder has index.php
		if (!is_file(gluePath($opts['auth_plugin_simplesamlphp_path'], '/lib/SimpleSAML/Auth/Simple.php'))) {
			return 'simpleSAMLphp was not found at the specified path.';
		}
		return 'Things seem to be in order.';
	}
	static function getSetting($fieldName) {
		global $settings;
		$keyName = 'auth_plugin_simplesamlphp_'.$fieldName;
		return $settings->$keyName;
	}

	function getSSAML() {
		if (!$this->ssaml) {
			require_once(gluePath(self::getSetting('path'), '/lib/_autoload.php'));
			$this->ssaml = new \SimpleSAML\Auth\Simple(self::getSetting('auth_source'));
		}
		return $this->ssaml;
	}

	function singleSignOn() {
		global $config;
		$this->getSSAML();
		$this->ssaml->requireAuth(['ReturnTo' => $config['url']['root'] . '/sso']);
		$this->attributes = $this->ssaml->getAttributes();
		$username = $this->attributes[self::getSetting('mapping_username')][0];
		if (!$username) {return false;}
		$userInfo = $this->getUserInfo($username);
		$groupsToAllow = self::getGroupsToAllow();
		if ($groupsToAllow) {
			$foundInGroup = false;
			foreach($userInfo['userGroups'] as $groupName) {
				if (in_array(mb_strtolower($groupName), $groupsToAllow)) {
					$foundInGroup = true;
					break;
				}
			}
			if (!$foundInGroup) {
				$this->error = 'Your user account does not have access to this service!';
				$this->errorCode = 'NO_GROUP_ACCESS';
				return false;
			}
		}
		return $username;
	}

	function getUserInfo($username) {
		//Here you can either look up the user info in a remote database:

		//1. connect to LDAP
		//2. $remoteRecord = ldap_get_attributes($connection_id, ldap_first_entry($connection_id, ldap_search($connection_id, 'dc=example,dc=com', '(&(uid='.$username.')(objectClass=person))')));

		//or use the attributes returned by SimpleSAMLphp:
		$remoteRecord = $this->attributes;

		$userData = [
			'name' => $remoteRecord[self::getSetting('mapping_name')][0],
			'name2' => $remoteRecord[self::getSetting('mapping_name2')][0],
			'email' => $remoteRecord[self::getSetting('mapping_email')][0]
		];

		$groups = [];
		if (self::getSetting('mapping_groups')) {
			$userGroups = $remoteRecord[self::getSetting('mapping_groups')];
			if (is_array($userGroups)) {
				$groups = $this->filterGroups($userGroups);
			}
		}
		$groups[] = 'SimpleSAMLPHP';

		if (!$userData['name']) {
			$this->error = 'Missing name for the user record';
			return false;
		}
		$userPerms = [];
		return [
			'userData' => $userData,
			'userPerms' => $userPerms,
			'userGroups' => $groups
		];
	}

	function filterGroups($userGroups): array {
		$groups = [];
		$groupsToImport = self::getGroupsToImport();
		foreach ($userGroups as $key => $entry) {
			if ($groupsToImport) {
				if (!in_array(mb_strtolower($entry), $groupsToImport)) {
					continue;
				}
			}
			$groups[] = $entry;
		}
		return $groups;
	}

	static function getGroupsToImport(): array {
		$val = trim(self::getSetting('groups_to_import'));
		if ($val === '') {return [];}
		$rs = trim_array(explode(',', mb_strtolower($val)));
		$items = [];
		foreach($rs as $groupName) {
			if (!$groupName) {continue;}
			$items[] = $groupName;
		}
		return $items;
	}

	static function getGroupsToAllow(): array {
		$val = trim(self::getSetting('groups_to_allow_access'));
		if ($val === '') {return [];}
		$rs = trim_array(explode(',', mb_strtolower($val)));
		$items = [];
		foreach($rs as $groupName) {
			if (!$groupName) {continue;}
			$items[] = $groupName;
		}
		return $items;
	}

	function logout() {
		global $settings, $config;
		$this->getSSAML();
		if ($this->ssaml->isAuthenticated()) {
			if ($settings->logout_redirect) {
				$redirect = $settings->logout_redirect;
			} else {
				$redirect = $config['url']['root'];
			}
			$this->ssaml->logout($redirect);
		}
	}

	function authenticate($username, $password) {
		$this->errorCode = 'USERNAME_NOT_FOUND';//allows fall back to local authentication
		$this->error = 'The provided username is not valid';
		return false;
	}
}