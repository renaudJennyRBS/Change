(function ($) {

	//=========================================================================
	//
	// TagsEditor class
	//
	//=========================================================================
	
	
	var TagsEditor = function ($el) {
		this.$el = $el;
		this.currentModule = null;
	};
	
	
	TagsEditor.prototype = {

			
		//=====================================================================

			
		changeModule: function (module) {
			if (this.currentModule) {
				this.$el.find('[data-tags-editor-module="' + this.currentModule + '"]').hide();
			}
			this.currentModule = module;
			this.$el.find('[data-tags-editor-module="' + this.currentModule + '"]').slideDown('fast');
			this.updateAvailableTags();
		},

		
		//=====================================================================
		
		
		updateAvailableTags: function () {
			var _this = this;
			this.$el.find('[data-tags-editor-module="' + this.currentModule + '"] .tag').each(function () {
				/*if ( _this.dashboard.widgetExists($(this).data('id')) ) {
					$(this).attr('disabled', 'disabled');
				} else {
					$(this).removeAttr('disabled');
				}*/
			});
		},
		
		
		open: function (docs, $trigger, elmList) {
			this.$el.show();
			this.elmList = elmList;
			this.docs = docs;
			this.$trigger = $trigger;
			
			var _this = this;
			var p = $.get('modules/tags.php');
			p.done(function (response) {
				_this.loaded(response);
			});
			p.fail(function () {
				console.error("Error while loading tags editor.");
			});
			this.elmList.disableSelection();
		},
		
		
		loaded: function (html) {
			this.$el.html(html);
			if (this.docs.length > 1) {
				this.$el.find('h4 span').html(this.docs.length + " documents");
			} else {
				this.$el.find('h4 span').html(this.docs[0].label);
			}
			var _this = this;
			var $selectModule = this.$el.find('select[data-tags-editor="changeModule"]');
			$selectModule.change(function () {
				_this.changeModule($(this).val());
			});
			this.$el.find('[data-tags-editor="close"]').click(function () {
				_this.close();
			});
			this.$el.on('click', '.tag', function () {
				_this.toggleTag($(this).data('id'));
			});
			$selectModule.trigger('change');
			
			this.$link = $('#tagsEditorListLink');
			if (this.$trigger !== null) {
				var y = Math.round(this.$trigger.offset().top + 10);
				var h = Math.round(this.$el.offset().top - y - 3);
				var x = Math.round(this.$trigger.offset().left - this.$link.outerWidth());
				this.$link.css({
					left: x + 'px',
					top: y + 'px',
					height: h + 'px'
				}).fadeIn('fast');
			} else {
				this.$link.hide();
			}
		},
		
		
		close: function () {
			this.$link.hide();
			this.$el.hide();
			this.elmList.enableSelection();
		},
		
		
		toggleTag: function (tagId) {
			
		}
		
	};

	
	$.fn.tagsEditor = function (method, args) {
		return this.each(function () {
			if ( ! $(this).data('tagsEditor') ) {
				$(this).data('tagsEditor', new TagsEditor($(this)));
			}
			var editor = $(this).data('tagsEditor');
			if (typeof method === 'string' && method in editor) {
				editor[method](args);
			}
		});
	};

	
	$('body').on('click', '[data-tags-editor-open-for]', function (e) {
		e.preventDefault();
		var doc = $($(this).data('tagsEditorOpenFor')).data();
		var elmList = $(this).closest('.table').elementList('get');
		$('#tagsEditor').tagsEditor();
		$('#tagsEditor').data('tagsEditor').open( [ doc ], $(this), elmList);
	});
	
})(window.jQuery);