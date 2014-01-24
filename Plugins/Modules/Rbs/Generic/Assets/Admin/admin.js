(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.run(['$templateCache', '$rootScope', '$location', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.UrlManager', function ($templateCache, $rootScope, $location, REST, i18n, urlManager)
	{
		// Update Breadcrumb.
		$rootScope.$on('Change:UpdateBreadcrumb', function (event, eventData, breadcrumbData, promises) {
			updateBreadcrumb(eventData, breadcrumbData, promises, REST, i18n, urlManager, $location);
		});
	}]);

	function updateBreadcrumb (eventData, breadcrumbData, promises, REST, i18n, urlManager, $location)
	{
		if (eventData.route.originalPath.indexOf('/Rbs/Generic/Plugins/') == 0)
		{
			breadcrumbData.location.pop();
		}

	}

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
					.route('tree', 'Rbs/Generic/Theme/:id/Templates/', 'Document/Rbs/Theme/Template/list.twig')
				.model('Rbs_Theme_Theme')
				.route('pagetemplates', 'Rbs/Generic/Theme/Browse/?theme=:id&view=PageTemplates', 'Document/Rbs/Theme/Theme/browse.twig')
				.route('mailtemplates', 'Rbs/Generic/Theme/Browse/?theme=:id&view=MailTemplates', 'Document/Rbs/Theme/Theme/browse.twig')

				.routesForLocalizedModels(['Rbs_Geo_AddressField'])
				.routesForModels(['Rbs_Tag_Tag', 'Rbs_Theme_Theme', 'Rbs_Theme_Template', 'Rbs_Website_Website', 'Rbs_Geo_Country', 'Rbs_Geo_Address', 'Rbs_Geo_TerritorialUnit', 'Rbs_Geo_Zone', 'Rbs_Geo_AddressFields', 'Rbs_Geo_Address']);

			return $delegate.module(null);
		}]);
	}]);

})();