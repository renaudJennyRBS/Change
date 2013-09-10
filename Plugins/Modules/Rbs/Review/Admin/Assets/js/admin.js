(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model(null)
				.route('home', 'Rbs/Review', { 'redirectTo': 'Rbs/Review/Review/'});

			$delegate.routesForModels([
				'Rbs_Review_Review'
			]);
			return $delegate;
		}]);
	}]);

	__change.createEditorForModel('Rbs_Review_Review');

})();