(function ($) {

	jQuery.event.props.push('dataTransfer');

	/**
	 * @name dropFilesZone
	 * @description A zone that can handle HTML5 files upload.
	 *
	 * <code>
	 * <div data-drop-files-zone=".dropped-files-info-selector"></div>
	 * </code>
	 */
	angular.module('RbsChange').directive('dropFilesZone', ['$compile', 'RbsChange.ArrayUtils', '$timeout', function ($compile, ArrayUtils, $timeout) {

		return {
			restrict : 'A',

			scope: true,

			link : function (scope, elm, attrs) {

				scope.files = [];
				scope.uploading = false;

				var template = '<div>' +
					'<div class="files-count" ng-show="!uploading"><span ng-pluralize count="files.length" when="{\'0\':\'Aucun fichier\',\'one\':\'Un fichier\',\'other\':\'{} fichiers\'}"></span> à envoyer</div>' +
					'<div class="files-count" ng-show="uploading">Transfert de <span ng-pluralize count="files.length" when="{\'one\':\'1 fichier\',\'other\':\'{} fichiers\'}"></span>...</div>' +
					'<span class="badge" ng-repeat="file in files | limitTo:2">{{file.name | ellipsis:30:\'center\'}}</span>' +
					'<div ng-show="files.length > 2">et {{files.length - 2}} de plus.</div>' +
					'<div class="btn-toolbar">' +
					' <button type="button" class="btn btn-xs btn-primary" ng-disabled="files.length == 0 || uploading" ng-click="upload($event)"><i class="icon-upload icon-white"></i> Envoyer</button>' +
					' <button type="button" class="btn btn-xs" ng-disabled="files.length == 0 || uploading" ng-click="cancel($event)"><i class="icon-remove-circle"></i> Effacer</button>' +
					' <button type="button" class="btn btn-xs" ng-click="close($event)"><i class="icon-remove"></i> Annuler</button>' +
					'</div>' +
					'</div>';

				var infoSelector = attrs.dropFilesZone;
				var infoElm = (infoSelector && infoSelector.length) ? elm.find(infoSelector) : elm;
				$compile(template)(scope, function (clone) {
					infoElm.html(clone);
				});
				infoElm.hide();

				function dropHandler (e) {

					var files = e.dataTransfer.files;
					// For each file
					$.each(files, function(index, file) {
						if (files[index].type.match('image.*')) {
							scope.files.push(files[index]);
						}
					});

					if (scope.files.length > 0) {
						infoElm.show();
					}

					scope.$apply();

					return false;
				}

				scope.upload = function (e) {
					e.stopPropagation();
					console.log('Uploading ' + scope.files.length + ' files... (fake)'); // TODO
					scope.uploading = true;
					$timeout(function () {
						scope.close();
						scope.uploading = false;
					}, 2000);
				};

				scope.cancel = function (e) {
					e.stopPropagation();
					ArrayUtils.clear(scope.files);
				};

				scope.close = function (e) {
					if (e) {
						scope.cancel(e);
					}
					infoElm.hide();
				};

				$(elm).bind('drop', dropHandler);
			}
		};
	}]);

})(window.jQuery);