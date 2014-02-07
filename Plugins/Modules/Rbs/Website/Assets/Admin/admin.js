(function () {

	"use strict";

	var app = angular.module('RbsChange');

	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModelTranslation('Rbs_Website_Website');
	__change.createEditorForModelTranslation('Rbs_Website_Topic');

	app.run(['$templateCache', '$rootScope', 'RbsChange.REST', 'RbsChange.Breadcrumb',
		function ($templateCache, $rootScope, REST, Breadcrumb) {
			// Template for menu items in pickers.
			$templateCache.put('picker-item-Rbs_Menu_Item.html', '(=item.title=)(=item.titleKey=)');

			// Update Breadcrumb.
			$rootScope.$on('Change:UpdateBreadcrumb', function (event, entriesArray, current) {
				if (current)
				{
					updateBreadcrumb(entriesArray, current, REST, Breadcrumb);
				}
			});
		}]);

	/**
	 * Updates the Breadcrumb when the default implementation is not the desired behavior.
	 */
	function updateBreadcrumb(entriesArray, current, REST, Breadcrumb) {
		if (current.route.relatedModelName === 'Rbs_Website_StaticPage' && current.route.ruleName === 'form') {
			REST.resource('Rbs_Website_StaticPage', current.params.id, current.params.LCID).then(function(page) {
				var website = page.website;
				if (angular.isObject(website)) {
					var webSiteEntry = Breadcrumb.getEntryByPath('/Rbs/Website/Website/' +  website.id + '/' +  website.refLCID);
					webSiteEntry.label = website.label;
					entriesArray.push(webSiteEntry);

					var webSiteStructureEntry = Breadcrumb.getEntryByPath('/Rbs/Website/Website/' +  website.id + '/' +  website.refLCID + '/Structure/');
					entriesArray.push(webSiteStructureEntry);

					Breadcrumb.refreshPageTitle();
				}
			});
		}
	}

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {
			$delegate.module('Rbs_Website', 'Rbs/Website/', { 'redirectTo': 'Rbs/Website/Browse/' })
			;
			$delegate.routesForLocalizedModels([
				['Rbs_Website_Website', 'form', 'workflow', 'timeline', 'urls'],
				['Rbs_Website_Topic', 'form', 'workflow', 'timeline', 'urls'],
				['Rbs_Website_StaticPage', 'form', 'workflow', 'timeline', 'urls'],
				['Rbs_Website_FunctionalPage', 'form', 'workflow', 'timeline', 'urls'],
				['Rbs_Website_Menu', 'form', 'workflow', 'timeline', 'urls']
			]);

			$delegate.model('Rbs_Website_Website')
				.route('list', 'Rbs/Generic/Website/', {templateUrl: 'Document/Rbs/Website/Website/list.twig', labelKey:'m.rbs.website.admin.website_list | ucf'})
				.route('browse', 'Rbs/Website/Browse/', {controller: 'Rbs_Website_WebsiteSelector', template: '<div></div>'})
				.route('structure', 'Rbs/Website/Website/:id/:LCID/Structure/', {
					templateUrl: 'Document/Rbs/Website/Website/browseStructure.twig',
					labelKey:'m.rbs.website.admin.breadcrumb_structure | ucf'})
				.route('menus', 'Rbs/Website/Website/:id/:LCID/Menus/', {
					templateUrl: 'Document/Rbs/Website/Website/browseMenus.twig',
					labelKey:'m.rbs.website.admin.breadcrumb_menus | ucf'})
				.route('functions', 'Rbs/Website/Website/:id/:LCID/Functions/', {
					templateUrl: 'Document/Rbs/Website/Website/browseFunctions.twig',
					labelKey:'m.rbs.website.admin.breadcrumb_functions | ucf'})
			;

			$delegate.model('Rbs_Website_Topic')
				.route('selector', 'Rbs/Website/Topic/Browse/', {controller: 'Rbs_Website_WebsiteSelector', template: '<div></div>'})
				.route('functions', 'Rbs/Website/Topic/:id/:LCID/Functions/', 'Document/Rbs/Website/SectionPageFunction/list.twig')
			;

			$delegate.model('Rbs_Website_StaticPage')
				.route('selector', 'Rbs/Website/StaticPage/Browse/', {controller: 'Rbs_Website_WebsiteSelector', template: '<div></div>'})
			;

			$delegate.model('Rbs_Website_FunctionalPage')
				.route('selector', 'Rbs/Website/FunctionalPage/Browse/', {controller: 'Rbs_Website_WebsiteSelector', template: '<div></div>'})
			;

			$delegate.model('Rbs_Website_Menu')
				.route('new', 'Rbs/Website/Website/:website/:LCID/Menus/new', {
					templateUrl: 'Document/Rbs/Website/Menu/form.twig',
					labelKey:'m.rbs.admin.adminjs.new_resource | ucf'})
				.route('selector', 'Rbs/Website/Menu/Browse/', {
					controller: 'Rbs_Website_WebsiteSelector',
					options: {view:'menus'},
					template: '<div></div>'})
			;
			return $delegate.module(null);
		}]);
	}]);
})();