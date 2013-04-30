(function ($) {


	// Initialize modal
	var modal = $('#modalTimeZoneSelector').modal( {
		backdrop: true,
		keyboard: true,
		show: false
	} );
	modal.find('.modal-footer button').unbind('click');
	modal.find('.modal-footer button').click(function() {
		var option = modal.find('select.timezone-list option:selected');
		modal.modal('hide');
		$('body').trigger('rbschange.timezone.changed', [ $(this).data('scope'), { 'id': option.val(), 'label': option.text() } ] );
	} );


	//=========================================================================
	//
	// Timezone Selector
	//
	//=========================================================================


	var TimezoneSelector = function($element, label) {
		this.$el = $element;
		this.$textEl = this.$el.find('span');
		this.inputLabel = label || this.$el.closest('.control-group').find('label.control-label').text();
		this.init();
	};


	TimezoneSelector.messages = {
	};


	TimezoneSelector.prototype = {

		init: function() {

			var _tzs = this;
			$('body').bind('rbschange.timezone.changed', function(event, scope, timezone) {
				_tzs.timezoneChanged(scope, timezone);
			} );

			this.$el.click(function() {
				_tzs.openSelector();
			});
		},


		openSelector: function() {
			modal.find('.modal-body strong.element').html(this.inputLabel);
			modal.modal('show');
		},


		timezoneChanged: function(scope, timezone) {
			this.$textEl.html(timezone.id);
			this.$el.attr('title', timezone.label);
			this.$el.hide().fadeIn();
		}

	};


	//=========================================================================
	//
	// jQuery plugin definition
	//
	//=========================================================================


	$.fn.timezoneSelector = function(label) {
		return this.each(function() {
			var $this = $(this);
			if ( ! $this.data('timezoneSelector') ) {
				$this.data('timezoneSelector', new TimezoneSelector($this, label));
			}
		});
	};


})( window.jQuery );