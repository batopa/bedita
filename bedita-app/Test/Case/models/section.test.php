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
require_once ROOT . DS . APP_DIR. DS. 'tests'. DS . 'bedita_base.test.php';

class SectionTestCase extends BeditaTestCase {

 	var $uses		= array('Section', 'Tree', 'Area') ;
    var $dataSource	= 'default' ;

	function testActsAs() {
 		$this->checkDuplicateBehavior($this->Section);
 	}

 	function testFeeds() {

 		$conf = Configure::getInstance();
		$tree = $this->Tree->getAll(null, null, null,
			array("object_type_id" => array($conf->objectTypes['area']['id']))) ;

		foreach ($tree as $area) {
			pr("Publication: ". $area['id'] . " - ". $area['title']);
			$result = $this->Section->feedsAvailable($area['id']);
			pr("Available feeds:");
	 		pr($result);
		}

 	}

 	function testMinInsert() {
 		$this->setDefaultDataSource('test') ;
 		echo '<h2>Using database: <b>'. ConnectionManager::getDataSource('test')->config['database'] .'</b></h2>';

		$this->requiredData(array("tree"));
		$result = $this->Area->save($this->data['tree']['area']) ;
		$this->assertEqual($result,true);
		if(!$result) {
			debug($this->Area->validationErrors);
			return ;
		}
		$area_id = $this->Area->id;
		$resultArea = $this->Area->findById($area_id);
		pr("<h4>Area created:</h4>");
		pr($resultArea);

		$this->data['tree']['section']['parent_id'] = $this->Area->id;
		$result = $this->Section->save($this->data['tree']['section']);
		$this->assertEqual($result,true);
		if(!$result) {
			debug($this->Section->validationErrors);
			return ;
		}
		$section_id = $this->Section->id;
		$resultSection = $this->Section->findById($section_id);
		pr("<h4>Section created:</h4>");
		pr($resultSection);

		$this->data['tree']['subsection']['parent_id'] = $section_id;
		$this->Section->create();
		$result = $this->Section->save($this->data['tree']['subsection']);
		$this->assertEqual($result,true);
		if(!$result) {
			debug($this->Section->validationErrors);
			return ;
		}
		$subsection_id = $this->Section->id;
		$resultSubsection = $this->Section->findById($subsection_id);
		pr("<h4>Subsection created:</h4>");
		pr($resultSubsection);

		echo "<hr/>";
		pr("<h4>Publishing tree path:</h4>");
		$result = $this->Tree->findById($area_id);
		$this->assertEqual($result["Tree"]["object_path"], '/'.$area_id);
		pr($result["Tree"]["object_path"]);

		pr("<h4>Section tree path:</h4>");
		$result = $this->Tree->findById($section_id);
		$this->assertEqual($result["Tree"]["object_path"], '/'.$area_id . '/' .$section_id);
		pr($result["Tree"]["object_path"]);

		pr("<h4>Subsection tree path:</h4>");
		$result = $this->Tree->findById($subsection_id);
		$this->assertEqual($result["Tree"]["object_path"], '/'.$area_id . '/' .$section_id . '/' . $subsection_id);
		pr($result["Tree"]["object_path"]);

		echo "<hr/>";

		// remove subsection
		$result = $this->Section->delete($subsection_id);
		$this->assertEqual($result,true);
		pr("Subsection removed");

		//remove section
		$result = $this->Section->delete($section_id);
		$this->assertEqual($result,true);
		pr("Section removed");

		// remove publication
		$result = $this->Area->delete($this->Area->{$this->Area->primaryKey});
		$this->assertEqual($result,true);
		pr("Area removed");
	}

	public function testPromoteSectionToArea() {
		pr("<h4>Building tree structure...</h4>");
		$this->requiredData(array("tree"));
		$result = $this->Area->save($this->data['tree']['area']) ;
		$this->assertEqual($result,true);
		if(!$result) {
			debug($this->Area->validationErrors);
			return ;
		}
		$area_id = $this->Area->id;

		$this->data['tree']['section']['parent_id'] = $this->Area->id;
		$result = $this->Section->save($this->data['tree']['section']);
		$this->assertEqual($result,true);
		if(!$result) {
			debug($this->Section->validationErrors);
			return ;
		}
		$section_id = $this->Section->id;

		$this->data['tree']['subsection']['parent_id'] = $section_id;
		$this->Section->create();
		$result = $this->Section->save($this->data['tree']['subsection']);
		$this->assertEqual($result,true);
		if(!$result) {
			debug($this->Section->validationErrors);
			return ;
		}
		$subsection_id = $this->Section->id;

		// insert children
		pr("<h4>Appending children to sections...</h4>");
		foreach ($this->data['tree']['section']['children'] as $modelName => $d) {
			$model = ClassRegistry::init($modelName);
			$model->create();
			$result = $model->save($d);
			$this->assertEqual($result,true);
			$this->Tree->appendChild($model->id, $section_id);
		}
		foreach ($this->data['tree']['subsection']['children'] as $modelName => $d) {
			$model = ClassRegistry::init($modelName);
			$model->create();
			$result = $model->save($d);
			$this->assertEqual($result,true);
			$this->Tree->appendChild($model->id, $subsection_id);
		}

		$sectionDescendants = $this->Tree->getDescendants($section_id);
		$sectionDescendantsId = Set::extract("/items/id", $sectionDescendants);

		// promote section to publication
		pr("<h4>Promote section " . $this->data['tree']['section']['title'] . " to publication...</h4>");
		$this->Section->promoteToArea($section_id);

		$this->Area->containLevel("detailed");
		$a = $this->Area->find("first", array(
			"conditions" => array("Area.id" => $section_id)
		));
		$this->assertEqual($a["object_type_id"],Configure::read("objectTypes.area.id"));
		pr("<h4>New Publication</h4>");
		pr($a);

		$pubDescendants = $this->Tree->getDescendants($section_id);
		$pubDescendantsId = Set::extract("/items/id", $pubDescendants);

		$this->assertEqual(asort($sectionDescendantsId),asort($pubDescendantsId));
		pr("<h4>New publication's descendants</h4>");
		pr($pubDescendants["items"]);
	}

	public function __construct () {
		parent::__construct('Section', dirname(__FILE__)) ;
	}
}

?>