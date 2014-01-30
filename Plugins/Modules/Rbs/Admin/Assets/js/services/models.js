(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.service('RbsChange.Models', ['$filter', 'RbsChange.REST', 'RbsChange.i18n', function ($filter, REST, i18n) {

		var allModels = [], shouldLoad = true;

		function loadModelsIfNeeded () {
			if (shouldLoad) {
				shouldLoad = false;
				REST.call(REST.getBaseUrl('Rbs/ModelsInfo')).then(function (models) {
					// Copy loaded models into 'allModels', keeping same reference to 'allModels' variable.
					angular.forEach(models, function (model) {
						allModels.push(model);
					});
				});
			}
		}

		// Public API
		return {

			getAll : function () {
				loadModelsIfNeeded();
				return allModels;
			},

			getModelLabel : function (name) {

				var label = null;
				if (!shouldLoad)
				{
					angular.forEach(allModels, function (model) {
						if (model.name == name)
						{
							label = model.label;
						}
					});
				}

				if (label === null)
				{
					var labArray = name.split('_');
					label = i18n.trans('m.' + labArray[0].toLowerCase() + '.' + labArray[1].toLowerCase() + '.documents.' + labArray[2].toLowerCase());
				}

				return label;
			}

		};

	}]);

})();