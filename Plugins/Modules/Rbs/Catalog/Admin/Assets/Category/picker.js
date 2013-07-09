(function ($)
{
	if ($('#document-picker-backdrop').length === 0)
	{
		$('body').append('<div id="document-picker-backdrop"/>');
	}

	var app = angular.module('RbsChange');
	app.directive('rbsCatalogCategoryPicker',
		['RbsChange.Modules', 'RbsChange.Clipboard', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.ArrayUtils',
			function (Modules, Clipboard, Breadcrumb, MainMenu, ArrayUtils)
			{
				return {
					restrict: 'EAC',
					templateUrl: 'Rbs/Catalog/Category/picker.twig',
					scope: '@',

					link: function (scope, elm, attrs)
					{
						scope.fieldLabel = attrs.fieldLabel;
						scope.documents = [];

						// Clipboard

						scope.clipboardValues = Clipboard.values;
						var first = scope.clipboardValues[0];
						if (first)
						{
							scope.clipboardFirstLabel = ' (' + Modules.models[first.model] + ' : ' + first.label + ')';
						}
						else
						{
							scope.clipboardFirstLabel = '';
						}

						scope.getFromClipboard = function ()
						{
							scope.selectDocuments(Clipboard.getItems(true));
						};

						// Selection

						scope.$watch('pickerListUrl', function ()
						{
							if (scope.pickerListUrl)
							{
								$('#document-picker-backdrop').show();
							}
							else
							{
								$('#document-picker-backdrop').hide();
							}
						});

						scope.openSelector = function ()
						{
							Breadcrumb.freeze();
							MainMenu.freeze();
							scope.selectorTitle = attrs.selectorTitle;
							scope.pickerListUrl = 'Rbs/Catalog/Category/picker-list.twig?model=' + attrs.acceptedModel;
						};

						scope.closeSelector = function ()
						{
							Breadcrumb.unfreeze();
							MainMenu.unfreeze();
							scope.pickerListUrl = null;
						};

						scope.selectDocuments = function (docs)
						{
							var value;
							if (angular.isArray(scope.documents))
							{
								value = scope.documents;
								angular.forEach(docs, function (doc)
								{
									if (ArrayUtils.inArray(doc, scope.documents) === -1)
									{
										value.push(doc);
									}
								});
								scope.documents = value;
							}
							else
							{
								scope.documents = docs;
							}
						};

						scope.replaceWithDocuments = function (docs)
						{
							scope.documents = docs;
						};

						scope.picker = {
							"replaceWithDocuments": function (docs)
							{
								scope.replaceWithDocuments(docs);
							},
							"selectDocuments": function (docs)
							{
								scope.selectDocuments(docs);
							}
						};

						scope.clear = function ()
						{
							scope.documents = [];
						};

						scope.isEmpty = function ()
						{
							return !scope.documents || scope.documents.length == 0;
						};

						scope.performAdding = function () {
							var docIds = [];
							for (var i in scope.documents)
							{
								docIds.push(scope.documents[i].id);
							}
							scope[attrs.addingMethodName](docIds);
							scope.documents = [];
						};
					}
				};
			}]);
})(window.jQuery);