<?php
/*-----8<--------------------------------------------------------------------
 *
 * BEdita - a semantic content management framework
 *
 * Copyright 2008 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * BEdita is distributed WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * version 3 along with BEdita (see LICENSE.LGPL).
 * If not, see <http://gnu.org/licenses/lgpl-3.0.html>.
 *
 *------------------------------------------------------------------->8-----
 */

/**
 *
 *
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 *
 * $Id$
 */
class FilesController extends AppController {

	var $helpers 	= array('Html');
	var $uses		= array('Stream','BEObject') ;
	var $components = array('Transaction', 'BeUploadToObj', 'RequestHandler');

	function upload () {
		$this->layout = "ajax";
		header("Content-Type: application/json");
		try {
			$data=array();
			if (!empty($this->request->params["form"]["userid"]))
				$data = array("user_created" => $this->request->params["form"]["userid"], "user_modified" => $this->request->params["form"]["userid"]);
			$this->Transaction->begin() ;
			$id = $this->BeUploadToObj->upload($data) ;
			$this->Transaction->commit();
			$response = array("fileId" => $id, "fileUploaded" => true);
			$this->set("response", $id);
		} catch(BeditaException $ex) {
			$errTrace = get_class($ex) . " - " . $ex->getMessage()."\nFile: ".$ex->getFile()." - line: ".$ex->getLine()."\nTrace:\n".$ex->getTraceAsString();
			$this->handleError($ex->getMessage(), $ex->getMessage(), $errTrace);
			$this->setResult(self::ERROR);
			$this->set("response", $ex->getMessage());
		}
	}

	function uploadAjax ($uploadSuffix=null) {
		$this->layout = "ajax";
		try {
			$this->Transaction->begin() ;
			$formUploadFields = 'streamUploaded';
			$formFileName = 'Filedata';
			if (!empty($uploadSuffix)) {
				$formUploadFields .= $uploadSuffix;
				unset($this->request->params["form"]["Filedata"]);
				$formFileName .= $uploadSuffix;
			}
			$this->request->params['form'][$formUploadFields]['lang'] = $this->request->data["lang"];
			$id = $this->BeUploadToObj->upload($this->request->params["form"][$formUploadFields],$formFileName) ;
			$this->Transaction->commit();
			$this->set("fileId", $id);
			$this->set("fileUploaded", true);
		} catch(BeditaException $ex) {
			$errTrace = get_class($ex) . " - " . $ex->getMessage()."\nFile: ".$ex->getFile()." - line: ".$ex->getLine()."\nTrace:\n".$ex->getTraceAsString();
			$this->handleError($ex->getMessage(), $ex->getMessage(), $errTrace);
			$this->setResult(self::ERROR);
			$this->set("errorMsg", $ex->getMessage());
		}
	}

	function uploadAjaxMediaProvider () {
		$this->layout = "ajax";
		header("Content-Type: application/json");
		try {
			if (!isset($this->request->params['form']['uploadByUrl']['url']))
				throw new BEditaException(__("Error during upload: missing url")) ;

			$this->request->params['form']['uploadByUrl']['lang'] = $this->request->data["lang"];

			$this->Transaction->begin() ;
			$id = $this->BeUploadToObj->uploadFromURL($this->request->params['form']['uploadByUrl']) ;
			$this->Transaction->commit();
			$this->set("fileId", $id);

		} catch(BeditaException $ex) {
			$errTrace = get_class($ex) . " - " . $ex->getMessage()."\nFile: ".$ex->getFile()." - line: ".$ex->getLine()."\nTrace:\n".$ex->getTraceAsString();
			$this->handleError($ex->getMessage(), $ex->getMessage(), $errTrace);
			$this->setResult(self::ERROR);
			$this->set("errorMsg", $ex->getMessage());
		}
	}

	/**
	 * Delete a Stream object (using _POST filename to find stream)
	 */
	function deleteFile() {
 		if(!isset($this->request->params['form']['filename'])) throw new BeditaException(sprintf(__("No data"), $id));
	 	$this->Transaction->begin() ;
	 	// Get object id from filename
		if(!($id = $this->Stream->getIdFromFilename($this->request->params['form']['filename']))) throw new BeditaException(sprintf(__("Error getting id object: %s"), $this->request->params['form']['filename']));
	 	// Delete data
	 	if(!$this->BeFileHandler->del($id)) throw new BeditaException(sprintf(__("Error deleting object: %d"), $id));
	 	$this->Transaction->commit() ;
	 	$this->layout = "empty" ;
	}

	protected function initAttributes() {
		// multiple upload
		if ($this->RequestHandler->isFlash()) {
			$this->skipCheck = true;
		}
	}

	/**
	 * Override AppController handleError to not save message in session
	 */
	public function handleError($eventMsg, $userMsg, $errTrace) {
		$this->log($errTrace);
		// end transactions if necessary
		if($this->Transaction->started()) {
			$this->Transaction->rollback();
		}
	}

	protected function forward($action, $esito) {
		$REDIRECT = array(
			"upload" =>	array(
	 			"OK"	=> self::VIEW_FWD.'upload_multi_response',
		 		"ERROR"	=> self::VIEW_FWD.'upload_multi_response'
		 	),
			"uploadAjax" =>	array(
	 			"OK"	=> self::VIEW_FWD.'upload_ajax_response',
		 		"ERROR"	=> self::VIEW_FWD.'upload_ajax_response'
		 	),
		 	"uploadAjaxMediaProvider" => array(
	 			"OK"	=> self::VIEW_FWD.'upload_ajax_response',
		 		"ERROR"	=> self::VIEW_FWD.'upload_ajax_response'
		 	)
       );
       if(isset($REDIRECT[$action][$esito]))
          return $REDIRECT[$action][$esito] ;
       return false;
     }
}
?>