(function () {

	var app = angular.module('RbsChange');

	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model(null).route('resumes', 'Rbs/Timeline/Resume', 'Rbs/Timeline/Resume/list.twig')
				.route('resume', 'Rbs/Timeline/Resume/:id', 'Rbs/Timeline/Resume/resume.twig');
			$delegate.model(null).route('home', 'Rbs/Timeline/', { 'redirectTo': 'Rbs/Timeline/Message/'});

			$delegate.routesForModels([
				'Rbs_Timeline_Message'
			]);

			return $delegate;
		}]);
	}]);

	//-------------------------------------------------------------------------
	//
	// Configuration and handlers.
	//
	//-------------------------------------------------------------------------


	/**
	 * Attach handlers:
	 *
	 */
	app.run(['RbsChange.Loading', 'RbsChange.Events', 'RbsChange.i18n', '$rootScope', 'RbsChange.REST', '$http', '$q', '$timeout', function (Loading, Events, i18n, $rootScope, REST, $http, $q, $timeout) {

		// Load messages of the document being edited in an Editor.
		$rootScope.$on(Events.EditorReady, function (event, args) {
			var doc = args.document;
			if (doc.model !== 'Rbs_Timeline_Message') {

			}
		});

		$rootScope.$on(Events.EditorFormButtonBarContents, function (event, args) {
			if (args.document.model !== 'Rbs_Timeline_Message') {
				//args.contents.push('<div>' + i18n.trans('m.rbs.tag.admin.js.tags | ucf')  + '<rbs-tag-selector ng-model="document.META$.tags"></rbs-tag-selector></div>');
				args.contents.push('<timeline docid="' + args.document.id + '"></timeline>');
			}
		});

	}]);

})();