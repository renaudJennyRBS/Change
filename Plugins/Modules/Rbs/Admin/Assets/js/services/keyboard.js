/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ($)
{
	"use strict";

	/**
	 * @ngdoc service
	 * @name RbsChange.service:Keyboard
	 * @description Service to help special keys detection.
	 *
	 * This Service creates the following object in the `$rootScope`, and updates it in real time
	 * to reflect the keyboard status:
	 * <pre>
	 *   rbsChangeKeyboardStatus = {
	 *     'shift': false,
	 *     'ctrl': false,
	 *     'alt': false,
	 *     'meta': false
	 *   };
	 * </pre>
	 */
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
	 * @ngdoc directive
	 * @id RbsChange.directive:rbs-kb-switch
	 * @name Alt-Ctrl-Shift-Meta keys manager
	 * @restrict EA
	 *
	 * @description
	 * This Directive helps you display different widgets depending on the state of special keys. This is very useful
	 * to display an alternative button when the user holds the Alt-key down for example.
	 *
	 * Use <code>rbs-kb-when=""</code> and <code>rbs-kb-default</code> to build the different combinations.
	 *
	 * @example
	 * <pre>
	 * <rbs-kb-switch>
	 *    <i rbs-kb-when="alt" class="icon-plane icon-3x"></i>
	 *    <i rbs-kb-when="alt+shift" class="icon-ambulance icon-3x"></i>
	 *    <i rbs-kb-default="" class="icon-github icon-3x"></i>
	 * </rbs-kb-switch>
	 * </pre>
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