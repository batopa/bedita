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
 * Controller module Publications: managing of publications, sections and sessions
 *
 *
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 *
 * $Id$
 */
class AreasController extends ModulesController {
	var $name = 'Areas';

	var $helpers 	= array('BeTree', 'BeToolbar');
	var $components = array('BeTree', 'BeCustomProperty', 'BeLangText', 'BeUploadToObj', 'BeFileHandler');

	var $uses = array('BEObject', 'Area', 'Section', 'Tree', 'User', 'Group', 'ObjectType') ;
	protected $moduleName = 'areas';

	function index($id = null, $order = "priority", $dir = true, $page = 1, $dim = 20) {
		if ($id == null && !empty($this->request->params["named"]["id"])) {
			$id = $this->request->params["named"]["id"];
		}
		// if empty $id try to get first publication.id
		if (empty($id)) {
			$publication = $this->Area->find("first", array(
				"order" => "BEObject.title asc",
				"contain" => array("BEObject")
			));
			$this->request->params["named"]["id"] = $id = $publication["id"];
		}

		if (!empty($id)) {
			$this->view($id);
		}

	}

	public function view($id) {
		$this->request->action = "index";
		$objectTypeId = $this->BEObject->field("object_type_id", array("BEObject.id" => $id));
		$modelName = Configure::read("objectTypes.".$objectTypeId.".model");
		if(empty($modelName)) {
			throw new BeditaException(sprintf(__("Object id not found: %d"), $id));
		}
		$this->viewObject($this->{$modelName}, $id);
		$dir = ($this->viewVars["object"]["priority_order"] == "asc")? true : false;
		$this->loadChildren($id, "priority", $dir);
		$this->set("objectType", Configure::read("objectTypes.".$objectTypeId.".name"));
		$this->set('parent_id', $this->Tree->getParent($id));
	}

	/**
	 * load paginated contents and no paginated sections of $id publication/section
	 *
	 * @param int $id
	 * @param string $order
	 * @param bool $dir
	 * @param int $page
	 * @param int $dim
	 */
	protected function loadChildren($id, $order = "priority", $dir = true, $page = 1, $dim = 20) {
		// get paginated children content (leaf objectTypes) if no other is passed
		if (!empty($this->request->params["named"]["object_type_id"])
				&& $this->request->params["named"]["object_type_id"] != Configure::read("objectTypes.area.id")
				&& $this->request->params["named"]["object_type_id"] != Configure::read("objectTypes.section.id")) {
			$filter["object_type_id"] = $this->request->params["named"]["object_type_id"];
		} else {
			$filter["object_type_id"] = Configure::read("objectTypes.leafs.id");
		}
		$filter["count_annotation"] = array("EditorNote");
		$dir = ($this->viewVars["object"]["priority_order"] == "asc")? true : false;
		$this->paginatedList($id, $filter, $order, $dir, $page, $dim);

		// get no paginated children sections
		$filter["object_type_id"] = Configure::read("objectTypes.section.id");
		$filter["count_permission"] = true;
		$sections = $this->BeTree->getChildren($id, null, $filter, "priority", $dir);
		$this->set("sections", $sections["items"]);
	}

	function viewArea($id = null) {
		// Get selected area
		$area = null ;
		if($id) {
			$this->Area->containLevel("detailed");
			if(!($area = $this->Area->findById($id))) {
				 throw new BeditaException(sprintf(__("Error loading area: %d"), $id));
			}
		}

		$property = $this->BeCustomProperty->setupForView($area, Configure::read("objectTypes.area.id"));

		// Data for template
		$this->set('area',$area);
		$this->set('objectProperty', $property);
		// get users and groups list
		$this->User->displayField = 'userid';
		$this->set("usersList", $this->User->find('list', array("order" => "userid")));
		$this->set("groupsList", $this->Group->find('list', array("order" => "name")));
	}

	function viewSection() {
		$sec = null;
		$this->set('objectProperty', $this->BeCustomProperty->setupForView($sec, Configure::read("objectTypes.section.id"))) ;
		$this->set('tree',$this->BeTree->getSectionsTree());
	}


