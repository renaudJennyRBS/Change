(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('changeWebsiteMenuCard', [ function ()
	{
		function getDraggedElement(event)
		{
			var data = event.dataTransfer.getData('Rbs/Document');
			if (!data)
			{
				data = event.dataTransfer.getData('Rbs/MenuItemFunction');
			}
			try
			{
				return JSON.parse(data);
			}
			catch (err)
			{
				console.error(err);
			}
			return null;
		}

		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Website/Menu/directives/menu-card.twig',
			// Create isolated scope.
			scope: { document: '=' },

			link: function (scope, elm, attrs)
			{
				scope.document.items = scope.document.items || [];

				$(elm).on({
					'dragenter': function (event)
					{
						event.preventDefault();
						event.stopPropagation();
					},

					'dragleave': function (event)
					{
						event.preventDefault();
						event.stopPropagation();
					},

					'dragover': function (event)
					{
						event.dataTransfer.dropEffect = "copy";
						event.preventDefault();
						event.stopPropagation();
					},

					'drop': function (event)
					{
						event.preventDefault();
						event.stopPropagation();

						var doc = getDraggedElement(event);

						if (!scope.document.items)
						{
							scope.document.items = [];
						}
						scope.$apply(function ()
						{
							scope.document.items.push(doc);
						});
					}
				});
			}
		};
	}]);
})();