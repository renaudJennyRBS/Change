(function () {

	"use strict";

	var app = angular.module('RbsChange');

		/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{


			$delegate.module('Rbs_Generic', 'Rbs/Generic', { 'templateUrl': 'Rbs/Generic/settings.twig'})
				.route('Installed', 'Rbs/Generic/Plugins/Installed/', { 'templateUrl': 'Rbs/Plugins/installed-list.twig', 'labelKey':'m.rbs.plugins.admin.installed_list | ucf'})
				.route('Registered', 'Rbs/Generic/Plugins/Registered/', { 'templateUrl': 'Rbs/Plugins/registered-list.twig', 'labelKey':'m.rbs.plugins.admin.registered_list | ucf'})
				.route('New', 'Rbs/Generic/Plugins/New/', { 'templateUrl': 'Rbs/Plugins/new-list.twig', 'labelKey':'m.rbs.plugins.admin.new_list | ucf'})

				.model('Rbs_Theme_Theme')
					.route('browse', 'Rbs/Generic/Theme/Browse/', {controller: 'Rbs_Theme_ThemeSelector', template: '<div></div>'})
					.route('pagetemplates', 'Rbs/Generic/Theme/:id/PageTemplates', {
						templateUrl : 'Document/Rbs/Theme/Theme/browsePageTemplates.twig',
						labelKey : 'm.rbs.theme.admin.breadcrumb_pagetemplates'})
					.route('mailtemplates', 'Rbs/Generic/Theme/:id/MailTemplates', {
						templateUrl : 'Document/Rbs/Theme/Theme/browseMailTemplates.twig',
						labelKey : 'm.rbs.theme.admin.breadcrumb_mailtemplates'})

				.routesForLocalizedModels(['Rbs_Geo_AddressField'])
				.routesForModels(['Rbs_Tag_Tag',
					'Rbs_Theme_Theme', ['Rbs_Theme_Template', 'form', 'workflow', 'timeline'],
					'Rbs_Website_Website',
					'Rbs_Geo_Country', 'Rbs_Geo_Address', 'Rbs_Geo_TerritorialUnit',
					'Rbs_Geo_Zone', 'Rbs_Geo_AddressFields', 'Rbs_Geo_Address']);

			return $delegate.module(null);
		}]);
	}]);

})();