(function ($)
{
	"use strict";

	function KeyboardService($rootScope)
	{
		$rootScope.__changeKeyboardStatus = {
			'shift': false,
			'ctrl': false,
			'alt': false,
			'meta': false
		};

		function keyDown(event)
		{
			if (event.shiftKey)
			{
				$rootScope.__changeKeyboardStatus.shift = true;
			}
			if (event.ctrlKey)
			{
				$rootScope.__changeKeyboardStatus.ctrl = true;
			}
			if (event.altKey)
			{
				$rootScope.__changeKeyboardStatus.alt = true;
			}
			if (event.metaKey)
			{
				$rootScope.__changeKeyboardStatus.meta = true;
			}
			$rootScope.$digest();
		}

		function keyUp(event)
		{
			if (!event.shiftKey)
			{
				$rootScope.__changeKeyboardStatus.shift = false;
			}
			if (!event.ctrlKey)
			{
				$rootScope.__changeKeyboardStatus.ctrl = false;
			}
			if (!event.altKey)
			{
				$rootScope.__changeKeyboardStatus.alt = false;
			}
			if (!event.metaKey)
			{
				$rootScope.__changeKeyboardStatus.meta = false;
			}
			$rootScope.$digest();
		}

		this.watch = function (scope, key, callback)
		{
			var expr = '__changeKeyboardStatus.' +
				key.replace(/\+/g, ' && __changeKeyboardStatus.').replace(/\-/g, ' && !__changeKeyboardStatus.');
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
			restrict: 'EAC',
			link: function (scope, elm)
			{
				var when, whenCounter, def;

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