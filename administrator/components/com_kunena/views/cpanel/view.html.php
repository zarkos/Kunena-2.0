<?php
/**
 * Kunena Component
 * @package Kunena.Administrator
 * @subpackage Views
 *
 * @copyright (C) 2008 - 2011 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

kimport ( 'kunena.view' );

/**
 * About view for Kunena cpanel
 */
class KunenaAdminViewCpanel extends KunenaView {
	function displayDefault() {
		JToolBarHelper::title ( '&nbsp;', 'kunena.png' );
		$this->config = KunenaFactory::getConfig ();
		$this->versioncheck = $this->get('latestversion');

		$this->display ();
	}
}
