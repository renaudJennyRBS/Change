(function ($)
{
	"use strict";

	function KeyboardService($rootScope)
	{
		$rootScope.rbsChangeKeyboardStatus = {
			'shift': false,
			'ctrl': false,
			'alt': false,
			'meta': false
		};

		function keyDown(event)
		{
			if (event.shiftKey)
			{
				$rootScope.rbsChangeKeyboardStatus.shift = true;
			}
			if (event.ctrlKey)
			{
				$rootScope.rbsChangeKeyboardStatus.ctrl = true;
			}
			if (event.altKey)
			{
				$rootScope.rbsChangeKeyboardStatus.alt = true;
			}
			if (event.metaKey)
			{
				$rootScope.rbsChangeKeyboardStatus.meta = true;
			}
			$rootScope.$digest();
		}

		function keyUp(event)
		{
			if (!event.shiftKey)
			{
				$rootScope.rbsChangeKeyboardStatus.shift = false;
			}
			if (!event.ctrlKey)
			{
				$rootScope.rbsChangeKeyboardStatus.ctrl = false;
			}
			if (!event.altKey)
			{
				$rootScope.rbsChangeKeyboardStatus.alt = false;
			}
			if (!event.metaKey)
			{
				$rootScope.rbsChangeKeyboardStatus.meta = false;
			}
			$rootScope.$digest();
		}

		this.watch = function (scope, key, callback)
		{
			var expr = 'rbsChangeKeyboardStatus.' +
				key.replace(/\+/g, ' && rbsChangeKeyboardStatus.').replace(/\-/g, ' && !rbsChangeKeyboardStatus.');
			var deregistrationFunc = $rootScope.$watch(expr, function (value, oldValue)
			{
				if (value === true || (value === false))
				{
					callback(value, oldValue);
				}
			}, true);
			scope.$on('$destroy', function ()
			{
				deregistrationFunc();
			});
		};

		$('body').on('keydown', keyDown).on('keyup', keyUp);
	}

	/**
	 * <rbs-kb-switch>
	 *    <i rbs-kb-when="shift-alt" class="icon-plane icon-3x"></i>
	 *    <i rbs-kb-when="alt-shift" class="icon-bell icon-3x"></i>
	 *    <i rbs-kb-when="alt+shift" class="icon-ambulance icon-3x"></i>
	 *    <i rbs-kb-default="" class="icon-github icon-3x"></i>
	 * </rbs-kb-switch>
	 */
	function rbsKbSwitch(Keyboard)
	{
		return {
			restrict : 'EA',
			link : function (scope, elm)
			{
				var when, whenCounter, def;

				// shortcut rbs-kb-alt="" instead of rbs-kb-when="alt"
				elm.find('[rbs-kb-alt]').each(function () {
					$(this).removeAttr('rbs-kb-alt').attr('rbs-kb-when', 'alt');
				});

				when = elm.find('[rbs-kb-when]');
				whenCounter = 0;
				def = elm.find('[rbs-kb-default]');

				when.each(function ()
				{
					var $el = $(this);
					Keyboard.watch(scope, $el.attr('rbs-kb-when'), function (value, oldValue)
					{
						if (value)
						{
							def.hide();
							$el.show();
							whenCounter++;
						}
						else
						{
							$el.hide();
							if (oldValue === true)
							{
								whenCounter--;
							}
							if (whenCounter === 0)
							{
								def.show();
							}
						}
					});
				});
			}
		};
	}

	var app = angular.module('RbsChange');
	app.service('RbsChange.Keyboard', ['$rootScope', KeyboardService]);
	app.directive('rbsKbSwitch', ['RbsChange.Keyboard', rbsKbSwitch]);
})(window.jQuery);