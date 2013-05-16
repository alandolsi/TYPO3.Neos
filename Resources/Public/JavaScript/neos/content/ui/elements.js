/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'jquery',
	'neos/content/ui/elements/toolbar',
	'neos/content/ui/elements/button',
	'neos/content/ui/elements/toggle-button',
	'neos/content/ui/elements/popover-button',
	'neos/content/ui/elements/contentelement-handles',
	'neos/content/ui/elements/section-handles',
	'neos/content/ui/elements/page-tree',
	'text!neos/templates/content/ui/topToolbarTemplate.html',
	'text!neos/templates/content/ui/footerTemplate.html',
	'jquery.popover'
],
function($, Toolbar, Button, ToggleButton, PopoverButton, ContentElementHandle, SectionHandle, PageTree, topToolbarTemplate, footerTemplate) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/elements');

	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}

	T3.Content.UI = T3.Content.UI || {};

	/**
	 * T3.Content.UI.Toolbar
	 *
	 * Toolbar which can contain other views. Has two areas, left and right.
	 */
	T3.Content.UI.Toolbar = Toolbar;

	T3.Content.UI.NavigationToolbar = T3.Content.UI.Toolbar.extend({
		elementId: 't3-toolbar',
		template: Ember.Handlebars.compile(topToolbarTemplate)
	});

	T3.Content.UI.FooterToolbar = T3.Content.UI.Toolbar.extend({
		elementId: 't3-footer',
		template: Ember.Handlebars.compile(footerTemplate)
	});

	/**
	 * T3.Content.UI.Button
	 *
	 * A simple, styled TYPO3 button.
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	T3.Content.UI.Button = Button;

	/**
	 * T3.Content.UI.Image
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	T3.Content.UI.Image = Ember.View.extend({
		tagName: 'img',
		attributeBindings: ['src']
	});

	/**
	 * T3.Content.UI.ToggleButton
	 *
	 * A button which has a "pressed" state
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	T3.Content.UI.ToggleButton = ToggleButton;

	/**
	 * T3.Content.UI.PopoverButton
	 */
	T3.Content.UI.PopoverButton = PopoverButton;

	T3.Content.UI.ContentElementHandle = ContentElementHandle;

	T3.Content.UI.SectionHandle = SectionHandle;

	T3.Content.UI.PageTree = PageTree;
});