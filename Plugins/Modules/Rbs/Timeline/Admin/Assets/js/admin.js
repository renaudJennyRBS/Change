(function () {

	var app = angular.module('RbsChange');


	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

			// Timeline

			. when(
			'/Rbs/Timeline',
			{
				templateUrl : 'Rbs/Timeline/Message/list.twig',
				reloadOnSearch : false
			})

			. when(
			'/Rbs/Timeline/Message',
			{
				templateUrl : 'Rbs/Timeline/Message/list.twig',
				reloadOnSearch : false
			})

			. when(
			'/Rbs/Timeline/Message/:id',
			{
				templateUrl : 'Rbs/Timeline/Message/timeline.twig',
				reloadOnSearch : false
			})

			. when(
			'/Rbs/Timeline/Resume',
			{
				templateUrl : 'Rbs/Timeline/Resume/list.twig',
				reloadOnSearch : false
			})

			. when(
			'/Rbs/Timeline/Resume/:id',
			{
				templateUrl : 'Rbs/Timeline/Resume/resume.twig',
				reloadOnSearch : false
			})
		;
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