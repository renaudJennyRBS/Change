(function ()
{
	"use strict";

	/**
	 * @example <code><rbs-todo></rbs-todo></code>
	 */
	angular.module('RbsChange').directive('rbsTodo', function ()
	{
		return {
			'restrict': 'E',
			'transclude': true,
			'replace': true,
			'templateUrl': 'Rbs/Dev/todo.twig'
		};
	});
})();