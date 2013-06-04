(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('changeWebsiteMenuFunctions', [ function ()
	{
		return {
			restrict: 'EC',
			templateUrl: 'Change/Website/Menu/directives/menu-functions.twig',

			link: function (scope, elm)
			{
				$(elm).on({
					'dragstart': function (e)
					{
						var draggedEl = $(this);
						e.dataTransfer.setData('Change/MenuItemFunction', JSON.stringify({
							"label": draggedEl.text(),
							"id": draggedEl.data('id')
						}));
						e.dataTransfer.effectAllowed = "copyMove";
					},

					'dragend': function ()
					{
						//draggedEl.removeClass('dragged');
					}
				}, '.menu-item-function');
			}
		};
	}]);
})();