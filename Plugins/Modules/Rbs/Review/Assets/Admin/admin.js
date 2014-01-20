(function () {

	"use strict";

	var app = angular.module('RbsChange');

	__change.createEditorForModel('Rbs_Review_Review');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.module('Rbs_Review', 'Rbs/Review', { 'redirectTo': 'Rbs/Review/Review/'});

			$delegate.routesForModels([
				'Rbs_Review_Review'
			]);
			return $delegate.module(null);
		}]);
	}]);

})();