	 /**
	  * Add or modify area
	  */
	function saveArea() {
		$this->checkWriteModulePermission();
		$new = (empty($this->request->data['id'])) ? true : false;
		$this->Transaction->begin();
		if (empty($this->request->data["syndicate"])) {
			$this->request->data["syndicate"] = 'off';
		}
		$this->saveObject($this->Area);

		$id = $this->Area->id;
		if(!$new) {

			// remove children
			if (!empty($this->request->params["form"]["contentsToRemove"])) {
				$childrenToRemove = explode(",", trim($this->request->params["form"]["contentsToRemove"],","));
				foreach ($childrenToRemove as $idToRemove) {
					$this->Tree->removeChild($idToRemove, $id);
				}
			}

			$reorder = (!empty($this->request->params["form"]['reorder'])) ? $this->request->params["form"]['reorder'] : array();

			// add new children and reorder priority
			foreach ($reorder as $r) {
			 	if (!$this->Tree->find("first", array("conditions" => "id=".$r["id"]." AND parent_id=".$id))) {
					$this->Tree->appendChild($r["id"], $id);
				}
				if (!$this->Tree->setPriority($r['id'], $r['priority'], $id)) {
					throw new BeditaException( __("Error during reorder children priority"), $r["id"]);
				}
			}
		}

	 	$this->Transaction->commit() ;
 		$this->userInfoMessage(__("Area saved")." - ".$this->request->data["title"]);
		$this->eventInfo("area ". $this->request->data["title"]."saved");
	}

	/**
	 * Save/modify section.
	 */
	function saveSection() {

		$this->checkWriteModulePermission();
		$new = (empty($this->request->data['id'])) ? true : false;
		$this->Transaction->begin();
		if (empty($this->request->data["syndicate"])) {
			$this->request->data["syndicate"] = 'off';
		}
		if(empty($this->request->data["parent_id"])) {
			throw new BeditaException( __("Missing parent"));
		}

		$this->saveObject($this->Section);
		$id = $this->Section->id;

		// Move section in the right tree position, if necessary
		if(!$new) {

			if (!$this->BEObject->isFixed($id)) {
				$oldParent = $this->Tree->getParent($id);
				if($oldParent != $this->request->data["parent_id"]) {
					if(!$this->Tree->move($this->request->data["parent_id"], $oldParent, $id)) {
						throw new BeditaException( __("Error moving section in the tree"));
					}
				}
			}

			// save Tree.menu
			$menu = (!empty($this->request->data['menu']))? 1 : 0;
			$this->Tree->saveMenuVisibility($id, $this->request->data["parent_id"], $menu);

			// remove children
			if (!empty($this->request->params["form"]["contentsToRemove"])) {
				$childrenToRemove = explode(",", trim($this->request->params["form"]["contentsToRemove"],","));
				foreach ($childrenToRemove as $idToRemove) {
					$this->Tree->removeChild($idToRemove, $id);
				}
			}

			$reorder = (!empty($this->request->params["form"]['reorder'])) ? $this->request->params["form"]['reorder'] : array();

			// add new children and reorder priority
			foreach ($reorder as $r) {
			 	if (!$this->Tree->find("first", array("conditions" => "id=".$r["id"]." AND parent_id=".$id))) {
					$this->Tree->appendChild($r["id"], $id);
				}
				if (!$this->Tree->setPriority($r['id'], $r['priority'], $id)) {
					throw new BeditaException( __("Error during reorder children priority"), $r["id"]);
				}
			}
		}

	 	$this->Transaction->commit() ;
		$this->userInfoMessage(__("Section saved")." - ".$this->request->data["title"]);
		$this->eventInfo("section [". $this->request->data["title"]."] saved");
	}

	function delete() {
		if(empty($this->request->data['id'])) {
			throw new BeditaException(__("No data"));
		}
		$ot_id = $this->BEObject->field("object_type_id", array("BEObject.id" => $this->request->data['id']));
		switch ($ot_id) {
			case Configure::read("objectTypes.area.id"):
				$this->deleteArea();
				break;

			case Configure::read("objectTypes.section.id"):
				$this->deleteSection();
				break;
		}
	}

