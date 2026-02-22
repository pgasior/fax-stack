<?php
/**
 * AvantFAX - "Web 2.0" HylaFAX management
 *
 * PHP 5 only
 *
 * @author		David Mimms <david@avantfax.com>
 * @copyright	2005 - 2007 MENTALBARCODE Software, LLC
 * @copyright	2007 - 2008 iFAX Solutions, Inc.
 * @license		http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

	require_once '../check_login.php';

	$fid			= array_key_exists('fid', $_REQUEST) ? $_REQUEST['fid'] : NULL;
	$faxarchive		= new FaxPDFArchive;
	$moduser		= new AFUserAccount;
	$processed		= false;
	$error			= NULL;
	
	if (!$faxarchive->load_fax($fid)) { // load the fax ID
		exit;
	}
	
	if (!$_SESSION[USERSESSION]->superuser) {
		if (!$faxarchive->user_has_rights($_SESSION[USERSESSION]->uid, $_SESSION[USERSESSION]->get_modemdevs(), $_SESSION[USERSESSION]->get_didrouting(), $_SESSION[USERSESSION]->get_faxcats())) {
			avantfaxlog("set_note> Access denied to set note to fax '".$faxarchive->get_fid()."' by ".$_SESSION[USERSESSION]->username);
			exit;
		}
	}

	/******************************************************************************************************************************
			SETUP FORM RULES
	 ******************************************************************************************************************************/
	$formdata = new FormRules;
	$formdata->newRule('fid', $fid, FR_NUMBER);
	$formdata->newRule('description',		$faxarchive->get_description());
	$formdata->newRule('category',			$faxarchive->get_faxcatid(),	 FR_NUMBER);
	$formdata->newRule('_submit_check');
    $formdata->newRule('token', null, FR_STRING, null, null, "Token is required", true);

	/******************************************************************************************************************************
			PROCESS FORM
	 ******************************************************************************************************************************/
	if (array_key_exists('_submit_check', $_POST)) {
		$formdata->processForm($_POST);
		
		if ($formerror = $formdata->getFormErrors()) {
			$error = "<li>".join("<li>", $formerror);
		}

        if (!validate_csrf_token($formdata->token)) {
            $error .= "<li>Token mismatch</li>";
        }
		
		if (!$error) {
			$faxarchive->set_note($formdata->description, $formdata->category, $_SESSION[USERSESSION]->get_uid());
			$processed = true;
		}
    } else {
        $formdata->token = setup_csrf_token();
	}
	
	$faxcat			= new FaxPDFCategory;
	$category_list	= array('');
	if ($_SESSION[USERSESSION]->superuser) {
		$categories	= $faxcat->get_categories();
		if (is_array($categories)) {
			foreach ($categories as $cat) {
				$category_list[$cat['catid']] = $cat['name'];
			}
		}
	} else {
		$categories	= $_SESSION[USERSESSION]->get_faxcats();
		if (is_array($categories)) {
			foreach ($categories as $cat) {
				if ($catname = $faxcat->get_name($cat)) {
					$category_list[$cat] = $catname;
				}
			}
		}
	}
	
	/******************************************************************************************************************************
			SHOW TEMPLATE
	 ******************************************************************************************************************************/
	$usmarty = new UserSmarty;
	$usmarty->assign('processed',			$processed);
	$usmarty->assign('categories',			$category_list);
	$usmarty->assign('error',				$error);
	$usmarty->assign('description',			html_entity_decode($formdata->description, ENT_QUOTES, "UTF-8"));
	$usmarty->assign('fvalues',				$formdata->htmlReady());
	
	if ($moduser->load($faxarchive->get_lastmoduser())) {
		$usmarty->assign('modusername',		$moduser->name);
		$usmarty->assign('modlastmod',		$faxarchive->get_lastmoddate());
	}
	display_template('set_note.tpl',		$usmarty);
