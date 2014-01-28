(function ()
{
	"use strict";

	/**
	 * @param $http
	 * @param REST
	 * @param Utils
	 * @constructor
	 */
	function Editor($http, REST, Utils)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Mail/Mail/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.addSubstitutionVariable = function (variable)
				{
					if (scope.document.subject)
					{
						scope.document.subject += '{' + variable + '}';
					}
					else
					{
						scope.document.subject = '{' + variable + '}';
					}
				};

				editorCtrl.init('Rbs_Mail_Mail');
			}
		};
	}

	Editor.$inject = ['$http', 'RbsChange.REST', 'RbsChange.Utils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsMailMail', Editor);
})();