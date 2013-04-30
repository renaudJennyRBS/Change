/**
 * This directive finds the fieldsets and fields in a form during the Angular compilation process.
 * The information is then compiled and stored in the data attributes of the element this directive is bound to.
 *
 * During the 'link' phase, the compiled information is used to build the MainMenu (on the left):
 * fieldsets become sections and the fields are used to display the fields counter as a badge.
 * Labels should be configured with the following attributes:
 * - data-form-section-label (required): section label.
 * - data-form-section-group (optionnal, used to create 'nav-header' elements in the menu): group label.
 *
 * Fieldsets are found with the 'fieldset' selector.
 * Fields are found with their 'label' element.
 * Required fields are identified with the '.required' selector on their parent 'div.control-group'.
 *
<doc:example>
	<form data-form-sections name="form" data-ng-submit="submit()" class="form-horizontal" novalidate>
		<!-- section: id=general, label=Propriétés générales, group=Propriétés -->
		<fieldset data-ng-show="section='general'" data-form-section-label="Propriétés générales" data-form-section-group="Propriétés">
			<div class="control-group required">
				<label class="control-label" for="name">Titre</label>
				...
			</div>
			<div class="control-group">
				<label class="control-label" for="coderef">Code référence</label>
				...
			</div>
		</fieldset>
		<!-- section: id=stock, label=Stock -->
		<fieldset data-ng-show="section='stock'" data-form-section-label="Stock">
			...
		</fieldset>
		...
	</form>
</doc:example>
 */
(function ($) {

	var DIRECTIVE_NAME = 'formSections';

	function formWithSectionsDirectiveFn ($location, MainMenu, Utils, FormsManager) {

		// Used internally to store compiled informations in data attributes.
		var DATA_KEY_NAME = 'chg-form-sections';

		return {

			restrict : 'A',
			priority : 1,

			compile  : function compile (tElement, tAttrs) {

				var entries = [];
				var groups = {};

				tElement.data(DATA_KEY_NAME, entries);

				tElement.find('fieldset').each(function (index, fieldset) {
					var $fs = $(fieldset),
					    fsData = $fs.data(),
					    section,
					    entry;

					if (angular.isDefined(fsData.formSectionGroup) && angular.isUndefined(groups[fsData.formSectionGroup])) {
						groups[fsData.formSectionGroup] = true;
						entries.push({
							'label'   : fsData.formSectionGroup,
							'isHeader': true
						});
					}

					section = fsData.ngShow || $fs.attr('ng-show') || $fs.attr('x-ng-show');
					if (section) {
						var matches = (/section\s*==\s*'([\w\d\-]+)'/).exec(section);
						if (matches.length !== 2) {
							console.error("Could not find section ID on fieldset.");
						}
						section = matches[1];
					} else {
						section = fsData.ngSwitchWhen || $fs.attr('ng-switch-when') || $fs.attr('x-ng-switch-when');
					}

					entry = {
						'label'  : fsData.formSectionLabel,
						'badge'  : {
							'value'    : 0,
							'cssClass' : ''
						}
					};
					if (FormsManager.isCascading()) {
						entry.ngClick = "section='" + section + "'";
						//entry.section = section;
					} else {
						entry.url = Utils.makeUrl($location.absUrl(), {'section': section});
					}

					entries.push(entry);

					$fs.find('div.control-group > label').each(function (index, labelElm) {
						var $lbl = $(labelElm);
						if (angular.isUndefined($lbl.data('notAProperty'))) {
							entry.badge.value++;
							if ($lbl.closest('div.control-group').first().hasClass('required')) {
								entry.badge.cssClass = 'badge-important';
							}
						}
					});
				});

				return function postLink(scope, iElement, iAttrs) {
					MainMenu.build(iElement.data(DATA_KEY_NAME), scope);
				};
			}
		};

	}

	formWithSectionsDirectiveFn.$inject = [
	                                       '$location',
	                                       'RbsChange.MainMenu',
	                                       'RbsChange.Utils',
	                                       'RbsChange.FormsManager'
	                                      ];

	angular.module('RbsChange').directive(DIRECTIVE_NAME, formWithSectionsDirectiveFn);

})(window.jQuery);