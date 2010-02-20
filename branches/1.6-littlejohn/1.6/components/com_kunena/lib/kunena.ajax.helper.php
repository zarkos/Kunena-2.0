<?php
/**
 * @version $Id$
 * Kunena Component - CKunenaAjaxHelper class
 * @package Kunena
 *
 * @Copyright (C) 2010 www.kunena.com All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.com
 **/

// Dont allow direct linking
defined ( '_JEXEC' ) or die ();

require_once (KUNENA_PATH_LIB . DS . 'kunena.session.class.php');

/**
 * @author fxstein
 *
 */
class CKunenaAjaxHelper {
	/**
	 * @var JDatabase
	 */
	protected $_db;
	protected $_my;
	protected $_session;

	function __construct() {
		$this->_db = &JFactory::getDBO ();
		$this->_my = &JFactory::getUser ();
		$this->_session = & CKunenaSession::getInstance ();
	}

	function &getInstance() {
		static $instance = NULL;

		if (! $instance) {
			$instance = new CKunenaAjaxHelper ( );
		}
		return $instance;
	}

	public function generateJsonResponse($action, $do, $data) {
		$response = '';

		if(JDEBUG == 1 && defined('JFIREPHP')){
			FB::log("Kunena JSON action: ".$action);
		}

		if ($this->_my->id) {
			// We only entertain json requests for registered and logged in users

			switch ($action) {
				case 'autocomplete' :
					$response = $this->_getAutoComplete ( $do, $data );

					break;
				case 'preview' :
					$body = JRequest::getVar ( 'body', '' );

					$response = $this->_getPreview ( $body );

					break;
				case 'pollcatsallowed' :

					$response = $this->_getPollsCatsAllowed ();

					break;
				case 'pollvote' :
					$vote	= JRequest::getInt('kpollradio', '');
					$id = JRequest::getInt ( 'kpoll_id', 0 );

					$response = $this->_addPollVote ($vote, $id, $this->_my->id);

					break;
				case 'pollchangevote' :
					$vote	= JRequest::getInt('kpollradio', '');
					$id = JRequest::getInt ( 'kpoll_id', 0 );

					$response = $this->_changePollVote ($vote, $id, $this->_my->id);

					break;
				case 'uploadfile' :

					$response = $this->_uploadFile ($do);

					break;
				default :

					break;
			}
		}
		else {
			$response = array(
				'status' => '0',
				'error' => @sprintf(_KUNENA_AJAX_PERMISSION_DENIED)
			);
		}
		// Output the JSON data.
		return json_encode ( $response );
	}

	// JSON helpers
	protected function _getAutoComplete($do, $data) {
		$result = array ();

		// only registered users when the board is online should endup here

		// Verify permissions
		if ($this->_session->allowed && $this->_session->allowed != 'na') {
			$allowed = "c.id IN ({$this->_session->allowed})";
		} else {
			$allowed = "c.published='1' AND c.pub_access='0'";
		}

		// When we query for topics or categories we have to check against permissions

		switch ($do) {
			case 'getcat' :
				$query = "SELECT c.name, c.id
							FROM #__fb_categories AS c
							WHERE $allowed AND name LIKE '" . $data . "%'
							ORDER BY 1 LIMIT 0, 10;";

				$this->_db->setQuery ( $query );
				$result = $this->_db->loadResultArray ();
				check_dberror ( "Unable to lookup categories by name." );

				break;
			case 'gettopic' :
				$query = "SELECT m.subject
							FROM #__fb_messages AS m
							JOIN #__fb_categories AS c ON m.catid = c.id
							WHERE m.hold=0 AND m.parent=0 AND $allowed
								AND m.subject LIKE '" . $data . "%'
							ORDER BY 1 LIMIT 0, 10;";

				$this->_db->setQuery ( $query );
				$result = $this->_db->loadResultArray ();
				check_dberror ( "Unable to lookup topics by subject." );

				break;
			case 'getuser' :
				$kunena_config = &CKunenaConfig::getInstance ();

				// User the configured display name
				$queryname = $kunena_config->username ? 'username' : 'name';
				// Exclude the main superadmin from the search for security purposes
				$query = "SELECT `$queryname` FROM #__users WHERE block=0 AND `id` != 62 AND `$queryname`
							LIKE '" . $data . "%' ORDER BY 1 LIMIT 0, 10;";

				$this->_db->setQuery ( $query );
				$result = $this->_db->loadResultArray ();
				check_dberror ( "Unable to lookup users by $queryname." );

				break;
			default :
			// Operation not supported


		}

		return $result;
	}

	protected function _getPreview($data) {
		$result = array ();

		$config = & CKunenaConfig::getInstance ();

		require_once(JPATH_ROOT  .DS . '/libraries/joomla/document/html/html.php');

		$message = utf8_urldecode ( utf8_decode ( stripslashes ( $data ) ) );

		$kunena_emoticons = smile::getEmoticons ( 0 );
		$msgbody = smile::smileReplace ( $message, 0, $config->disemoticons, $kunena_emoticons );
		$msgbody = nl2br ( $msgbody );
		$msgbody = str_replace ( "__FBTAB__", "\t", $msgbody );
		$msgbody = CKunenaTools::prepareContent ( $msgbody );

		$result ['preview'] = $msgbody;

		return $result;
	}

	protected function _getPollsCatsAllowed () {
		$result = array ();

		$query = "SELECT id
							FROM #__fb_categories
							WHERE allow_polls=1;";
		$this->_db->setQuery ( $query );
		$allow_polls = $this->_db->loadResultArray ();
		check_dberror ( "Unable to lookup categories by name." );
		$result['status'] = '1';
		$result['allowed_polls'] = $allow_polls;

		return $result;
	}

	protected function _addPollVote ($value_choosed, $id, $userid) {
		$result = array ();

		require_once (KUNENA_PATH_LIB .DS. 'kunena.poll.class.php');
		$poll = new CKunenaPolls();
		$result = $poll->save_results($id,$userid,$value_choosed);

		return $result;
	}

	protected function _changePollVote ($value_choosed, $id, $userid) {
		$result = array ();

		require_once (KUNENA_PATH_LIB .DS. 'kunena.poll.class.php');
		$poll = new CKunenaPolls();
		$result = $poll->save_changevote($id,$userid,$value_choosed);

		return $result;
	}

	protected function _uploadFile ($do) {
		require_once (KUNENA_PATH_LIB .DS. 'kunena.upload.class.php');
		$upload = new CKunenaUpload();
		$upload->uploadFile();
		return $upload->fileInfo();
	}

}