<?php

class hideadmin
{
	var $config;
	var $lang;

	function __construct()
	{
		global $LANG;

		$this->_loadConfig();

		if(!isset($LANG))
		{
			$this->_loadLanguage();
		}
		else
		{
			$this->lang = $LANG;
		}

		$this->_loadConfig();
	}

	function _loadLanguage()
	{
		global $CONFIG;

		$sql = "SELECT language
			FROM tbladmins
			WHERE id = '{$_SESSION['adminid']}'";
		$result = mysql_query($sql);
		$admin_details = mysql_fetch_assoc($result);

		$default = 'english';
		$language = strtolower($admin_details['language']);

		$admin_lang_file = "../modules/addons/hideadmin/lang/{$language}.php";
		$default_lang_file = "../modules/addons/hideadmin/lang/{$default}.php";

		if(file_exists($admin_lang_file))
		{
			require_once($admin_lang_file);
		}
		elseif(file_exists($default_lang_file))
		{
			require_once($default_lang_file);
		}

		$this->lang = isset($_ADDONLANG) ? $_ADDONLANG : array();
	}

	function _loadConfig()
	{
		$sql = "SELECT *
			FROM mod_hideadmin_config";
		$result = mysql_query($sql);

		while($row = mysql_fetch_assoc($result))
		{
			$this->config[$row['name']] = $row['value'];
		}
		mysql_free_result($result);
	}

	function setConfig($key, $value)
	{
		if(isset($this->config[$key]))
		{
			$sql = "UPDATE mod_hideadmin_config
				SET value = '" . mysql_escape_string($value) . "'
				WHERE name = '" . mysql_escape_string($key) . "'";
			mysql_query($sql);
		}
		else
		{
			$sql = "INSERT INTO mod_hideadmin_config (`name`,`value`) VALUES
				('" . mysql_escape_string($key) . "','" . mysql_escape_string($value) . "')";
			mysql_query($sql);
		}

		$this->config[$key] = $value;
	}

	function addFlag($adminid, $hidden = 0)
	{
		$this->deleteFlag($adminid);

		$sql = "INSERT INTO mod_hideadmin_hidden (`adminid`,`hidden`) VALUES
			('{$adminid}', '{$hidden}')";
		mysql_query($sql);

		$this->flag = mysql_insert_id();
	}

	function deleteFlag($adminid)
	{
		$sql = "DELETE
			FROM mod_hideadmin_hidden
			WHERE adminid = '{$adminid}'";
		mysql_query($sql);
	}

	function updateFlag($adminid, $hidden)
	{
		$sql = "UPDATE mod_hideadmin_hidden
			SET hidden = '{$hidden}'
			WHERE adminid = '{$adminid}'";
		mysql_query($sql);
	}

	function getFlag($adminid)
	{
		$sql = "SELECT *
			FROM mod_hideadmin_hidden
			WHERE adminid = '{$adminid}'";
		$result = mysql_query($sql);
		$details = mysql_fetch_assoc($result);

		return $details ? $details : null;
	}

	function isHidden($adminid, $username, $logintime)
	{
		$hide_details = $this->getFlag($adminid);

		if(isset($hide_details) && $hide_details['hidden'])
		{
			return 1;
		}
		elseif(isset($hide_details) && !$hide_details['hidden'])
		{
			return 0;
		}
		elseif(in_array($username, explode(',', $this->config['allowedadmins'])) && ($this->config['autohidetime'] <= 0 || ($this->config['autohidetime'] > 0 && ((time() - $logintime) < ($this->config['autohidetime'] * 60)))))
		{
			return 2;
		}

		return 0;
	}

	function getAdminList()
	{
		$admins = array();

		$sql = "SELECT DISTINCT l.id as logid, l.adminusername, l.logintime, a.id
			FROM tbladminlog as l
			INNER JOIN tbladmins as a
			ON a.username = l.adminusername
			WHERE l.lastvisit >= '" . date( "Y-m-d H:i:s", mktime( date( "H" ), date( "i" ) - 15, date( "s" ), date( "m" ), date( "d" ), date( "Y" ) ) ) . "' 
			AND l.logouttime = '0000-00-00'
			ORDER BY l.logintime ASC";
		$result = mysql_query($sql);

		while ($data = mysql_fetch_array( $result )) 
		{
			$logintime = strtotime($data['logintime']);
			$isHidden = $this->isHidden($data['id'], $data['adminusername'], $logintime);

			if(!$isHidden || $_SESSION['adminid'] == $data['id'])
			{
				if($isHidden && $_SESSION['adminid'] == $data['id'])
				{
					$data['adminusername'] .= " <strong style=\"color: #CC0000;\">[ Hidden ]</strong>";
				}

				$admins[] = $data['adminusername'];
			}
		}

		return $admins;
	}
}

?>