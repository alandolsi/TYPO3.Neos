<?php
namespace TYPO3\Neos\ViewHelpers;

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
 * A View Helper to render enabled locales from Settings
 *
 * <code>
 * <neos:enabledLocales as="items" current="currentItem">
 *   {currentItem.label}
 *   <ul class="dropdown-menu">
 *     <f:for each="{items}" as="item">
 *       <li><a href="{neos:uri.node()}?locale={item.locale}">{item.label}</a></li>
 *     </f:for>
 *   </ul>
 * </neos:enabledLocales>
 * <code>
 * <output>
 * English
 * <ul class="dropdown-menu">
 *   <li><a href="home@user-test.html?locale=de">German</a></li>
 * </ul>
 * </output>
 */
class EnabledLocalesViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	const STATE_NORMAL = 'normal';
	const STATE_CURRENT = 'current';

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\I18n\Service
	 */
	protected $localizationService;

	/**
	 * Settings
	 *
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Provide enabled locale information with current locale
	 *
	 * @param string $as The name of the iteration variable
	 * @param string $current Name of template variable for holding current item separately
	 * @return string Rendered string
	 */
	public function render($as, $current = NULL) {
		$items = array();
		$currentItem = array();
		if (isset($this->settings['i18n']['enabledLocales'])) {
			$currentLocale = $this->session->getData('locale');
			$currentLocaleValue = $this->settings['i18n']['enabledLocales'][(string) $currentLocale];
			$switchLinkUri = clone $this->controllerContext->getRequest()->getHttpRequest()->getUri();
			$uriArguments = $switchLinkUri->getArguments();

			foreach ($this->settings['i18n']['enabledLocales'] as $locale => $value) {
					// always override
				$uriArguments['locale'] = $locale;
				$switchLinkUri->setQuery(http_build_query($uriArguments));
				$switchLink = (string) $switchLinkUri;

				if ($locale === (string)$currentLocale) {
					if ($current !== NULL) {
						$currentItem = array(
							'label' => $currentLocaleValue,
							'locale' => (string)$currentLocale,
							'state'	=> self::STATE_CURRENT,
							'switchLink' => $switchLink
						);
						continue;
					}
					$items[] = array(
						'label' => $value,
						'locale' => $locale,
						'state' => self::STATE_CURRENT,
						'switchLink' => $switchLink
					);
					continue;
				}
				$items[] = array(
					'label' => $value,
					'locale' => $locale,
					'state' => self::STATE_NORMAL,
					'switchLink' => $switchLink
				);
			}
		}
		$this->templateVariableContainer->add($as, $items);
		if ($current !== NULL) {
			$this->templateVariableContainer->add($current, $currentItem);
		}
		$output = $this->renderChildren();

		return $output;
	}

}
?>