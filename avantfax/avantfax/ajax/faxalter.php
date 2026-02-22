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
	
	$jid			= array_key_exists('jid',	$_REQUEST) ? $_REQUEST['jid']	: NULL;
	$resubmit		= array_key_exists('r',		$_REQUEST) ? $_REQUEST['r']		: NULL;
	$owner			= array_key_exists('owner',	$_REQUEST) ? $_REQUEST['owner']	: $_SESSION[USERSESSION]->username;
	$modeminst		= new FaxModem;
	$fq				= new FaxQueue;
	$processed		= false;
	$error			= NULL;
	
	$modems			= ($_SESSION[USERSESSION]->superuser) ? $modeminst->get_modems() : $_SESSION[USERSESSION]->get_modemdevs();
	$modem_list		= array("");
	
	if (is_array($modems)) {
		foreach ($modems as $device) {
			if ($modeminst->load_device($device)) {
				$modem_list[$device] = $modeminst->get_alias();
			}
		}
	}

	$priority_list = array('127' => '');
	for ($i = 0; $i < 255; $i+=10) $priority_list["$i"] = $i;
	
	/******************************************************************************************************************************
			SETUP FORM RULES
	 ******************************************************************************************************************************/
	$formdata = new FormRules;
	$formdata->newRule('jid', $jid, FR_NUMBER);
	$formdata->newRule('destination', NULL, FR_STRING, 0, 20);
	$formdata->newRule('modem', NULL, FR_STRING, 0, 10);
	$formdata->newRule('priority', NULL, FR_NUMBER, 50, 250);
	$formdata->newRule('sendtime', NULL, FR_NUMBER, 0, 1); // , ($resubmit) ? 1 : NULL, FR_NUMBER
	$formdata->newRule('sendtimeHour', NULL, FR_STRING, 0, 2);
	$formdata->newRule('sendtimeMin', NULL, FR_STRING, 0, 2);
	$formdata->newRule('sendtime_unit', ($resubmit) ? 'minutes' : 'hours', FR_STRING, 0, 7);
	$formdata->newRule('sendnow', NULL, FR_NUMBER, 0, 1);
	$formdata->newRule('killtime', ($resubmit) ? 3 : NULL, FR_NUMBER, 0, 99);
	$formdata->newRule('killtime_unit', 'hours', FR_STRING, 0, 7);
	$formdata->newRule('numtries', NULL, FR_NUMBER, 0, 9);
	$formdata->newRule('resubmit', $resubmit, FR_NUMBER, 0, 1);
	$formdata->newRule('owner', $owner, FR_STRING, 0, 150);
	$formdata->newRule('_submit_check', NULL, FR_NUMBER, 0, 1);
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
			// array('sendtime' => NULL, 'destination' => NULL, 'killtime' => NULL, 'device' => NULL, 'priority' => NULL, 'tries' => NULL);
			$operations = array();
			
			if ($formdata->destination)			$operations['destination']	= $formdata->destination;
			if ($formdata->numtries)			$operations['tries']		= $formdata->numtries;
			if ($formdata->modem)				$operations['device']		= $formdata->modem;
			if ($formdata->priority != '127')		$operations['priority']		= $formdata->priority;
			if ($formdata->sendtime)			$operations['sendtime']		= ($formdata->sendtime && $formdata->sendtimeHour && $formdata->sendtimeMin) ? $formdata->sendtimeHour.":".$formdata->sendtimeMin : NULL;
			if ($formdata->killtime)			$operations['killtime']		= "now + ".$formdata->killtime." ".$formdata->killtime_unit;
			if ($formdata->sendnow)				$operations['sendtime']		= "now";
			if ($formdata->resubmit)			$operations['resubmit']		= true;
			
			$fq->faxalter($formdata->owner, $jid, $operations);
			exit;
		}
    } else {
        $formdata->token = setup_csrf_token();
	}

	$units = array('minutes' => $LANG['MINUTES'], 'hours' => $LANG['HOURS'], 'days' => $LANG['DAYS']);
	$unitHours = array();
	$unitMins = array();
	
	for ($i = 0; $i < 24; $i++) {
		$cnt = $i;
		
		if ($cnt < 10) {
			$cnt = "0$cnt";
		}
		
		$unitHours["$cnt"] = "$cnt";
	}
	
	for ($i = 0; $i < 60; $i++) {
		$cnt = $i;
		
		if ($cnt < 10) {
			$cnt = "0$cnt";
		}
		
		$unitMins["$cnt"] = "$cnt";
	}
	
	/******************************************************************************************************************************
			SHOW TEMPLATE
	 ******************************************************************************************************************************/
	$usmarty = new UserSmarty;
	$usmarty->assign('fvalues',			$formdata->htmlReady());
	$usmarty->assign('modem_list',		$modem_list);
	$usmarty->assign('priority_list',	$priority_list);
	$usmarty->assign('units',			$units);
	$usmarty->assign('unitHours',		$unitHours);
	$usmarty->assign('unitMins',		$unitMins);
	display_template('faxalter.tpl',	$usmarty);
