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

/**
 * CLASS: AFUserPasswords
	METHODS:
		public function __construct()
        public function log_password($pwd, $uid)
        public function password_used($pwd, $uid)
        public function clear_hashes($uid)
*/

class AFUserPasswords
{
	protected	$upid,
				$uid,
				$pwdhash;
	
	private		$userpasswords;
	
	/**
	 * construct
	 *
	 * @return void
	 * @access public
	 */
	public function __construct() {
		$this->userpasswords = new MDBOData('UserPasswords');
	}
	
	/**
	 * log_password
	 *
     * @param string $passwordHash
     * @param int $uid
	 * @return bool
	 * @access public
	 */
	public function log_password($passwordHash, $uid) {
		return $this->userpasswords->new_entry(array('uid' => $uid, 'pwdhash' => $passwordHash));
	}
	
	/**
	 * password_used
	 *
     * @param string $password
     * @param int $uid
	 * @return array
	 * @access public
	 */
	public function password_used($password, $uid) {
        if ($pwds = $this->userpasswords->query("SELECT pwdhash FROM UserPasswords WHERE uid = ".$this->userpasswords->quote($uid), false)) {
            foreach ($pwds as $pwd) {
                if (verifyPassword($password, $pwd['pwdhash'])) {
                    return true;
                }
            }
        }

        return false;
	}
	
	/**
	 * clear_hashes
	 *
     * @param int $uid
	 * @return array|false|null
	 * @access public
	 */
	public function clear_hashes($uid) {
		return $this->userpasswords->query("DELETE FROM UserPasswords WHERE uid = ".$this->userpasswords->quote($uid));
	}	
}
