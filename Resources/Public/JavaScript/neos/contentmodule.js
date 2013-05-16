/**
 * T3.ContentModule
 *
 * Entry point which initializes the Content Module UI
 */

define(
[
	'jquery',
	'vie/instance',
	'emberjs',
	'create',
	'neos/common',
	'neos/content/model',
	'neos/content/ui',
	'neos/content/controller',
	'jquery.hotkeys'
],
function($, vie, Ember, CreateJS) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/contentmodule');

	return Ember.Application.extend(Ember.Evented, {
		rootElement: '#t3-application',

		TYPO3_NAMESPACE: 'http://www.typo3.org/ns/2012/Flow/Packages/Neos/Content/',

		/**
		 * The following setting is set to "true" when unfinished features should be shown.
		 *
		 * You can use it in the UI as following:
		 *
		 * Ember.View.extend({
		 *    template: Ember.Handlebars.compile('<span style="color:white">!!! Development mode !!!</span>'),
		 *    isVisibleBinding: 'T3.ContentModule.showDevelopmentFeatures'
		 * })
		 *
		 * OR
		 *
		 * {{view T3.Content.UI.Button label="Inspect" isVisibleBinding="T3.ContentModule.showDevelopmentFeatures"}}
		 *
		 * OR
		 * {{#boundIf T3.ContentModule.showDevelopmentFeatures}}
		 *   Display only in development mode
		 * {{/boundif}}
		 */
		showDevelopmentFeatures: false,

		currentUri: window.location.href,

		_isLoadingPage : null,

		vie: null,

		_activeEntity: null,

		_vieOptions: {
			stanbolUrl: null,
			dbPediaUrl: null
		},

		$loader: null,
		spinner: null,

		bootstrap: function() {
			this.set('vie', vie);
			this._initializeAjaxPageReload();
			this._initializeVie();

			this._initializeDevelopmentFeatures();

			this._initializeNotifications();

			// this._initializeDoubleClickEdit();

			$('body').toggleClass('t3-ui-controls t3-backend');

			this._setPagePosition();

			this._initializeShortcuts();
			this._initializeHistoryManagement();

				// Remove the Aloha sidebar completely from DOM, as there is
				// currently no other way to deactivate it.
			$('.aloha-sidebar-bar').remove();
		},

		_initializeNotifications: function() {
				// Initialize notifications
			$('#t3-application').append('<div class="t3-notification-container t3-ui"></div>');
				// TODO: Remove with resolving #45049
			$('#t3-application').midgardNotifications();
		},

		_initializeDevelopmentFeatures: function() {
			var that = this;
			window.addEventListener('hashchange', function() {
				that._enableDevelopmentFeaturesIfNeeded();
			}, false);
			this._enableDevelopmentFeaturesIfNeeded();
		},

		_initializeDoubleClickEdit: function() {
			$('.t3-contentelement').live('dblclick', function(event) {
				if ($('.t3-primary-editor-action').length > 0) {
					$('.t3-primary-editor-action').click();
				}
				event.preventDefault();
			});
		},

		_initializeVie: function() {
			var that = this;

			if (this.get('_vieOptions').stanbolUrl) {
				vie.use(new vie.StanbolService({
					proxyDisabled: true,
					url: this.get('_vieOptions').stanbolUrl
				}));
			}

			if (this.get('_vieOptions').dbPediaUrl) {
				vie.use(new vie.DBPediaService({
					proxyDisabled: true,
					url: this.get('_vieOptions').dbPediaUrl
				}));
			}

			$.when(T3.ResourceCache.get(T3.Configuration.VieSchemaUri), T3.ResourceCache.get(T3.Configuration.NodeTypeSchemaUri)).done(function(vieSchemaString, nodeTypeSchemaString) {
					var schema = JSON.parse(vieSchemaString);
					VIE.Util.loadSchemaOrg(vie, schema, null);

					T3.Configuration.Schema = JSON.parse(nodeTypeSchemaString);

					that._initializeVieAfterSchemaIsLoaded(vie);
				}).fail(function(xhr, status, error) {
					console.warn('Error loading schemas.', xhr, status, error);
				});

		},

		_initializeVieAfterSchemaIsLoaded: function() {
			T3.Content.Model.NodeSelection.initialize();
			T3.Content.Model.PublishableNodes.initialize();
			this.trigger('pageLoaded');

			this._registerVieNodeTypeTemplateCallbacks();
			this._initializeCreateJs();
		},

		/**
		 * Register template generation callbacks.
		 *
		 * For adding new content elements VIE needs an HTML template. This method registers callback methods
		 * for generating those templates. The template itself is rendered on the server, and contains the
		 * rendered output of the requested node type, rendered within the current typoscript path.
		 *
		 * @return {Void}
		 */
		_registerVieNodeTypeTemplateCallbacks: function() {
			_.each(vie.types.toArray(), function(type) {
				var nodeType = type.id.substring(1, type.id.length - 1).replace(T3.ContentModule.TYPO3_NAMESPACE, '');
				var prefix = vie.namespaces.getPrefix(type.id);

				if (prefix === 'typo3') {
					vie.service('rdfa').setTemplate('typo3:' + nodeType, 'typo3:content-collection', function(entity, callBack, collectionView) {
							// This callback function is called whenever we create a content element
						var type = entity.get('@type'),
							nodeType = type.id.substring(1, type.id.length - 1).replace(T3.ContentModule.TYPO3_NAMESPACE, ''),
							referenceEntity = null,
							lastMatchedEntity = null;

						var afterCreationCallback = function(nodePath, template) {
							entity.set('@subject', nodePath);

								// We also want to load all the other RDFa properties on the entity.
								// Else, editing newly created content elements in the Property Inspector
								// does not work.
							vie.load({element: template}).from('rdfa').execute();
							callBack(template);

								// When adding nested content elements (like the two-column-element),
								// we need to refresh CreateJS to render the content element handles
								// for the nested sections.
							CreateJS.enableEdit();
						};

						_.each(collectionView.collection.models, function(matchEntity) {
							if (entity === matchEntity && lastMatchedEntity) {
								referenceEntity = lastMatchedEntity;
								T3.Content.Controller.NodeActions.addBelow(
									nodeType,
									referenceEntity,
									afterCreationCallback
								);
							} else {
								lastMatchedEntity = matchEntity;
							}
						});

						if (referenceEntity === null) {
								// No reference entity found. This only happens when an element is created into a section
							if (collectionView.collection.models.length === 1) {
									// The section only contains the new entity and was empty before, so we create the node into the section
								T3.Content.Controller.NodeActions.addInside(
									nodeType,
									vie.entities.get($(collectionView.el).attr('about')),
									afterCreationCallback
								);
							} else {
									// The section contains other entities, so we create the node before the first entity (index 1 as index 0 is the newly created entity)
								T3.Content.Controller.NodeActions.addAbove(
									nodeType,
									collectionView.collection.models[1],
									afterCreationCallback
								);
							}
						}
					});
				}
			});
		},

		_initializeCreateJs: function() {
				// Midgard Storage
			$('#t3-application').midgardStorage({
				vie: vie,
				url: function () { /* empty function to prevent Midgard error */ },
				localStorage: true,
				autoSave: true
			});

			CreateJS.initialize();
		},

		_initializeShortcuts: function() {
			var that = this;
			$(document).bind('keydown', 'alt+p', function() {
				T3.Content.Controller.Preview.togglePreview();
				return false;
			});
		},

		_initializeHistoryManagement: function() {
			var that = this;
			if (window.history) {
				window.history.replaceState({uri: window.location.href}, document.title, window.location.href);
			}
			window.addEventListener('popstate', function(event) {
				if (event.state) {
					that.loadPage(event.state.uri, true);
				}
			});
		},

		_enableDevelopmentFeaturesIfNeeded: function() {
			if (window.location.hash === '#dev') {
				this.set('showDevelopmentFeatures', true);
				T3.Common.LocalStorage.setItem('showDevelopmentFeatures', true);
			} else if (window.location.hash === '#nodev') {
				this.set('showDevelopmentFeatures', false);
				T3.Common.LocalStorage.removeItem('showDevelopmentFeatures');
			} else if(T3.Common.LocalStorage.getItem('showDevelopmentFeatures')) {
				this.set('showDevelopmentFeatures', true);
			}
		},

		/**
		 * Intercept all links, and instead use AJAX for reloading the page.
		 */
		_initializeAjaxPageReload: function() {
			this._linkInterceptionHandler($('a:not(.t3-ui a, .aloha-floatingmenu a)'));
			this._linkInterceptionHandler('a.t3-link-ajax', true);
		},

		_setPagePosition: function() {
			var hash = location.hash;
			if (hash.length > 0) {
				var contentElement = $('#' + hash.substring(1));
				if (contentElement.length > 0) {
					window.scroll(0, contentElement.position().top - $('body').offset().top);
				}
			}
		},

		reloadPage: function() {
			this.loadPage(T3.ContentModule.currentUri);
		},

		_linkInterceptionHandler: function(selector, constant) {
			var that = this;
			function clickHandler(e, link) {
				e.preventDefault();
				var $this = $(link);
				if (!$this.attr('href').match(/[a-z]*:\/\//) && $this.parents('.t3-contentelement-active').length === 0 && $this.parents('.t3-inline-editable').length === 0) {
						// We only load the page if the link is a non-external link and the parent contentelement is not selected
						// as links should not be followed if the element is currently being edited or being editable
					that.loadPage($this.attr('href'));
				}
			}
			if (constant === true) {
				$(document).delegate(selector, 'click', function(e) {
					clickHandler(e, this);
				});
			} else {
				$(selector).click(function(e) {
					clickHandler(e, this);
				});
			}
		},

		loadPage: function(uri, ignorePushToHistory) {
				// TODO: when http://forge.typo3.org/issues/42014 is fixed this might be unnecessary
			$('.t3-new-contentelement-popover').remove();

			var that = this;
			if (uri === '#') {
					// Often, pages use an URI of "#" to go to the homepage. In this case,
					// we extract the current workspace name and redirect to this workspace instead.
				var siteRoot = $('#t3-page-metainformation').attr('data-__siteroot');
				var workspaceName = siteRoot.substr(siteRoot.lastIndexOf('@') + 1);
				uri = '@' + workspaceName;
			}

			var selectorsToReplace = [];

			$('.t3-reloadable-content').each(function() {
				if (!$(this).parents('.t3-reloadable-content').length) {
					var id = $(this).attr('id');
					if (!id) {
						// TODO: we need cleaner developer error handling
						throw 'You have marked a DOM element with the CSS class t3-reloadable-content; but this element has no ID.';
					}
					selectorsToReplace.push('#' + id);
				}
			});

			if (selectorsToReplace.length === 0) {
					// FALLBACK: The user did not configure reloadable content;
					// so we fall back to classical reload.
				window.location.href = uri;
				return;
			}

			this.showPageLoader();
			this.set('_isLoadingPage', true);

			if (window.history && !ignorePushToHistory) {
				window.history.pushState({uri: uri}, document.title, uri);
			}

			var currentlyActiveContentElementNodePath = $('.t3-contentelement-active').attr('about');
			$.get(uri, function(htmlString, status) {
				if (status === 'success') {
					var $htmlDom = $(htmlString);

					$.each(selectorsToReplace, function(index, selector) {
						if ($htmlDom.find(selector).length > 0) {
							$(selector).replaceWith($htmlDom.find(selector));
						} else if ($htmlDom.filter(selector).length > 0) {
							// find only looks inside the *descendants* of the result
							// set; that's why we might need to use "filter" if a top-
							// level element has the t3-reloadable-content CSS class applied
							$(selector).replaceWith($htmlDom.filter(selector));
						} else {
							// todo find cleaner solution for pages with different structures
							// but without the classic reload, loadPage breaks here
							if (typeof console !== 'undefined') {
								console.log('Target HTML selector was not found because of a different page structure');
							}
							window.location.href = uri;
						}

						that._linkInterceptionHandler($(selector).find('a'));
					});

					var $newMetaInformation = $htmlDom.filter('#t3-page-metainformation');
					if ($newMetaInformation.length === 0) {
						// FALLBACK: Something went really wrong with the fetching.
						// so we reload the whole backend.
						window.location.href = uri;
					} else {
						T3.ContentModule.set('currentUri', uri);
					}
					$('#t3-page-metainformation').replaceWith($newMetaInformation);
					$('title').html($htmlDom.filter('title').html());

					that._setPagePosition();

						// Update node selection (will update VIE)
					T3.Content.Model.NodeSelection.initialize();
					T3.Content.Model.PublishableNodes.initialize();
					that.trigger('pageLoaded');

						// Refresh CreateJS, renders the button bars f.e.
					CreateJS.enableEdit();

						// If doing a reload, we highlight the currently active content element again
					var $currentlyActiveContentElement = $('[about="' + currentlyActiveContentElementNodePath + '"]');
					if ($currentlyActiveContentElement.length === 1) {
						T3.Content.Model.NodeSelection.updateSelection($currentlyActiveContentElement);
					}
				} else {
						// FALLBACK: AJAX error occured,
						// so we reload the whole backend.
					window.location.href = uri;
				}
				that.set('_isLoadingPage', false);
				that.hidePageLoader();
			});
		},

		/**
		 * Display an overlay over the full frontend page with a loading indicator.
		 *
		 * This method is automatically called during an in-page reload. Furthermore,
		 * this method should be called by other part of the content module if
		 * they need to do some work (like saving changes to the server), but
		 * already know that this will be followed by a reload of the current page.
		 */
		showPageLoader: function() {
			if (this.$loader !== null) {
				this.$loader.fadeTo('fast', .8);
				this.spinner.spin(this.$loader.get(0));
				return;
			}
			var that = this;
			require([
				'spinjs'
			], function(Spinner) {
				that.$loader = $('<div />').addClass('t3-pageloader-wrapper').fadeTo(0, .8).appendTo($('body'));
				that.spinner = new Spinner({
					lines: 13, // The number of lines to draw
					length: 15, // The length of each line
					width: 4, // The line thickness
					radius: 10, // The radius of the inner circle
					corners: 1, // Corner roundness (0..1)
					rotate: 0, // The rotation offset
					color: '#000', // #rgb or #rrggbb
					speed: 1, // Rounds per second
					trail: 64, // Afterglow percentage
					shadow: false, // Whether to render a shadow
					hwaccel: false, // Whether to use hardware acceleration
					className: 't3-pageloader', // The CSS class to assign to the spinner
					zIndex: 2e9, // The z-index (defaults to 2000000000)
					top: 'auto', // Top position relative to parent in px
					left: 'auto' // Left position relative to parent in px
				}).spin(that.$loader.get(0));
			});
		},

		hidePageLoader: function() {
			var that = this;
			this.$loader.fadeOut('fast', function() {
				that.spinner.stop();
			});
		},

		hidePageLoaderSpinner: function() {
			if (this.spinner !== null) {
				this.spinner.stop();
			}
		}
	}).create();
});
