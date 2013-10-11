<?php
namespace TYPO3\Neos\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * A TypoScript Menu object
 *
 * @Flow\Scope("prototype")
 */
class MenuImplementation extends \TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation {

	/**
	 * Hard limit for the maximum number of levels supported by this menu
	 */
	const MAXIMUM_LEVELS_LIMIT = 100;

	const STATE_NORMAL = 'normal';
	const STATE_CURRENT = 'current';
	const STATE_ACTIVE = 'active';

	/**
	 * @var string
	 */
	protected $templatePath = 'resource://TYPO3.Neos/Private/Templates/TypoScriptObjects/Menu.html';

	/**
	 * The first navigation level which should be rendered.
	 *
	 * 1 = first level of the site
	 * 2 = second level of the site
	 * ...
	 * 0  = same level as the current page
	 * -1 = one level above the current page
	 * -2 = two levels above the current page
	 * ...
	 *
	 * @var integer
	 */
	protected $entryLevel = 1;

	/**
	 * The last navigation level which should be rendered.
	 *
	 * 1 = first level of the site
	 * 2 = second level of the site
	 * ...
	 * 0  = same level as the current page
	 * -1 = one level above the current page
	 * -2 = two levels above the current page
	 * ...
	 *
	 * @var integer
	 */
	protected $lastLevel;

	/**
	 * Maximum number of levels which should be rendered in this menu.
	 *
	 * @var integer
	 */
	protected $maximumLevels = 1;

	/**
	 * An internal cache for the built menu items array.
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	protected $startingPoint;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\Node
	 */
	protected $currentNode;

	/**
	 * @param integer $entryLevel
	 * @return void
	 */
	public function setEntryLevel($entryLevel) {
		$this->entryLevel = $entryLevel;
	}

	/**
	 * Return evaluated entryLevel value.
	 *
	 * @return integer
	 */
	public function getEntryLevel() {
		return $this->tsValue('entryLevel');
	}

	/**
	 * @param integer $maximumLevels
	 * @return void
	 */
	public function setMaximumLevels($maximumLevels) {
		$this->maximumLevels = $maximumLevels;
	}

	/**
	 * @return integer
	 */
	public function getMaximumLevels() {
		$maximumLevels = $this->tsValue('maximumLevels');
		if ($maximumLevels > self::MAXIMUM_LEVELS_LIMIT) {
			$maximumLevels = self::MAXIMUM_LEVELS_LIMIT;
		}
		return $maximumLevels;
	}

	/**
	 * @param integer $lastLevel
	 * @return void
	 */
	public function setLastLevel($lastLevel) {
		if ($lastLevel > self::MAXIMUM_LEVELS_LIMIT) {
			$lastLevel = self::MAXIMUM_LEVELS_LIMIT;
		}
		$this->lastLevel = $lastLevel;
	}

