(function () {

	"use strict";

	var app = angular.module('RbsChange'),
		INDEX_FUNCTION_CODE = 'Rbs_Website_Section';

	/**
	 *
	 * @param scope
	 * @param $q
	 * @param REST
	 * @param Query
	 * @param ArrayUtils
	 * @param NotificationCenter
	 * @param ErrorFormatter
	 * @constructor
	 */
	function SectionFunctionsManagerDirective($q, REST, Query, ArrayUtils, NotificationCenter, ErrorFormatter)
	{
		return {
			restrict    : 'E',
			templateUrl : 'Rbs/Website/section-functions-manager.twig',
			replace     : false,
			scope       : { 'section' : '=' },

			link : function (scope)
			{
				/**
				 * Loads the Functions implemented in the given Section.
				 *
				 * This methods populates the following objects in the scope:
				 * - sectionPageFunctionList : array of all the implemented Functions in the given Section
				 *
				 * @param section
				 * @param sortColumn
				 * @param sortDesc
				 */
				function loadSectionPageFunctions (section, sortColumn, sortDesc)
				{
					var query = Query.simpleQuery('Rbs_Website_SectionPageFunction', 'section', section.id),
						p;

					query.limit = 500;
					query.offset = 0;
					if (sortColumn) {
						query.order = [{ 'property': sortColumn, 'order': sortDesc ? 'desc' : 'asc' }];
					}
					p = REST.query(query, {"column": ['functionCode', 'page']});
					p.then(function (result)
					{
						scope.sectionPageFunctionList = result.resources;
						angular.forEach(scope.sectionPageFunctionList, function (spf) {
							spf.label = scope.getFunctionLabel(spf.functionCode);
						});
					});
					return p;
				}


				/**
				 * Loads the pages that can be used to implement a function for the given Section.
				 *
				 * These pages are:
				 * - the child pages of the current section
				 * - all the FunctionalPages attached to the current website
				 *
				 * This methods populates the following objects in the scope:
				 * - staticPages     : array of StaticPage documents
				 * - functionalPages : array of FunctionalPage documents
				 *
				 * @param section Rbs_Website_Section document.
				 *
				 * @returns {all|*|all|Promise|all}
				 */
				function loadPages (section)
				{
					var promises = [],
						query,
						p;

					// Load StaticPages
					query = Query.treeChildrenQuery('Rbs_Website_StaticPage', section.id);
					query.sort = [{
						'property' : 'label'
					}];
					p = REST.query(query);
					p.then(function (result)
					{
						scope.staticPages = result.resources;
					});
					promises.push(p);

					// Load FunctionalPages
					query = Query.simpleQuery('Rbs_Website_FunctionalPage', 'website', section.model === "Rbs_Website_Website" ? section.id : section.website.id);
					query.sort = [{
						'property' : 'label'
					}];
					p = REST.query(query);
					p.then(function (result)
					{
						scope.functionalPages = result.resources;
					});
					promises.push(p);

					p = $q.all(promises);
					p.then(function () {
						scope.allPages = [];
						ArrayUtils.append(scope.allPages, scope.staticPages);
						ArrayUtils.append(scope.allPages, scope.functionalPages);
					});

					return p;
				}


				/**
				 * Loads the whole list of available Functions.
				 *
				 * This methods populates the following objects in the scope:
				 * - allFunctions : array of all the available Functions
				 *
				 * @returns {*}
				 */
				function loadAllFunctions ()
				{
					var p = REST.call(REST.getBaseUrl('Rbs/Website/FunctionsList'));
					p.then(function (functions) {
						scope.allFunctions = functions;
						scope.allFunctions.unshift({
							'code' : INDEX_FUNCTION_CODE,
							'label' : INDEX_FUNCTION_CODE,
							'usage' : 0
						});
					});
					return p;
				}


				/**
				 * Tells whether a Function is implemented in the current Section or not.
				 *
				 * @param functionCode
				 * @returns {boolean}
				 */
				function isFunctionImplemented (functionCode)
				{
					var i;
					for (i=0 ; i<scope.sectionPageFunctionList.length ; i++) {
						if (scope.sectionPageFunctionList[i].functionCode === functionCode) {
							return true;
						}
					}
					return false;
				}


				/**
				 * Creates the list of unimplemented Functions in the current Section.
				 *
				 * This methods populates the following objects in the scope:
				 * - unimplementedFunctions : array of all the unimplemented Functions in the current Section.
				 */
				function initUnimplementedFunctions ()
				{
					scope.unimplementedFunctions.length = 0;

					angular.forEach(scope.allFunctions, function (f)
					{
						if (! isFunctionImplemented(f.code)) {
							scope.unimplementedFunctions.push(f);
						}
					});
				}


				/**
				 * Retrieve a Function from its code.
				 *
				 * @param functionCode
				 * @returns {*}
				 */
				function getFunctionByCode (functionCode)
				{
					var i;
					for (i=0 ; i<scope.allFunctions.length ; i++) {
						if (scope.allFunctions[i].code === functionCode) {
							return scope.allFunctions[i];
						}
					}
					return null;
				}


				function preparePageSelectionForFunction (functionCode)
				{
					var p = REST.call(REST.getBaseUrl('Rbs/Website/PagesForFunction'), {"function": functionCode});
					p.then(function (pages)
					{
						function setReadyForFunctionFlag (pages, readyPages)
						{
							angular.forEach(pages, function (p)
							{
								var i;
								p.readyForFunction = false;
								for (i=0 ; i<readyPages.length ; i++) {
									if (readyPages[i].id === p.id) {
										p.readyForFunction = true;
									}
								}
							});
						}

						setReadyForFunctionFlag(scope.functionalPages, pages);
						setReadyForFunctionFlag(scope.staticPages, pages);
					});

					return p;
				}


				//
				// --- Scope methods and data
				//


				scope.sectionPageFunctionList = [];
				scope.unimplementedFunctions = [];
				scope.allFunctions = [];
				scope.newFunction = null;
				scope.showAllPages = false;


				scope.getFunctionLabel = function (functionCode)
				{
					var f = getFunctionByCode(functionCode);
					return f ? f.label : '**' + functionCode + '**';
				};


				scope.changePage = function (spf)
				{
					preparePageSelectionForFunction(spf.functionCode).then(function ()
					{
						scope.newFunction = spf;
					});
				};


				scope.implementFunction = function (func)
				{
					preparePageSelectionForFunction(func.code).then(function ()
					{
						scope.newFunction = func;
					});
				};


				scope.closePageSelection = function ()
				{
					scope.newFunction = null;
				};


				scope.selectPage = function (p)
				{
					var spf;
					if (scope.newFunction.model && scope.newFunction.model === 'Rbs_Website_SectionPageFunction')
					{
						spf = scope.newFunction;
					}
					else
					{
						spf = REST.newResource('Rbs_Website_SectionPageFunction');
						spf.section = scope.section.id;
						spf.functionCode = scope.newFunction.code;
					}

					spf.page = p.id;

					REST.save(spf).then(
						// Success
						function () {
							scope.closePageSelection();
							loadSectionPageFunctions(scope.section).then(initUnimplementedFunctions);
						},
						// Error
						function (error) {
							console.log("error: ", error);
						}
					);
				};


				scope.removeFunction = function (spf)
				{
					REST['delete'](spf).then(function ()
					{
						loadSectionPageFunctions(scope.section).then(initUnimplementedFunctions);
					});
				};


				/**
				 * Initializes everything: available functions, current section's data, child pages, used functions...
				 */
				scope.$watch('section', function (section)
				{
					if (section) {
						scope.document = section;
						loadAllFunctions().then(function ()
						{
							$q.all([
								loadPages(section),
								loadSectionPageFunctions(section)
							]).then(initUnimplementedFunctions);
						});
					}
				});
			}
		};
	}


	app.directive(
		'rbsSectionFunctionsManager',
		[
			'$q',
			'RbsChange.REST',
			'RbsChange.Query',
			'RbsChange.ArrayUtils',
			'RbsChange.NotificationCenter',
			'RbsChange.ErrorFormatter',
			SectionFunctionsManagerDirective
		]
	);

})();