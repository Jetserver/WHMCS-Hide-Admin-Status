<?php
/*
*
* Hide Admin
* Created By Idan Ben-Ezra
*
* Copyrights @ Jetserver Web Hosting
* www.jetserver.net
*
* Hook version 1.0.1
*
**/

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

function hideadmin_addPopup($vars) 
{
	global $aInt;

	require_once(dirname(__FILE__) . '/functions.php');

	$hideadmin = new hideadmin;

	$sql = "SELECT *
		FROM tbladmins
		WHERE id = '" . intval($_SESSION['adminid']) . "'";
	$result = mysql_query($sql);
	$admin_details = mysql_fetch_array($result);

	if($_SESSION['adminid'] && $_REQUEST['hideAdminAjax'])
	{
		if($admin_details && in_array($admin_details['username'], explode(',', $hideadmin->config['allowedadmins'])))
		{
			$hideadmin->addFlag(intval($_SESSION['adminid']));

			$btns = array('yes' => 1, 'no' => 0);

			if(isset($btns[$_REQUEST['btn']]))
			{
				$hideadmin->updateFlag(intval($_SESSION['adminid']), $btns[$_REQUEST['btn']]);
			}

			echo json_encode(array('success' => true, 'message' => '', 'admins' => implode(', ', $hideadmin->getAdminList())));
			exit;
		}
		else
		{
			echo json_encode(array('success' => false, 'message' => $hideadmin->lang['accessdenied']));
			exit;
		}
	}

	$admins = $hideadmin->getAdminList();

	$show_popup = ($hideadmin->getFlag($_SESSION['adminid']) || $hideadmin->isHidden($_SESSION['adminid'], $admin_details['username'], $logintime) == 1) ? false : true;

	$scripts = "
	<style type=\"text/css\">#left_side > div.smallfont:last-child, #sidebar > div.smallfont:last-child { display: none; }</style>
	<script type=\"text/javascript\">
		var staffonline = '" . implode(", ", $admins) . "';

		$(document).ready(function() { 
			$('#left_side div.smallfont:last, #sidebar div.smallfont:last').html(staffonline).css('display', 'block');
		});
	</script>";

	if(in_array($admin_details['username'], explode(',', $hideadmin->config['allowedadmins'])))
	{
		$scripts .= "
		<script type=\"text/javascript\">

			var hideadminpopup = null;
			var floatRightYesBtn = null;
			var floatRightNoBtn = null;
			var floatRightLoading = null;

			$(document).ready(function() { 

				$('#left_side div.smallfont:last, #sidebar div.smallfont:last').append('<br /><br />[ <a href=\"javascript: void(0);\" class=\"hideadmin-change\">{$hideadmin->lang['changesettings']}</a> ]');

				hideadminpopup = $('<div />').attr({ id: \"hideadminpopup\" }).css({ 
					position: 'fixed',
					top: '0',
					left: '0',
					width: '100%',
					zIndex: '9999',
					background: '#fff4b5',
					borderBottom: '1px solid #625718',
					display: 'none'
				});

				var contentBox = $('<div />').css({ padding: '5px 10px' });

				floatRightYesBtn = $('<div />').css({ float: 'right', marginRight: '5px' });
				floatRightNoBtn = floatRightYesBtn.clone();
				floatRightLoading = floatRightYesBtn.clone().css({ display: 'none', marginTop: '7px' }).html('<img src=\"images/loading.gif\" alt=\"\" />');

				var yesBtn = $('<button />').addClass('btn btn-default').html('{$hideadmin->lang['hideme']}');
				var noBtn = $('<button />').addClass('btn btn-danger').html('{$hideadmin->lang['donthideme']}');

				floatRightYesBtn.append(yesBtn);
				floatRightNoBtn.append(noBtn);

				contentBox.append('<div style=\"font-size: 16px; float: left; margin-top: 5px;\">{$hideadmin->lang['doyouwanttobehidden']}</div>')
					.append(floatRightYesBtn)
					.append(floatRightNoBtn)
					.append(floatRightLoading)
					.append('<br style=\"clear: both;\" />');

				$('body').append(hideadminpopup.append(contentBox));

				" . ($show_popup ? "hideadminpopup.slideDown('slow');" : '') . "

				floatRightYesBtn.click(function() { hideAdminBtnClick('yes'); });
				floatRightNoBtn.click(function() { hideAdminBtnClick('no'); });

				$('.hideadmin-change').click(function() {
					hideadminpopup.slideDown('slow');
				});
			});

			function hideAdminBtnClick(btn)
			{
				floatRightYesBtn.css('display', 'none');
				floatRightNoBtn.css('display', 'none');
				floatRightLoading.css('display', '');

				$.ajax({
					url: '{$_SERVER['REQUEST_URI']}',
					data: 'hideAdminAjax=true&btn=' + btn,
					success: function(data) {
		
						data = eval('(' + data + ')');

						if(data.success)
						{
							$('#left_side div.smallfont:last, #sidebar div.smallfont:last').html(data.admins + '<br /><br />[ <a href=\"javascript: void(0);\" class=\"hideadmin-change\">{$hideadmin->lang['changesettings']}</a> ]');

							$('.hideadmin-change').click(function() {
								hideadminpopup.slideDown('slow');
							});

							hideadminpopup.slideUp('slow', function() {

								floatRightYesBtn.css('display', '');
								floatRightNoBtn.css('display', '');
								floatRightLoading.css('display', 'none');
							});
						}
						else
						{
							floatRightYesBtn.css('display', '');
							floatRightNoBtn.css('display', '');
							floatRightLoading.css('display', 'none');

							alert(data.message);
						}
					},
					error: function() {

						floatRightYesBtn.css('display', '');
						floatRightNoBtn.css('display', '');
						floatRightLoading.css('display', 'none');

						alert('{$hideadmin->lang['unknownerror']}');
					}
				});
			}

		</script>";
	}

	return $scripts;
}

function hideadmin_clearData($vars)
{
	require_once(dirname(__FILE__) . '/functions.php');

	$hideadmin = new hideadmin;

	$hideadmin->deleteFlag($vars['adminid']);
}

add_hook('AdminAreaHeadOutput', 0, 'hideadmin_addPopup');
add_hook('AdminLogin', 		0, 'hideadmin_clearData');

?>