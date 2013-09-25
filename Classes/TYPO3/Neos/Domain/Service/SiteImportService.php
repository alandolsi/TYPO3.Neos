<?php
namespace TYPO3\Neos\Domain\Service;

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

/**
 * The Site Import Service
 *
 * @Flow\Scope("prototype")
 * @api
 */
class SiteImportService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\ImageRepository
	 */
	protected $imageRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Checks for the presence of Sites.xml in the given package and imports
	 * it if found.
	 *
	 * @param $packageKey
	 * @param \TYPO3\TYPO3CR\Domain\Service\Context $contentContext
	 * @return void
	 * @throws \TYPO3\Neos\Exception
	 */
	public function importFromPackage($packageKey, \TYPO3\TYPO3CR\Domain\Service\Context $contentContext) {
		if (!$this->packageManager->isPackageActive($packageKey)) {
			throw new \TYPO3\Neos\Exception('Error: Package "' . $packageKey . '" is not active.');
		} elseif (!file_exists('resource://' . $packageKey . '/Private/Content/Sites.xml')) {
			throw new \TYPO3\Neos\Exception('Error: No content found in package "' . $packageKey . '".');
		} else {
			try {
				$this->importSitesFromFile('resource://' . $packageKey . '/Private/Content/Sites.xml', $contentContext);
			} catch (\Exception $exception) {
				throw new \TYPO3\Neos\Exception('Error: During import an exception occurred. ' . $exception->getMessage(), 1300360480, $exception);
			}
		}
	}

	/**
	 * @param string $pathAndFilename
	 * @param \TYPO3\TYPO3CR\Domain\Service\Context $contentContext
	 * @return void
	 * @throws \TYPO3\Flow\Package\Exception\UnknownPackageException
	 * @throws \TYPO3\Flow\Package\Exception\InvalidPackageStateException
	 */
	public function importSitesFromFile($pathAndFilename, \TYPO3\TYPO3CR\Domain\Service\Context $contentContext) {
			// no file_get_contents here because it does not work on php://stdin
		$fp = fopen($pathAndFilename, 'rb');
		$xmlString = '';
		while (!feof($fp)) {
			$xmlString .= fread($fp, 4096);
		}
		fclose($fp);

		$xml = new \SimpleXMLElement($xmlString, LIBXML_PARSEHUGE);
		foreach ($xml->site as $siteXml) {
			$site = $this->siteRepository->findOneByNodeName((string)$siteXml['nodeName']);
			if ($site === NULL) {
				$site = new \TYPO3\Neos\Domain\Model\Site((string)$siteXml['nodeName']);
				$this->siteRepository->add($site);
			} else {
				$this->siteRepository->update($site);
			}
			if ((string)$siteXml->properties->name !== '') {
				$site->setName((string)$siteXml->properties->name);
			}
			$site->setState((integer)$siteXml->properties->state);

			$siteResourcesPackageKey = (string)$siteXml->properties->siteResourcesPackageKey;
			if ($this->packageManager->isPackageAvailable($siteResourcesPackageKey) === FALSE) {
				throw new \TYPO3\Flow\Package\Exception\UnknownPackageException('Package "' . $siteResourcesPackageKey . '" specified in the XML as site resources package does not exist.', 1303891443);
			}
			if ($this->packageManager->isPackageActive($siteResourcesPackageKey) === FALSE) {
				throw new \TYPO3\Flow\Package\Exception\InvalidPackageStateException('Package "' . $siteResourcesPackageKey . '" specified in the XML as site resources package is not active.', 1303898135);
			}
			$site->setSiteResourcesPackageKey($siteResourcesPackageKey);

			$rootNode = $contentContext->getRootNode();

			if ($rootNode->getNode('/sites') === NULL) {
				$rootNode->createSingleNode('sites');
			}

			$siteNode = $rootNode->getNode('/sites/' . $site->getNodeName());
			if ($siteNode === NULL) {
				$siteNode = $rootNode->getNode('/sites')->createSingleNode($site->getNodeName());
			}
			$siteNode->setContentObject($site);

			$this->parseNodes($siteXml, $siteNode);
		}
	}

	/**
	 * Iterates over the nodes and adds them to the workspace.
	 *
	 * @param \SimpleXMLElement $parentXml
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeData $parentNode
	 * @return void
	 */
	protected function parseNodes(\SimpleXMLElement $parentXml, \TYPO3\TYPO3CR\Domain\Model\NodeInterface $parentNode) {
		foreach ($parentXml->node as $childNodeXml) {
			$childNode = $parentNode->getNode((string)$childNodeXml['nodeName']);
			$nodeTypeName = (string)$childNodeXml['type'];
			if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
				$nodeType = $this->nodeTypeManager->createNodeType($nodeTypeName);
			} else {
				$nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
			}
			if ($childNode === NULL) {
				$identifier = (string)$childNodeXml['identifier'] === '' ? NULL : (string)$childNodeXml['identifier'];
				$childNode = $parentNode->createSingleNode((string)$childNodeXml['nodeName'], $nodeType, $identifier);
			} else {
				$childNode->setNodeType($nodeType);
			}

			$childNode->setHidden((boolean)$childNodeXml['hidden']);
			$childNode->setHiddenInIndex((boolean)$childNodeXml['hiddenInIndex']);
			if ($childNodeXml['hiddenBeforeDateTime'] != '') {
				$childNode->setHiddenBeforeDateTime(\DateTime::createFromFormat(\DateTime::W3C, (string)$childNodeXml['hiddenBeforeDateTime']));
			}
			if ($childNodeXml['hiddenAfterDateTime'] != '') {
				$childNode->setHiddenAfterDateTime(\DateTime::createFromFormat(\DateTime::W3C, (string)$childNodeXml['hiddenAfterDateTime']));
			}

			if ($childNodeXml->properties) {
				foreach ($childNodeXml->properties->children() as $childXml) {
					if (isset($childXml['__type']) && (string)$childXml['__type'] == 'object') {
						$childNode->setProperty($childXml->getName(), $this->xmlToObject($childXml));
					} else {
						$childNode->setProperty($childXml->getName(), (string)$childXml);
					}
				}
			}

			if ($childNodeXml->accessRoles) {
				$accessRoles = array();
				foreach ($childNodeXml->accessRoles->children() as $childXml) {
					$accessRoles[] = (string)$childXml;
				}
				$childNode->setAccessRoles($accessRoles);
			}

			if ($childNodeXml->node) {
				$this->parseNodes($childNodeXml, $childNode);
			}
		}
	}

	/**
	 * Handles conversion of our XML format into objects.
	 *
	 * Note: currently only ImageVariant instances are supported.
	 *
	 * @param \SimpleXMLElement $xml
	 * @return object
	 * @throws \TYPO3\Neos\Domain\Exception
	 */
	protected function xmlToObject(\SimpleXMLElement $xml) {
		$object = NULL;
		$className = (string)$xml['__classname'];
		switch ($className) {
			case 'TYPO3\Media\Domain\Model\ImageVariant':
				$processingInstructions = unserialize(trim((string)$xml->processingInstructions));
				$originalResource = $this->resourceManager->createResourceFromContent(
					base64_decode(trim((string)$xml->originalImage->resource->content)),
					(string)$xml->originalImage->resource->filename
				);
				$image = new \TYPO3\Media\Domain\Model\Image($originalResource);
				$this->imageRepository->add($image);
				$object = new \TYPO3\Media\Domain\Model\ImageVariant(
					$image,
					$processingInstructions
				);
			break;
			case 'DateTime':
				$object = \DateTime::createFromFormat(\DateTime::W3C, (string)$xml->dateTime);
				break;
			default:
				throw new \TYPO3\Neos\Domain\Exception('Unsupported object of target type "' . $className . '" hit during XML import.', 1347144938);
		}

		return $object;
	}
}
?>