	/**
	 * Export section objects to a specific file format
	 */
	public function export() {
		$this->autoRender = false;
		$modelType = $this->BEObject->getType($this->request->data["id"]);
		$this->viewObject($this->{$modelType}, $this->request->data["id"]);
		if(empty($this->request->data["type"])) {
			throw new BeditaException(__("No valid export filter has been selected"));
		}
		
		$filterClass = Configure::read("filters.export." . $this->request->data["type"]);
		$filterModel = ClassRegistry::init($filterClass);
		$objects = array($this->viewVars["object"]);
		$result = $filterModel->export($objects);

		Configure::write('debug', 0);
		// TODO: optimizations!!! use cake tools
		header('Content-Description: File Transfer');
		header("Content-type: " . $result["contentType"]);
		header('Content-Disposition: attachment; filename='.$this->request->data["filename"]);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . $result["size"]);
		ob_clean();
   		flush();
		echo $result["content"];
		exit();
	}

	/**
	 * Import objects from file in current section
	 */
	public function import() {
		$this->checkWriteModulePermission();
		$this->Transaction->begin();
		if (!empty($this->request->params['form']['Filedata']['name'])) {
			unset($this->request->data['url']);
			$this->request->params['form']['forceupload'] = true;
			$streamId = $this->BeUploadToObj->upload($this->request->data) ;
		} elseif (!empty($this->request->data['url'])) {
			$streamId = $this->BeUploadToObj->uploadFromURL($this->request->data) ;
		}
		$stream = ClassRegistry::init("Stream");
		$path = $stream->field("uri", array("id" => $streamId));

		if($this->request->data["type"] !== "auto") {
			$filterClass = Configure::read("filters.import." . $this->request->data["type"]);
		} else { // search matching mime types
			$mimeType = $stream->field("mime_type", array("id" => $streamId));
			$filterClass = Configure::read("filters.mime." . $mimeType . ".import");
		}

		$this->Section->id = $this->request->data['sectionId'];
		if(!empty($filterClass)) {
			$filterModel = ClassRegistry::init($filterClass);
			$options = array("sectionId" => $this->request->data['sectionId']);
			$result = $filterModel->import(Configure::read("mediaRoot") . $path, $options);
			$this->userInfoMessage(__("Objects imported").": ". $result["objects"]);
			$this->eventInfo($result["objects"] . " objects imported in section " . $this->Section->id . " from " . $path);
		} else {
			$this->userErrorMessage(__("No import filter found for file type"). " : ". $mimeType);
			$this->eventError("Import filter not found for type " . $mimeType);

		}
		if(!$this->BeFileHandler->del($streamId)) {
			throw new BeditaException(__("Error deleting object: ") . $streamId);
		}
	 	$this->Transaction->commit() ;
	}



	private function deleteArea() {
		$this->checkWriteModulePermission();
		$objectsListDeleted = $this->deleteObjects("Area");
		$this->userInfoMessage(__("Area deleted")." - ".$objectsListDeleted);
		$this->eventInfo("area [". $objectsListDeleted."] deleted");
	}

	private function deleteSection() {
		$this->checkWriteModulePermission();
		$objectsListDeleted = $this->deleteObjects("Section");
		$this->userInfoMessage(__("Section deleted")." - ".$objectsListDeleted);
		$this->eventInfo("section [". $objectsListDeleted."] deleted");
	}

	 /**
	  * Return associative array representing publications/sections tree
	  *
	  * @param unknown_type $data
	  * @param unknown_type $tree
	  */
	private function _getTreeFromPOST(&$data, &$tree) {
		$tree = array() ;
		$IDs  = array() ;
		// Creating subtrees
		$arr = preg_split("/;/", $data) ;
		for($i = 0 ; $i < count($arr) ; $i++) {
			$item = array() ;
			$tmp = split(" ", $arr[$i] ) ;
			foreach($tmp as $val) {
				$t  = split("=", $val) ;
				$item[$t[0]] = ($t[1] == "null") ? null : ((integer)$t[1]) ;
			}
			$IDs[$item["id"]] 				= $item ;
			$IDs[$item["id"]]["children"] 	= array() ;
		}
		// Creating the tree
		foreach ($IDs as $id => $item) {
			if(!isset($item["parent"])) {
				$tree[] = $item ;
				$IDs[$id] = &$tree[count($tree)-1] ;
			}
			if(isset($IDs[$item["parent"]])) {
				$IDs[$item["parent"]]["children"][] = $item ;
				$IDs[$id] = &$IDs[$item["parent"]]["children"][count($IDs[$item["parent"]]["children"])-1] ;
			}
		}
		unset($IDs) ;
	}


	protected function forward($action, $esito) {
		$REDIRECT = array(
			"saveArea"	=> 	array(
									"OK"	=> "/areas/view/{$this->Area->id}",
									"ERROR"	=> $this->referer()
								),
			"saveSection"	=> 	array(
									"OK"	=> "/areas/view/{$this->Section->id}",
									"ERROR"	=> $this->referer()
								),
			"delete"	=> 	array(
									"OK"	=> "./",
									"ERROR"	=> "/areas/view/" . @$this->request->data["id"]
								),
			"deleteSection"	=> 	array(
									"OK"	=> "./",
									"ERROR"	=> $this->referer()
								),
			"import"	=> 	array(
									"OK"	=> "/areas/view/{$this->Section->id}",
									"ERROR"	=> $this->referer()
								),
		) ;
		if(isset($REDIRECT[$action][$esito])) return $REDIRECT[$action][$esito] ;
		return false ;
	}
}

?>