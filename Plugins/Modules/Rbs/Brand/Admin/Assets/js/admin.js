(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Brand')
				.route('home', 'Rbs/Brand', { 'redirectTo': 'Rbs/Brand/Brand/'});

			$delegate.routesForLocalizedModels([
				'Rbs_Brand_Brand'
			]);
			return $delegate;
		}]);
	}]);

	__change.createEditorForModel('Rbs_Brand_Brand');

})();