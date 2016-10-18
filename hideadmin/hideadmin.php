<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

function hideadmin_config() 
{
	return array(
		'name' 		=> 'Jetserver Hide Admin',
		'description' 	=> 'This addon module will give you option to hide yourself or other admins from staff members in the Staff Online section',
    		'version' 	=> '1.0.2',
		'author' 	=> 'Idan Ben-Ezra',
		'language' 	=> 'english',
	);
}

function hideadmin_activate() 
{
	$sql = "CREATE TABLE IF NOT EXISTS `mod_hideadmin_config` (
			`name` varchar(255) NOT NULL,
			`value` text NOT NULL,
			PRIMARY KEY (`name`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8";
	mysql_query($sql);

	$sql = "INSERT INTO `mod_hideadmin_config` (`name`, `value`) VALUES
		('localkey', ''),
		('allowedadmins', ''),
		('autohidetime', '5')";
	mysql_query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `mod_hideadmin_hidden` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`adminid` int(11) unsigned NOT NULL DEFAULT '0',
			`hidden` tinyint(1) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
	mysql_query($sql);

	return array(
		'status'	=> 'success',
		'description'	=> 'Module activated successfully'
	);
}

function hideadmin_deactivate() 
{
	mysql_query("DROP TABLE IF EXISTS `mod_hideadmin_config`");
	mysql_query("DROP TABLE IF EXISTS `mod_hideadmin_hidden`");

	return array(
		'status'	=> 'success',
		'description'	=> 'Module deactivated successfully'
	);
}

function hideadmin_output($vars) 
{
	global $whmcs, $cc_encryption_hash, $LANG, $CONFIG;

	require_once(dirname(__FILE__) . '/functions.php');

	$modulelink = $vars['modulelink'];
	$LANG = $vars['_lang'];

	$hideadmin = new hideadmin;

	$admins = $success = array();

	$sql = "SELECT username
		FROM tbladmins";
	$result = mysql_query($sql);

	while($admin_details = mysql_fetch_assoc($result))
	{
		$admins[] = $admin_details['username'];
	}
	mysql_free_result($result);

	$submit = $_REQUEST['submit'] ? true : false;

	if($submit)
	{
		$autohidetime = intval($_REQUEST['autohidetime']);
		$selectedadmins = $_REQUEST['selectedadmins'];

		$allowedadmins = array();

		if(is_array($selectedadmins) && sizeof($selectedadmins))
		{
			foreach($selectedadmins as $username)
			{
				if(in_array($username, $admins)) $allowedadmins[] = $username;
			}
		}

		$hideadmin->setConfig('autohidetime', $autohidetime);
		$hideadmin->setConfig('allowedadmins', implode(',', $allowedadmins));

		$success[] = "Settings updated successfully";
	}                                                    

?>

<script type="text/javascript">
$(document).ready(function() {

	$("#adminadd").click(function () {
		$("#adminslist option:selected").appendTo("#selectedadmins");
		return false;
	});

	$("#adminrem").click(function () {
		$("#selectedadmins option:selected").appendTo("#adminslist");
		return false;
	});
});
</script>

<?php if(sizeof($success)) { ?>
<div class="successbox">
	<strong><span class="title"><?php echo $hideadmin->lang['success']; ?></span></strong><br />
	<?php echo implode("<br />", $success); ?>
</div>
<?php } ?>

<form action="<?php echo $modulelink; ?>" method="post">

<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">
<tbody>
<tr>
	<td class="fieldlabel" style="width: 30%;"><?php echo $hideadmin->lang['allowedadmins']; ?></td>
	<td class="fieldarea">

		<table>
		<tbody>
		<tr>
			<td>
				<select style="width:200px;" id="adminslist" multiple="multiple" size="10">
					<?php foreach($admins as $username) { ?>
					<?php if(in_array($username, explode(',', $hideadmin->config['allowedadmins']))) continue; ?>
					<option value="<?php echo $username; ?>"><?php echo $username; ?></option>
					<?php } ?>
				</select>
			</td>
			<td align="center">
				<input type="button" class="btn btn-sm" value="<?php echo $hideadmin->lang['add']; ?> »" id="adminadd"><br><br>
				<input type="button" class="btn btn-sm" value="« <?php echo $hideadmin->lang['remove']; ?>" id="adminrem">
			</td>
			<td>
				<select style="width:200px;" name="selectedadmins[]" id="selectedadmins" multiple="multiple" size="10">
					<?php foreach($admins as $username) { ?>
					<?php if(!in_array($username, explode(',', $hideadmin->config['allowedadmins']))) continue; ?>
					<option value="<?php echo $username; ?>"><?php echo $username; ?></option>
					<?php } ?>
				</select>
			</td>
		</tr>
		</tbody>
		</table>

	</td>
</tr>
<tr>
	<td class="fieldlabel" style="width: 30%;"><?php echo $hideadmin->lang['autohidetime']; ?></td>
	<td class="fieldarea">
		<input type="text" name="autohidetime" value="<?php echo $hideadmin->config['autohidetime']; ?>" size="3" /> Minutes
	</td>
</tr>
</tbody>
</table>

<p align="center"><input type="submit" class="btn btn-primary" name="submit" onclick="$('#selectedadmins *').attr('selected','selected')" value="<?php echo $hideadmin->lang['save_changes']; ?>" /></p>

</form>

<p style="text-align: center;">Plugin Version <?php echo $vars['version']; ?></p>

<?php
}

?>
