(function () {

	"use strict";

	var app = angular.module('RbsChange');

	__change.createEditorForModelTranslation('Rbs_Mail_Mail');

	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.module('Rbs_Mail', 'Rbs/Mail', { 'redirectTo': 'Rbs/Mail/Mail/'});

			$delegate.routesForModels([
				'Rbs_Mail_Mail'
			]);
			$delegate.routesForLocalizedModels(['Rbs_Mail_Mail']);
			return $delegate.module(null);
		}]);
	}]);

})();