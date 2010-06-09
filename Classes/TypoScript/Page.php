<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A TypoScript Page object
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class Page extends \F3\TypoScript\AbstractContentObject {

	/**
	 * @var \F3\TYPO3\Domain\Model\Content\Page
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $modelType = 'F3\TYPO3\Domain\Model\Content\Page';

	/**
	 * @var string
	 */
	protected $templateSource = 'resource://TYPO3/Private/TypoScript/Templates/Page.html';

	/**
	 * Names of the properties of this TypoScript which should be available in
	 * this TS object's template while rendering it.
	 *
	 * @var array
	 */
	protected $presentationModelPropertyNames = array('title', 'head', 'body', 'sections', 'identity');

	/**
	 * The type is used to distinguish between different TypoScript Page objects.
	 * This property won't be rendered nor does it exist in the Page Domain Model.
	 *
	 * @var string
	 */
	protected $type = 'default';

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var mixed
	 */
	protected $body;

	/**
	 * @var array
	 */
	protected $head = array();

	/**
	 * @transient
	 * @var \F3\TYPO3\TypoScript\Sections
	 */
	protected $sections;

	/**
	 * Sets the type of this page.
	 *
	 * @param string $type
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * Returns the type of this page.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Overrides the title of this page.
	 *
	 * @param string $title
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the overriden title of this page.
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets head content of this page.
	 *
	 * @param array $head
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHead(array $head) {
		$this->head = $head;
	}

	/**
	 * Gets head content of this page.
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getHead() {
		return $this->head;
	}

	/**
	 * Explicitly sets the body content of this page.
	 *
	 * @param mixed $body Either a plain string or a TypoScript Content Object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * Returns the explicitly set body content of this page (if any).
	 *
	 * Either a plain string or a TypoScript Content Object
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * Returns the sections used on this page.
	 *
	 * @return array An array of TypoScript Objectes, indexed by section names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSections() {
		if ($this->sections === NULL) {
			$this->sections = $this->typoScriptObjectFactory->createByName('Sections');
		}
		$this->sections->setModel($this->model);
     	return $this->sections;
  	}

	public function getIdentity() {
		return $this->model->FLOW3_Persistence_Entity_UUID;
	}
}
?>