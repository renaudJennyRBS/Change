(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * Routes and URL definitions.
	 */
	app.config(['$provide', function ($provide)
	{
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate)
		{
			$delegate.model('Rbs_Tag')
				.route('home', 'Rbs/Tag', { 'redirectTo': 'Rbs/Tag/Tag/'})
				.route('myTags', 'Rbs/Tag/MyTags/', { 'templateUrl': 'Document/Rbs/Tag/Tag/myTags-list.twig'});
			$delegate.routesForModels(['Rbs_Tag_Tag']);
			return $delegate;
		}]);
	}]);


	// Register default editors:
	// Do not declare an editor here if you have an 'editor.js' for your Model.
	__change.createEditorForModel('Rbs_Tag_Tag');


	//-------------------------------------------------------------------------
	//
	// Configuration and handlers.
	//
	//-------------------------------------------------------------------------


	/**
	 * Attach handlers:
	 * - create new tags in a Document's Editor (pre-save).
	 * - affect tags in a Document's Editor (post-save).
	 * - build predicates for "filter:hasTag" in <rbs-document-list/>.
	 */
	app.run(['RbsChange.TagService', 'RbsChange.Events', 'RbsChange.i18n', '$rootScope', '$q', '$timeout', function (TagService, Events, i18n, $rootScope, $q, $timeout)
	{
		// Filter 'hasTag' for <rbs-document-list/>.
		$rootScope.$on(Events.DocumentListApplyFilter, function (event, args) {
			var	filter = args.filter,
				predicates = args.predicates,
				p, filterName, filterValue, tags;

			p = filter.indexOf(':');
			if (p === -1) {
				return;
			}

			filterName = filter.substring(0, p);
			if (filterName !== 'hasTag') {
				return;
			}

			filterValue = filter.substring(p+1);
			tags = filterValue.split(/,/);
			for (p=0 ; p<tags.length ; p++) {
				predicates.push({
					"op"  : "hasTag",
					"tag" : parseInt(tags[p], 10)
				});
			}
		});


		function affectTagToDocuments (tag, documents)
		{
			var promises = [];
			angular.forEach(documents, function (doc) {
				promises.push(TagService.addTagToDocument(tag, doc));
			});
			return $q.all(promises);
		}


		// Attach 'drop' handler on every <rbs-document-list/> to allow setting a Tag to the selected documents
		// with drag and drop.
		$(document).on({

			'dragover.rbs.tag' : function (e) {
				e.dataTransfer.dropEffect = "copy";
				e.preventDefault();
				e.stopPropagation();
			},

			'drop.rbs.tag' : function (e) {
				e.dataTransfer.dropEffect = "copy";
				e.preventDefault();
				e.stopPropagation();
				var	tag = e.dataTransfer.getData('Rbs/Tag'),
					documents = $(this).isolateScope().selectedDocuments;
				if (tag && documents && documents.length) {
					$timeout(function () {
						affectTagToDocuments(JSON.parse(tag), documents).catch(
							function () {
								// TODO Notify user.
							}
						);
					});
				}
			}

		}, 'rbs-document-list');

	}]);


	//-------------------------------------------------------------------------
	//
	// TagService
	//
	//-------------------------------------------------------------------------


	app.service('RbsChange.TagService', ['$rootScope', 'RbsChange.REST', 'RbsChange.Utils', '$q', '$http', function ($rootScope, REST, Utils, $q, $http) {

		function getIdArray (data) {
			var ids = [];
			angular.forEach(data, function (item) {
				ids.push(item.id);
			});
			return ids;
		}

		return {

			'getList' : function (defered) {
				var tags = [];
				var tagQuery = {
					"model": "Rbs_Tag_Tag",
					"where": {
						"and": [
							{
								"or" : [
									{
										"op" : "eq",
										"lexp" : {
											"property" : "authorId"
										},
										"rexp" : {
											"value" : $rootScope.user.id
										}
									},
									{
										"op" : "eq",
										"lexp" : {
											"property" : "userTag"
										},
										"rexp" : {
											"value" : 0
										}
									}
								]
							},
							{
								"or" : [
									{
										"op" : "eq",
										"lexp" : {
											"property" : "module"
										},
										"rexp" : {
											"value" : $rootScope.rbsCurrentPluginName
										}
									},
									{
										"op" : "isNull",
										"exp" : {
											"property" : "module"
										}
									}
								]
							}
						]
					},
					"order": [
						{
							"property": "userTag",
							"order": "desc"
						},
						{
							"property": "label",
							"order": "asc"
						}
					],
					"offset": 0
				};

				REST.query(tagQuery, {'column': ['color','userTag'], 'limit': 1000}).then(function (result) {
					angular.forEach(result.resources, function (r) {
						tags.push(r);
					});
					if (defered) {
						defered.resolve(tags);
					}
				});
				return tags;
			},

			'create' : function (tag) {
				tag.model = 'Rbs_Tag_Tag';
				tag.id = Utils.getTemporaryId();
				var promise = REST.save(tag);
				promise.then(function (created) {
					angular.extend(tag, created);
					delete tag.unsaved;
				});
				return promise;
			},

			'setDocumentTags' : function (doc, tags) {
				var q = $q.defer();
				$http.post(doc.getTagsUrl(), {"ids": getIdArray(tags)}, REST.getHttpConfig())
					.success(function (data) {
						q.resolve(doc);
					})
					.error(function errorCallback (data, status) {
						data.httpStatus = status;
						q.reject(data);
					});
				return q.promise;
			},

			'addTagToDocument' : function (tag, doc) {
				var q = $q.defer();
				$http.put(doc.getTagsUrl(), {"addIds": [tag.id]}, REST.getHttpConfig())
					.success(function (data) {
						if (!angular.isArray(doc.META$.tags)) {
							doc.META$.tags = [];
						}
						doc.META$.tags.push(tag);
						q.resolve(doc);
					})
					.error(function errorCallback (data, status) {
						data.httpStatus = status;
						q.reject(data);
					});
				return q.promise;
			}

		};

	}]);

	app.controller('Rbs_Tag_Tag_MyTagsController', ['$rootScope', '$scope', function ($rootScope, $scope) {
		$scope.filter = {name: "group", operator: "AND", parameters : {},
			filters: [
				{
					name: "userTag",
					parameters: {
						propertyName: "userTag", operator: "eq", value: true
					}
				},
				{
					name: "authorId",
					parameters: {
						propertyName: "authorId", operator: "eq", value: $rootScope.user.id
					}
				}
			]
		};
	}]);

	app.controller('Rbs_Tag_Tag_TagsController', ['$rootScope', '$scope', function ($rootScope, $scope) {

		$scope.filter = {name: "group", operator: "AND", parameters : {},
			filters: [
				{
					name: "userTag",
					parameters: {
						propertyName: "userTag", operator: "eq", value: false
					}
				}
			]
		};
	}]);

})(window.jQuery);