(function () {

	"use strict";

	var	app = angular.module('RbsChange'),
		fieldIdCounter = 0;


	registerFieldDirective('Text', '<input type="text" class="form-control"/>', 'input');
	registerFieldDirective('Email', '<input type="email" class="form-control"/>', 'input');
	registerFieldDirective('Url', '<input type="url" class="form-control"/>', 'input');
	registerFieldDirective('Integer', '<input type="number" class="form-control" ng-pattern="/^\\-?[0-9]+$/"/>', 'input');
	registerFieldDirective('Float', '<input type="text" class="form-control" smart-float=""/>', 'input');
	registerFieldDirective('Boolean', '<switch></switch>', 'switch');
	registerFieldDirective('RichText', '<rbs-rich-text-input></rbs-rich-text-input>', 'rbs-rich-text-input');
	registerFieldDirective('Picker', '<div class="document-picker-single"></div>', '.document-picker-single');
	registerFieldDirective('PickerMultiple', '<div class="document-picker-multiple"></div>', '.document-picker-multiple');
	registerFieldDirective('Date', '<date-selector></date-selector>', 'date-selector');
	registerFieldDirective('Price', '<rbs-price-input></rbs-price-input>', 'rbs-price-input');
	registerFieldDirective('Image', '<div class="rbs-uploader image-uploader" storage-name="images" file-accept="image/*"></div>', '.image-uploader');
	registerFieldDirective('File', '<div class="rbs-uploader file-uploader" storage-name="files" file-accept="*"></div>', '.file-uploader');
	registerFieldDirective('SelectFromCollection', '<select class="form-control"></select>', 'select');


	/**
	 * Basic Directive to wrap custom fields.
	 *
	 * <code>
	 *     <rbs-field required="true" label="Property label">...</rbs-field>
	 * </code>
	 */
	app.directive('rbsField', function ()
	{
		return {
			restrict   : 'E',
			replace    : true,
			transclude : true,
			template   : fieldTemplate(''),

			compile : function (tElement, tAttrs, transcludeFn)
			{
				var $lbl = tElement.find('label').first(),
					fieldId = 'rbs_field_' + (++fieldIdCounter),
					required = (tAttrs.required === 'true');
				$lbl.html(tAttrs.label);
				$lbl.attr('for', fieldId);
				return function link (scope, element)
				{
					transcludeFn(scope, function (clone) {
						element.find('.controls').append(clone);
						var $input = element.find('.controls [ng-model]').attr('id', fieldId);
						if (required) {
							element.addClass('required');
							$input.attr('required', 'required');
						}
					});
				};
			}
		};
	});


	function fieldTemplate (contents)
	{
		return '<div class="form-group property">' +
				'<label class="col-lg-3 control-label"></label>' +
				'<div class="col-lg-9 controls">' + contents + '</div>' +
			'</div>';
	}


	function registerFieldDirective (name, tpl, selector)
	{
		app.directive('rbsField' + name, ['RbsChange.Utils', function (Utils)
		{
			return {
				restrict   : 'E',
				replace    : true,
				transclude : true,
				template   : fieldTemplate(tpl + '<div ng-transclude=""></div>'),

				compile : function (tElement, tAttrs) {
					rbsFieldCompile(tElement, tAttrs, selector, Utils);
					return function () {};
				}
			};
		}]);
	}


	/**
	 * Generic compile function for all field Directives.
	 * @param tElement
	 * @param tAttrs
	 * @param inputSelector
	 * @param Utils
	 * @param inputIdSelector
	 */
	function rbsFieldCompile (tElement, tAttrs, inputSelector, Utils)
	{
		if (! tAttrs.property) {
			throw new Error("Missing 'property' attribute on <rbs-field-*/> directive");
		}

		var $lbl = tElement.find('label').first(),
			$ipt = tElement.find(inputSelector).first(),
			fieldId, property, ngModel, p;

		// Determine property's name and ngModel value.
		if ((p = tAttrs.property.indexOf('.')) === -1) {
			property = tAttrs.property;
			ngModel = 'document.' + property;
		} else {
			ngModel = tAttrs.property;
			property = tAttrs.property.substr(p + 1);
		}

		// Bind label and input field (unique 'for' attribute).
		fieldId = 'rbs_field_' + property.replace(/[^a-z0-9]/ig, '_') + '_' + (++fieldIdCounter);
		$lbl.html(tAttrs.label).attr('for', fieldId);
		$ipt.attr('id', fieldId).attr('input-id', fieldId).attr('name', property);

		// Init input field
		$ipt.attr('ng-model', ngModel);

		// Transfer most attributes to the input field
		angular.forEach(tAttrs, function (value, name) {
			if (name === 'required') {
				if (value === 'true' || value === 'required') {
					$ipt.attr('required', 'required');
					tElement.addClass('required');
				}
				tElement.removeAttr(name);
			}
			else if (name === 'inputClass') {
				$ipt.addClass(value);
				tElement.removeAttr(name);
			}
			else if (name === 'label') {
				$ipt.attr('property-label', value);
			}
			else if (shouldTransferAttribute(name)) {
				name = Utils.normalizeAttrName(name);
				$ipt.attr(name, value);
				tElement.removeAttr(name);
			}
		});
	}


	function shouldTransferAttribute (name)
	{
		return name !== 'id'
			&& name !== 'class'
			&& name !== 'property'
			&& name !== 'label'
			&& name !== 'ngHide' && name !== 'dataNgHide'
			&& name !== 'ngShow' && name !== 'dataNgShow'
			&& name !== 'ngIf' && name !== 'dataNgIf'
			&& name !== 'ngSwitchWhen' && name !== 'dataNgSwitchWhen'
			&& name.charAt(0) !== '$';
	}

})();
