<?php
/**
 * Kunena Component
 * @package Kunena.Framework
 * @subpackage Integration.None
 *
 * @copyright (C) 2008 - 2011 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

class KunenaProfileNone extends KunenaProfile
{
	public function __construct() {
		$this->priority = 5;
	}

	public function getUserListURL($action='', $xhtml = true) {}

	public function getProfileURL($user, $task='', $xhtml = true) {}

	public function showProfile($userid, &$msg_params) {}
}
