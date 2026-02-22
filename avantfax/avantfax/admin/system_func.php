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

	require_once 'check_login.php';
	
	$reboot		= false;
	$shutdown	= false;
	$error		= NULL;
	
	/******************************************************************************************************************************
			SETUP FORM RULES
	 ******************************************************************************************************************************/
	$formdata = new FormRules;
	$formdata->newRule ('reboot');
	$formdata->newRule ('shutdown');
	$formdata->newRule ('download_ar');
	$formdata->newRule ('download_db');
	$formdata->newRule ('_submit_check');
    $formdata->newRule('token', null, FR_STRING, null, null, "Token is required", true);

	/******************************************************************************************************************************
			PROCESS FORM
	 ******************************************************************************************************************************/
	if (array_key_exists ('_submit_check', $_POST)) {
		$formdata->processForm ($_POST);
		
		if ($formerror = $formdata->getFormErrors()) {
			$error = "<li>".join ("<li>", $formerror);
		}

        if (!validate_csrf_token($formdata->token)) {
            $error .= "<li>Token mismatch</li>";
        }
	
		if (!$error) {
            $randstr = bin2hex(openssl_random_pseudo_bytes(16));

			if ($formdata->reboot) {
				system ("sudo /sbin/reboot");
				$reboot = true;
			} elseif ($formdata->shutdown) {
				system ("sudo /sbin/halt");
				$shutdown = true;
			} elseif ($formdata->download_ar) {
				// download fax archive
				$basname = "avantfax-archive-".date("Ymd-").$randstr.".tar.gz";
				$tmpfile = $TMPDIR.$basname;
				system ("tar -czf $tmpfile $ARCHIVE $ARCHIVE_SENT");
				header ("Location: ../tmp/$basname");
				exit;
			} elseif ($formdata->download_db) {
				// download fax database dump
				$basname = "avantfax-schema-".date("Ymd-").$randstr.".sql";
				$tmpfile = $TMPDIR.$basname;
                $cmd = "mysqldump --user=".escapeshellarg(AFDB_USER).
                    " --password=".escapeshellarg(AFDB_PASS).
                    " --result-file=".escapeshellarg($tmpfile).
                    " ".escapeshellarg(AFDB_NAME). " 2>&1";
                exec($cmd, $output, $rc);

                if ($rc === 0) {
                    exec("gzip -9 $tmpfile 2>&1", $output, $rc);
                    if ($rc === 0) {
                        header("Location: ../tmp/$basname.gz");
                    } else {
                        echo "<pre>";
                        echo "There was an error generating the compressed mysql dump file: (Exit code: $rc)\n";
                        echo join("\n", $output);
                        echo "</pre>";
                    }
                } else {
                    echo "<pre>";
                    echo "There was an error generating the mysql dump file: (Exit code: $rc)\n";
                    echo join("\n", $output);
                    echo "</pre>";
                }
				exit;
			}
		}
    } else {
        $formdata->token = setup_csrf_token();
	}
	
	/******************************************************************************************************************************
			SHOW TEMPLATE
	 ******************************************************************************************************************************/
	$asmarty = new AdminSmarty;
	$asmarty->assign ('shutdown',			$shutdown);
	$asmarty->assign ('reboot',				$reboot);
    $asmarty->assign('fvalues',	           	$formdata->htmlReady());
	display_template ('system_func.tpl',	$asmarty);