	/**
	 * Return evaluated lastLevel value.
	 *
	 * @return integer
	 */
	public function getLastLevel() {
		return $this->tsValue('lastLevel');
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $startingPoint
	 * @return void
	 */
	public function setStartingPoint($startingPoint) {
		$this->startingPoint = $startingPoint;
	}

	/**
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function getStartingPoint() {
		return $this->tsValue('startingPoint');
	}

	/**
	 * Returns the menu items according to the defined settings.
	 *
	 * @return array
	 */
	public function getItems() {
		if ($this->items === NULL) {
			$this->items = $this->buildItems();
		}
		return $this->items;
	}

	/**
	 * Builds the array of menu items containing those items which match the
	 * configuration set for this Menu object.
	 *
	 * @throws \TYPO3\Neos\Domain\Exception
	 * @return array An array of menu items and further information
	 */
	protected function buildItems() {
		$currentContext = $this->tsRuntime->getCurrentContext();
		if (!isset($currentContext['node']) && !$this->getStartingPoint()) {
			throw new \TYPO3\Neos\Domain\Exception('You must either set a "startingPoint" for the menu or "node" must be set in the TypoScript context.', 1369596980);
		} else {
			$this->currentNode = $currentContext['node'];
			$contentContext = $currentContext['node']->getContext();
		}
		$startingPoint = $this->getStartingPoint() ?: $currentContext['node'];

		$entryParentNode = $this->findParentNodeInBreadcrumbPathByLevel($this->getEntryLevel(), $contentContext->getCurrentSiteNode(), $startingPoint);
		if ($entryParentNode === NULL) {
			return array();
		}
		$lastParentNode = ($this->getLastLevel() !== NULL) ? $this->findParentNodeInBreadcrumbPathByLevel($this->getLastLevel(), $contentContext->getCurrentSiteNode(), $startingPoint) : NULL;

		return $this->buildRecursiveItemsArray($entryParentNode, $lastParentNode);
	}

	/**
	 * Recursively called method which builds the actual items array.
	 *
	 * @param NodeInterface $entryParentNode The parent node whose children should be listed as items
	 * @param NodeInterface $lastParentNode The last parent node whose children should be listed. NULL = no limit defined through lastLevel
	 * @param integer $currentLevel Level count for the recursion – don't use.
	 * @return array A nested array of menu item information
	 * @see buildItems()
	 */
	private function buildRecursiveItemsArray(NodeInterface $entryParentNode, NodeInterface $lastParentNode = NULL, $currentLevel = 1) {
		$items = array();
		foreach ($entryParentNode->getChildNodes('TYPO3.Neos:Document') as $currentNode) {
			if ($currentNode->isVisible() === FALSE || $currentNode->isHiddenInIndex() === TRUE || $currentNode->isAccessible() === FALSE) {
				continue;
			}

			$item = array(
				'label' => $currentNode->getProperty('title'),
				'node' => $currentNode,
				'state' => self::STATE_NORMAL
			);
			if ($currentNode === $this->currentNode) {
				$item['state'] = self::STATE_CURRENT;
			}

			if ($currentLevel < $this->getMaximumLevels() && $entryParentNode !== $lastParentNode) {
				$subItems = $this->buildRecursiveItemsArray($currentNode, $lastParentNode, $currentLevel + 1);
				if ($subItems !== array()) {
					$item['subItems'] = $subItems;
					if ($item['state'] !== self::STATE_CURRENT) {
						foreach ($subItems as $subItem) {
							if ($subItem['state'] === self::STATE_CURRENT || $subItem['state'] === self::STATE_ACTIVE) {
								$item['state'] = self::STATE_ACTIVE;
								break;
							}
						}
					}
				}
			}
			$items[] = $item;
		}
		return $items;
	}

	/**
	 * Finds the node in the current breadcrumb path between current site node and
	 * current node whose level matches the specified entry level.
	 *
	 * @param integer $givenSiteLevel The site level child nodes of the to be found parent node should have. See $this->entryLevel for possible values.
	 * @param NodeInterface $currentSiteNode
	 * @param NodeInterface $startingPoint
	 * @return NodeInterface The parent node of the node at the specified level or NULL if none was found
	 */
	private function findParentNodeInBreadcrumbPathByLevel($givenSiteLevel, NodeInterface $currentSiteNode, NodeInterface $startingPoint) {
		$parentNode = NULL;
		$breadcrumbNodes = $currentSiteNode->getContext()->getNodesOnPath($currentSiteNode, $startingPoint);

		if ($givenSiteLevel > 0 && isset($breadcrumbNodes[$givenSiteLevel - 1])) {
			$parentNode = $breadcrumbNodes[$givenSiteLevel - 1];
		} elseif ($givenSiteLevel <= 0) {
			$currentSiteLevel = count($breadcrumbNodes) - 1;
			if ($currentSiteLevel + $givenSiteLevel < 1) {
				$parentNode = $breadcrumbNodes[0];
			} else {
				$parentNode = $breadcrumbNodes[$currentSiteLevel + $givenSiteLevel - 1];
			}
		}
		return $parentNode;
	}
}
?>