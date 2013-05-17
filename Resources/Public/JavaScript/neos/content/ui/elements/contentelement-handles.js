/**
 */
define(
	[
		'Library/jquery-with-dependencies',
		'Library/underscore',
		'emberjs',
		'Content/Application',

		'vie/instance',
		'text!neos/templates/content/ui/contentelementHandles.html',
		'neos/content/ui/elements/new-contentelement-popover-content'
	],
	function ($, _, Ember, ContentModule, vieInstance, template, ContentElementPopoverContent) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/contentelement-handles');
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),

			_element: null,

			_entity: null,

			$newAfterPopoverContent: null,

			_entityCollectionIndex: null,

			_collection: null,

			popoverPosition: 'right',

			_nodePath: null,

			_pasteInProgress: false,

			_hidden: true,

			_showHide: false,

			_showRemove: true,

			_showCut: true,

			_showCopy: true,

			_hideToggleTitle: function() {
				return this.get('_hidden') === true ? 'Unhide' : 'Hide';
			}.property('_hidden'),

			_thisElementStartedCut: function() {
				var clipboard = T3.Content.Controller.NodeActions.get('_clipboard');
				if (!clipboard) {
					return false;
				}

				return (clipboard.type === 'cut' && clipboard.nodePath === this.get('_nodePath'));
			}.property('T3.Content.Controller.NodeActions._clipboard', '_nodePath'),

			_thisElementStartedCopy: function() {
				var clipboard = T3.Content.Controller.NodeActions.get('_clipboard');
				if (!clipboard) {
					return false;
				}

				return (clipboard.type === 'copy' && clipboard.nodePath === this.get('_nodePath'));
			}.property('T3.Content.Controller.NodeActions._clipboard', '_nodePath'),

			_thisElementIsAddingNewContent: function() {
				var elementIsAddingNewContent = T3.Content.Controller.NodeActions.get('_elementIsAddingNewContent');
				if (!elementIsAddingNewContent) {
					return false;
				}

				return (elementIsAddingNewContent === this.get('_nodePath'));
			}.property('T3.Content.Controller.NodeActions._elementIsAddingNewContent', '_nodePath'),

			_entityChanged: function() {
				this.set('_hidden', this.get('_entity').get('typo3:_hidden'));
			},

			didInsertElement: function() {
				var that = this,
					entity = vieInstance.entities.get(vieInstance.service('rdfa').getElementSubject(this.get('_element')));
				this.set('_entity', entity);
				this.set('_nodePath', entity.getSubjectUri());
				if (entity.has('typo3:_hidden') === true) {
					this.set('_showHide', true);
					this.set('_hidden', entity.get('typo3:_hidden'));
				}

				entity.on('change', this._entityChanged, this);

				this.$newAfterPopoverContent = $('<div />', {id: this.get(Ember.GUID_KEY)});

				this.$().find('.action-new').popover({
					additionalClasses: 't3-new-contentelement-popover',
					content: this.$newAfterPopoverContent,
					preventLeft: this.get('popoverPosition') === 'left' ? false : true,
					preventRight: this.get('popoverPosition') === 'right' ? false : true,
					preventTop: this.get('popoverPosition') === 'top' ? false : true,
					preventBottom: this.get('popoverPosition') === 'bottom' ? false : true,
					positioning: 'absolute',
					zindex: 10090,
					closeEvent: function() {
						that.set('pressed', false);
					},
					openEvent: function() {
						that.onPopoverOpen.call(that);
					}
				});

				this.$().find('.action-remove').popover({
					header: '<div>Delete this element?</div>',
					content: $('<div class="typo3-confirmationdialog"><div class="actions"><button class="delete t3-button btn btn-mini btn-danger">Delete</button> <button class="cancel t3-button btn btn-mini">Cancel</button></div></div>'),
					preventLeft: this.get('popoverPosition') === 'left' ? false : true,
					preventRight: this.get('popoverPosition') === 'right' ? false : true,
					preventTop: this.get('popoverPosition') === 'top' ? false : true,
					preventBottom: this.get('popoverPosition') === 'bottom' ? false : true,
					positioning: 'absolute',
					zindex: 10090,
					openEvent: function() {
						this.popover$.find('button.delete').click(function() {
							that.get('_element').fadeOut(function() {
								that.get('_element').addClass('t3-contentelement-removed');
							});

							T3.Content.Controller.NodeActions.remove(that.get('_entity'));
							that.$().find('.action-remove').trigger('hidePopover');
						});

						this.popover$.find('button.cancel').click(function() {
							that.$().find('.action-remove').trigger('hidePopover');
						});
					}
				});
			},

			toggleHidden: function() {
				var entity = this.get('_entity'),
					value = !entity.get('typo3:_hidden');
				this.set('_hidden', value);
				entity.set('typo3:_hidden', value);
				T3.Content.Controller.Inspector.nodeProperties.set('_hidden', value);
				T3.Content.Controller.Inspector.apply();
			},

			cut: function() {
				T3.Content.Controller.NodeActions.cut(this.get('_nodePath'));
			},

			copy: function() {
				T3.Content.Controller.NodeActions.copy(this.get('_nodePath'));
			},

			pasteAfter: function() {
				if (T3.Content.Controller.NodeActions.pasteAfter(this.get('_nodePath')) === true) {
					this.set('_pasteInProgress', true);
				}
			},

			newAfter: function() {
				var that = this;
				this.$().find('.action-new').trigger('showPopover');
			},

			onPopoverOpen: function() {
				var groups = {};

				_.each(this.get('_collection').options.definition.range, function(nodeType) {
					var type = this.get('_collection').options.vie.types.get(nodeType);
					type.metadata.nodeType = type.id.substring(1, type.id.length - 1).replace(ContentModule.TYPO3_NAMESPACE, '');

					if (type.metadata.ui && type.metadata.ui.group) {
						if (!groups[type.metadata.ui.group]) {
							groups[type.metadata.ui.group] = {
								name: type.metadata.ui.group,
								children: []
							};
						}
						groups[type.metadata.ui.group].children.push(type.metadata);
					}
				}, this);

					// Make the data object an array for usage in #each helper
				var data = [];

				T3.Configuration.nodeTypeGroups.forEach(function(groupName) {
					if (groups[groupName]) {
						data.push(groups[groupName]);
					}
				});

				ContentElementPopoverContent.create({
					_options: this.get('_collection').options,
					_index: this.get('_entityCollectionIndex'),
					_clickedButton: this,
					data: data
				}).replaceIn(this.$newAfterPopoverContent);
			},

			willDestroyElement: function() {
				this.$().find('.action-new').trigger('hidePopover');
			}
		});
	}
);
