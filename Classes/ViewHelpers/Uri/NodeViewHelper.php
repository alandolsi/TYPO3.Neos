<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\ViewHelpers\Uri;

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
 * A view helper for creating URIs to nodes.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <typo3:uri.node />
 * </code>
 *
 * Output:
 * live/sites/mysite.com/homepage/about.html
 * (depending on current workspace, current node, format etc.)
 *
 * <code title="Additional arguments">
 * <typo3:uri.node node="{myNode}" format="json" service="rest" />
 * </code>
 *
 * Output:
 * typo3/service/rest/v1/node/live/sites/mysite.com/homepage/about.json
 * (depending on current workspace, current node, format etc.)
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 */
class NodeViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render the Uri.
	 *
	 * @param mixed $node A node object or a node path
	 * @param string $format Format to use for the URL, for example "html" or "json"
	 * @param string $service If set, an URI using the specified service is rendered. Examples: "Rest", "Frontend"
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @return string The rendered URI
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render($node, $format = 'html', $service = 'Frontend', $absolute = FALSE) {
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$uri = $uriBuilder
			->reset()
			->setCreateAbsoluteUri($absolute)
			->setFormat($format)
			->uriFor(NULL, array('node' => $node, 'service' => $service), 'Node', 'TYPO3', 'Service\Rest\V1');
		return $uri;
	}
}
?>