(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	__change.createEditorForModelTranslation('Rbs_Simpleform_Form');
	__change.createEditorForModelTranslation('Rbs_Simpleform_Field');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Simpleform').route('home', 'Rbs/Simpleform', { 'redirectTo': 'Rbs/Simpleform/Form/'});
			$delegate.routesForLocalizedModels(['Rbs_Simpleform_Form', 'Rbs_Simpleform_Field']);
			return $delegate;
		}]);
	}]);

})();