(function () {

	"use strict";

	var app = angular.module('RbsChange'),
		INDEX_FUNCTION_CODE = 'Rbs_Website_Section',
		queryLimit = 500; // 640K ought to be enough for anybody :)

	/**
	 *
	 * @param $q
	 * @param REST
	 * @param Query
	 * @param ArrayUtils
	 * @param Navigation
	 * @constructor
	 */
	function SectionFunctionsManagerDirective($q, REST, Query, ArrayUtils, Navigation)
	{
		return {
			restrict    : 'E',
			templateUrl : 'Rbs/Website/section-functions-manager.twig',
			replace     : false,
			scope       : { 'section' : '=' },

			link : function linkFn (scope)
			{
				/**
				 * Loads the Functions implemented in the given Section.
				 *
				 * This method populates the following objects in the scope:
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

					query.limit = queryLimit;
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

					return $q.all([ p, loadInheritedFunctions(section) ]);
				}


				/**
				 * Loads the inherited Functions for the given Section (the Functions implemented on the ancestors
				 * of the Section).
				 *
				 * This method populates the following objects in the scope:
				 * - inheritedFunctions : array of all the inherited Functions
				 *
				 * @param section
				 */
				function loadInheritedFunctions (section)
				{
					var p = REST.call(REST.getBaseUrl('Rbs/Website/InheritedFunctions'), {'section' : section.id});
					p.then(function (functions) {
						scope.inheritedFunctions = functions;
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
				 * This method populates the following objects in the scope:
				 * - staticPages     : array of StaticPage documents
				 * - functionalPages : array of FunctionalPage documents
				 *
				 * @param section Rbs_Website_Section document.
				 *
				 * @returns Promise
				 */
				function loadPages (section)
				{
					var promises = [],
						query,
						p;

					// Load StaticPages
					query = Query.treeChildrenQuery('Rbs_Website_StaticPage', section.id);
					query.limit = queryLimit;
					query.offset = 0;
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
					query.limit = queryLimit;
					query.offset = 0;
					query.sort = [{
						'property' : 'label'
					}];
					p = REST.query(query);
					p.then(function (result)
					{
						scope.functionalPages = result.resources;
					});
					promises.push(p);

					// When all the pages are loaded (Static and Functional),
					// we build an Array that contains all the pages.
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
				 * This method populates the following objects in the scope:
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
					var i, result = false;
					for (i=0 ; i<scope.sectionPageFunctionList.length && ! result ; i++) {
						if (scope.sectionPageFunctionList[i].functionCode === functionCode) {
							result = true;
						}
					}
					return result;
				}


				/**
				 * Creates the list of unimplemented Functions in the current Section.
				 *
				 * This method populates the following objects in the scope:
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
				 * Retrieves a Function from its code.
				 *
				 * @param functionCode
				 * @returns {*}
				 */
				function getFunctionByCode (functionCode)
				{
					var i, result = null;
					for (i=0 ; i<scope.allFunctions.length && ! result ; i++) {
						if (scope.allFunctions[i].code === functionCode) {
							result = scope.allFunctions[i];
						}
					}
					return result;
				}


				/**
				 * Prepares the selection of a page for the given function.
				 * This method loads the list of pages that are ready to implement the function.
				 *
				 * This method populates the following objects in the scope:
				 * - readyForFunctionPages    : array of pages ready to implement the function
				 * - notReadyForFunctionPages : array of pages NOT ready to implement the function
				 *
				 * @param functionCode
				 * @returns {*}
				 */
				function preparePageSelectionForFunction (functionCode)
				{
					var p = REST.call(REST.getBaseUrl('Rbs/Website/PagesForFunction'), {"function": functionCode});
					p.then(function (pages)
					{
						scope.readyForFunctionPages = [];
						scope.notReadyForFunctionPages = [];

						// Dispatch pages:
						// - into 'scope.readyForFunctionPages' for pages ready to implement the function
						// - into 'scope.notReadyForFunctionPages' for pages NOT ready to implement the function
						function dispathPages (pages, readyPages)
						{
							angular.forEach(pages, function (p)
							{
								var i, ready = false;
								for (i=0 ; i<readyPages.length && ! ready ; i++) {
									if (readyPages[i].id === p.id) {
										scope.readyForFunctionPages.push(p);
										ready = true;
									}
								}
								if (! ready) {
									scope.notReadyForFunctionPages.push(p);
								}
							});
						}

						dispathPages(scope.functionalPages, pages);
						dispathPages(scope.staticPages, pages);
					});

					return p;
				}


				/**
				 * Initializes the Navigation Context for the current view.
				 *
				 * @param section
				 */
				function initNavigationContext (section)
				{
					Navigation.setContext(scope, 'rbsWebsiteFunctions_' + section.id, "Fonctions pour " + section.label)
						.then(function(context)
						{
							// When context is resolved, we need to update/create the appropriate SectionPageFunction.
							// Context's result is the newly created page.
							// Function code is found in the context's params.
							var page = context.result,
								spf = getSectionPageFunctionByCode(context.params['function']);

							if (! spf) {
								spf = REST.newResource('Rbs_Website_SectionPageFunction');
								spf.section = section.id;
								spf.functionCode = context.params['function'];
							}

							spf.section = section.id;
							spf.page = page.id;
							REST.save(spf).then(
								// Success
								function () {
									scope.closePageSelection();
									loadSectionPageFunctions(section, null, null).then(initUnimplementedFunctions);
								},
								// Error
								function (error) {
									console.log("error: ", error);
									// FIXME Display error message to user.
								}
							);
						});
				}


				/**
				 * Finds an existing SectionPageFunction document with the given functionCode.
				 *
				 * @param functionCode
				 * @returns Rbs_Website_SectionPageFunction
				 */
				function getSectionPageFunctionByCode (functionCode)
				{
					var i, result = null;
					for (i=0 ; i<scope.sectionPageFunctionList.length && ! result ; i++)
					{
						if (scope.sectionPageFunctionList[i].functionCode === functionCode) {
							result = scope.sectionPageFunctionList[i];
						}
					}
					return result;
				}


				/**
				 * Gets (creates if needed) the Rbs_Website_SectionPageFunction document suitable for the implementation
				 * of the given Function.
				 *
				 * @param func
				 * @returns {*}
				 */
				function getSectionPageFunctionDocument (func)
				{
					var spf;
					if (func.model === 'Rbs_Website_SectionPageFunction')
					{
						spf = func;
					}
					else
					{
						spf = REST.newResource('Rbs_Website_SectionPageFunction');
						spf.section = scope.section.id;
						spf.functionCode = func.code;
					}
					return spf;
				}


				//----------------------------//
				//                            //
				//   Scope methods and data   //
				//                            //
				//----------------------------//


				scope.sectionPageFunctionList = [];
				scope.unimplementedFunctions = [];
				scope.allFunctions = [];
				scope.inheritedFunctions = {};
				scope.newFunction = null;
				scope.showAllPages = false;


				// Returns the label of a Function from its code.
				scope.getFunctionLabel = function (functionCode)
				{
					var f = getFunctionByCode(functionCode);
					return f ? f.label : '**' + functionCode + '**';
				};


				// Called when the user wants the change the page attributed to a Function.
				scope.changePage = function (spf)
				{
					preparePageSelectionForFunction(spf.functionCode).then(function ()
					{
						scope.newFunction = spf;
					});
				};


				// Called when the user wants to implement a new Function to the Section.
				scope.implementFunction = function (func)
				{
					preparePageSelectionForFunction(func.code).then(function ()
					{
						scope.newFunction = func;
					});
				};


				scope.hasInheritedFunctions = function ()
				{
					var count = 0;
					// Here we check if the object is empty or not.
					// (angular.forEach does the hasOwnProperty() check).
					angular.forEach(scope.inheritedFunctions, function ()
					{
						count++;
					});
					return count > 0;
				};


				scope.closePageSelection = function ()
				{
					scope.newFunction = null;
				};


				// Called when the user selects a page for the Function to be implemented.
				scope.selectPage = function (p)
				{
					var spf = getSectionPageFunctionDocument(scope.newFunction);
					spf.page = p.id;

					REST.save(spf).then(
						// Success
						function () {
							scope.closePageSelection();
							loadSectionPageFunctions(scope.section, null, null).then(initUnimplementedFunctions);
						},
						// Error
						function (error) {
							console.log("error: ", error);
						}
					);
				};


				// Called when the user wants to remove a Function in the Section.
				scope.removeFunction = function (spf)
				{
					REST['delete'](spf).then(function ()
					{
						loadSectionPageFunctions(scope.section, null, null).then(initUnimplementedFunctions);
					});
				};


				// Initialization:
				// load available functions, current section's data, child pages, used functions...
				scope.$watch('section', function (section)
				{
					if (section) {
						scope.document = section;
						loadAllFunctions().then(function ()
						{
							$q.all([
								loadPages(section),
								loadSectionPageFunctions(section, null, null)
							]).then(function () {
								initUnimplementedFunctions();
								initNavigationContext(section);
							});
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
			'RbsChange.Navigation',
			SectionFunctionsManagerDirective
		]
	);

})();