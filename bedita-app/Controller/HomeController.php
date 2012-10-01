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
 * BEdita main page
 *
 *
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 *
 * $Id$
 *  */
class HomeController extends AppController {

	var $uses = array('BEObject', 'Tree') ;
	var $helpers 	= array('BeTree');
	var $components = array('BeTree', 'BeUploadToObj');


	 function index() {
	 	$conf  = Configure::getInstance();

	 	$user = $this->Session->read("BEAuthUser");
	 	$lastModBYUser = array();
	 	$lastMod = array();
	 	$excludedObjectTypes = array($conf->objectTypes["editor_note"]["id"], $conf->objectTypes["comment"]["id"]);
	 	if (!empty($conf->objectTypes["questionnaireresult"]["id"]))
	 		$excludedObjectTypes[] = $conf->objectTypes["questionnaireresult"]["id"];

	 	$lastModBYUser = $this->BEObject->find("all", array(
		 								"contain" 		=> array("ObjectType"),
		 								"fields"		=> array("id", "title", "status", "modified", "object_type_id", "ObjectType.module_name"),
		 								"conditions" 	=> array(
		 														"user_modified = '" . $user["id"] . "'",
	 															'NOT' => array('object_type_id' => $excludedObjectTypes)
	 														),
		 								"order"			=> array("modified DESC"),
		 								"limit"			=> 5
	 								)
	 						);

	 	$lastMod = $this->BEObject->find("all", array(
		 								"contain" 		=> array("ObjectType"),
		 								"fields"		=> array("id", "title", "status", "modified", "object_type_id", "ObjectType.module_name"),
		 								"conditions" 	=> array(
	 															'NOT' => array('object_type_id' => $excludedObjectTypes)
	 														),
	 									"order"			=> array("modified DESC"),
		 								"limit"			=> 10
	 								)
	 						);

	 	$filter = array();
	 	$filter["object_type_id"] = $conf->objectTypes['comment']["id"];
		$filter["ref_object_details"] = "Comment";
	 	$lastComments = $this->BEObject->findObjects(null, null, null, $filter, "modified", false, 1, 10);

	 	$filter["object_type_id"] = $conf->objectTypes['editor_note']["id"];
		$filter["ref_object_details"] = "EditorNote";
		$filter["user_created"] = "";
		$lastNotes = $this->BEObject->findObjects(null, null, null, $filter,  "modified", false, 1, 10);

	 	$connectedUser = $this->BeAuth->connectedUser();
	 	$this->set("lastModBYUser", $lastModBYUser);
	 	$this->set("lastMod", $lastMod);
	 	$this->set("lastNotes", $lastNotes["items"]);
	 	$this->set("lastComments", $lastComments["items"]);
	 	$this->set("connectedUser", $connectedUser);
		$this->set("noFooter", true);
		$this->set("bodyClass", "home");
		$this->set('tree',$this->BeTree->getSectionsTree());
	 }

	/**
	 * Generic view methods redirects to specific module controller checking object type
	 *
	 * @param integer $id - object id to view
	 */
	public function view($id) {

		$this->request->action = "index";
		$id = $this->BEObject->objectId($id);
		$typeId = $this->BEObject->findObjectTypeId($id);
		$conf  = Configure::getInstance();
		if(!isset($conf->objectTypes[$typeId]["module_name"])) {
	 		throw new BeditaException(__("No module found for object"));
		}
		$module = $conf->objectTypes[$typeId]["module_name"];
		$this->redirect("/".$module . "/view/" . $id);
	}


	public function search($page=1, $dim=5) {

		$this->layout = null;

		if (!empty($this->request->params["form"]["searchstring"])) {
			$conf  = Configure::getInstance();
			$filter["query"] = addslashes($this->request->params["form"]["searchstring"]);

			$user = $this->Session->read("BEAuthUser");

			$objects = $this->BEObject->findObjects(null, $user["id"], null, $filter, null, true, $page, $dim);
			// get objects module
			foreach ($objects["items"] as $key => $o) {
				$condition = "id=".$o['object_type_id'];
				$objects["items"][$key]["module_name"] = $this->BEObject->ObjectType->field("module_name", $condition);
			}
			$this->set("objects", $objects);
		}
	}

	public function editProfile() {
		if (empty($this->request->data['User']['id']))
	 		throw new BeditaException(__("No user data"));

	 	$oldPwd = trim($this->request->params['form']['oldpwd']);
	 	$pwd = trim($this->request->data['User']['passwd']);
		$confirmPwd = trim($this->request->params['form']['pwd']);

		$userModel = ClassRegistry::init("User");

		if(empty($pwd) && empty($confirmPwd)) {
			unset($this->request->data['User']['passwd']);
		} else {
			$user = $userModel->find("first", array(
		 			"conditions" => array(
		 				"id" => $this->request->data["User"]["id"],
		 				"passwd" => md5($oldPwd)
		 			),
		 			"contain" => array()
		 		)
		 	);

		 	if (!$user)
		 		throw new BeditaException(__("Wrong old user password"));

			if (!$this->BeAuth->checkConfirmPassword($pwd, $confirmPwd))
				throw new BeditaException(__("Passwords mismatch"));
		}

		if (empty($this->request->data["User"]["notify_changes"])) {
			$this->request->data["User"]["notify_changes"] = null;
		}
	 	if (empty($this->request->data["User"]["lang"])) {
			$this->request->data["User"]["lang"] = null;
		}
		if (empty($this->request->params["form"]["comments"])) {
			$this->request->data["User"]["comments"] = "never";
		}
		 if (empty($this->request->params["form"]["notes"])) {
			$this->request->data["User"]["notes"] = "never";
		}
	 	$this->Transaction->begin();
	 	$this->BeAuth->updateUser($this->request->data);
	 	$this->Transaction->commit();
	 	$userModel->containLevel("default");
	 	$user = $userModel->findById($this->request->data["User"]["id"]);
	 	$userModel->compact($user);
	 	$this->Session->write($this->BeAuth->sessionKey, $user);
	 	if (!empty($user["lang"]))
	 		$this->Session->write('Config.language',$user["lang"]);
		$this->eventInfo("user ".$this->request->data['User']['userid']." updated");
		$this->userInfoMessage(__("User updated"));
	}


	protected function forward($action, $esito) {
 	 	$REDIRECT = array(
			"editProfile" => array(
 							"OK"	=> "/home/index",
 							"ERROR"	=> "/home/index"
 						),
			"view" => array(
 							"ERROR"	=> "/home/index"
 						)
 			);
	 	if(isset($REDIRECT[$action][$esito])) return $REDIRECT[$action][$esito] ;
	 	return false;
	 }
}

