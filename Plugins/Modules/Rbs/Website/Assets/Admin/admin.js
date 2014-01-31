(function () {

	"use strict";

	var app = angular.module('RbsChange');


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModelTranslation('Rbs_Website_Website');
	__change.createEditorForModelTranslation('Rbs_Website_Topic');


	app.run(['$templateCache', '$rootScope', '$location', 'RbsChange.REST', 'RbsChange.i18n', function ($templateCache, $rootScope, $location, REST, I18n)
	{
		// Template for menu items in pickers.
		$templateCache.put('picker-item-Rbs_Menu_Item.html', '(=item.title=)(=item.titleKey=)');

		// Update Breadcrumb.
		$rootScope.$on('Change:UpdateBreadcrumb', function (event, eventData, breadcrumbData, promises) {
			updateBreadcrumb(eventData, breadcrumbData, promises, REST, I18n, $location);
		});
	}]);


	/**
	 * Updates the Breadcrumb when the default implementation is not the desired behavior.
	 * @param eventData
	 * @param breadcrumbData
	 * @param promises
	 * @param REST
	 * @param $location
	 */
	function updateBreadcrumb (eventData, breadcrumbData, promises, REST, i18n, $location)
	{
		var p, search = $location.search();

		if (eventData.modelName === 'Rbs_Website_StaticPage' || eventData.modelName === 'Rbs_Website_Topic')
		{
			breadcrumbData.location.length = 1;
		}
		else if (eventData.modelName === 'Rbs_Website_Browse')
		{
			// Structure, Menus and Functions

			breadcrumbData.location.length = 1;
			if (search.hasOwnProperty('website'))
			{
				p = REST.resource(search['website']).then(function (website)
				{
					breadcrumbData.resource = website;
					breadcrumbData.resourceModifier = i18n.trans('m.rbs.website.admin.breadcrumb_' + search['view'].toLowerCase() + ' | ucf');
				});
				promises.push(p);
			}
		}
		else if (eventData.route.relatedModelName === 'Rbs_Website_Menu')
		{
			// Menus

			if (eventData.route.params.website)
			{
				breadcrumbData.location.length = 1;
				p = REST.resource(eventData.route.params.website);
				p.then(function (website) {
					breadcrumbData.path.push(website);
					breadcrumbData.path.push([i18n.trans('m.rbs.website.admin.breadcrumb_menus | ucf'), website.url('menus')]);
					if (! breadcrumbData.resource) {
						breadcrumbData.resource = i18n.trans('m.rbs.admin.adminjs.new_resource | ucf');
					}
				});
				promises.push(p);
			}
		}
	}


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.module('Rbs_Website', 'Rbs/Website', { 'redirectTo': 'Rbs/Website/Browse/' })
			;

			$delegate.model('Rbs_Website_Website')
				.route('tree', 'Rbs/Website/Browse/?website=:id&view=Structure', 'Document/Rbs/Website/Website/browse.twig')
				.route('functions', 'Rbs/Website/Browse/?website=:id&view=Functions', 'Document/Rbs/Website/Website/browse.twig')
				.route('menus', 'Rbs/Website/Browse/?website=:id&view=Menus', 'Document/Rbs/Website/Website/browse.twig')
			;

			$delegate.model('Rbs_Website_Topic')
				.route('selector', 'Rbs/Website/Browse/?view=Structure', 'Document/Rbs/Website/Website/browse.twig')
				.route('functions', 'Rbs/Website/Topic/:id/Functions/', 'Document/Rbs/Website/SectionPageFunction/list.twig')
			;

			$delegate.model('Rbs_Website_Section')
				.route('selector', 'Rbs/Website/Browse/?view=Structure', 'Document/Rbs/Website/Website/browse.twig')
			;

			$delegate.routesForLocalizedModels([
				'Rbs_Website_Topic',
				'Rbs_Website_StaticPage',
				'Rbs_Website_FunctionalPage',
				'Rbs_Website_Menu'
			]);

			$delegate.model('Rbs_Website_Menu')
				.route('new', 'Rbs/Website/Website/:website/Menus/new', 'Document/Rbs/Website/Menu/form.twig')
				.route('form', 'Rbs/Website/Website/:website/Menus/:id/:LCID', 'Document/Rbs/Website/Menu/form.twig')
			;

			return $delegate.module(null);
		}]);
	}]);


})();