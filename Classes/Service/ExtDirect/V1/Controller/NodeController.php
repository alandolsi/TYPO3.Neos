<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Service\ExtDirect\V1\Controller;

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
 * ExtDirect Controller for managing Nodes
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class NodeController extends \F3\FLOW3\MVC\Controller\ActionController {

	/**
	 * @var string
	 */
	protected $viewObjectNamePattern = 'F3\TYPO3\Service\ExtDirect\V1\View\NodeView';

	/**
	 * Select special error action
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
		$this->errorMethodName = 'extErrorAction';
	}

	/**
	 * Returns the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function showAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$this->view->assignNode($node);
	}

	/**
	 * Returns the primary child node (if any) of the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getPrimaryChildNodeAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$this->view->assignNode($node->getPrimaryChildNode());
	}

	/**
	 * Return child nodes of specified node for usage in a TreeLoader
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node The node to find child nodes for
	 * @param string $contentTypeFilter A content type filter
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getChildNodesForTreeAction(\F3\TYPO3CR\Domain\Model\Node $node, $contentTypeFilter) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \F3\TYPO3\Service\ExtDirect\V1\View\NodeView::TREESTYLE);
	}

	/**
	 * Return child nodes of specified node with all details and
	 * metadata.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @param string $contentTypeFilter
	 * @return string A response string
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function getChildNodesAction(\F3\TYPO3CR\Domain\Model\Node $node, $contentTypeFilter) {
		$this->view->assignChildNodes($node, $contentTypeFilter, \F3\TYPO3\Service\ExtDirect\V1\View\NodeView::LISTSTYLE);
	}

	/**
	 * Creates a new node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $parentNode
	 * @param array $nodeData
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @extdirect
	 */
	public function createAction(\F3\TYPO3CR\Domain\Model\Node $parentNode, array $nodeData) {
		$newNode = $parentNode->createNode($nodeData['nodeName'], $nodeData['contentType']);
		foreach ($nodeData['properties'] as $propertyName => $propertyValue) {
			$newNode->setProperty($propertyName, $propertyValue);
		}

		if ($nodeData['contentType'] === 'TYPO3:Page') {
			$this->createTypeHereTextNode($newNode);
		}

		$nextUri = $this->uriBuilder
			->reset()
			->setFormat('html')
			->setCreateAbsoluteUri(TRUE)
			->uriFor('show', array('node' => $newNode, 'service' => 'REST'), 'Node', 'TYPO3', 'Service\Rest\V1');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Creates a new node as the following sibling of the given node.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $preceedingSibling
	 * @param string $nodeType
	 * @return void
	 * @extdirect
	 */
	public function createFollowingSiblingAction(\F3\TYPO3CR\Domain\Model\Node $preceedingSibling, $contentType) {
		$parentNode = $preceedingSibling->getParent();

		$newNode = $parentNode->createNode(uniqid(), $contentType);
		$newNode->moveAfter($preceedingSibling);

		$nextUri = $this->uriBuilder
			->reset()
			->setFormat('html')
			->setCreateAbsoluteUri(TRUE)
			->uriFor('show', array('node' => $newNode, 'service' => 'REST'), 'Node', 'TYPO3', 'Service\Rest\V1');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}

	/**
	 * Create a section + text node for the new page.
	 *
	 * The section name is currently hardcoded, but should be determined by the currently selected Fluid template
	 * in the future. This whole text-element-creation should also be triggered by the Content Type once we have
	 * support for that.
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $pageNode The page node
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Move section + text node creation to better place (content type triggered)
	 */
	protected function createTypeHereTextNode(\F3\TYPO3CR\Domain\Model\Node $pageNode) {
		$sectionNode = $pageNode->createNode('main', 'TYPO3:Section');
		$textNode = $sectionNode->createNode(uniqid(), 'TYPO3:Text');
		$textNode->setProperty('text', '<em>[ Start typing here ]</em>');
	}

	/**
	 * Updates the specified node
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string View output for the specified node
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 * @todo the updateAction now implicitly saves the node, as the NodeObjectConverter does not clone the node right now. This is a hack, and needs to be cleaned up.
	 */
	public function updateAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$this->view->assign('value', array('data' => '', 'success' => TRUE));
	}

	/**
	 * Deletes the specified node and all of its sub nodes
	 *
	 * @param \F3\TYPO3CR\Domain\Model\Node $node
	 * @return string A response string
	 * @author Robert Lemke <robert@typo3.org>
	 * @extdirect
	 */
	public function deleteAction(\F3\TYPO3CR\Domain\Model\Node $node) {
		$node->remove();
		$nextUri = $this->uriBuilder
			->reset()
			->setFormat('html')
			->setCreateAbsoluteUri(TRUE)
			->uriFor('show', array('node' => $node->getParent(), 'service' => 'REST'), 'Node', 'TYPO3', 'Service\Rest\V1');
		$this->view->assign('value', array('data' => array('nextUri' => $nextUri), 'success' => TRUE));
	}
}
?>