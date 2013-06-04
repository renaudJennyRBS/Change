describe('Actions', function () {

	var Actions = null,
	    printAction = null,
	    activateAction = null,
	    activateActionCalled = false,
	    activateActionPageId = null,
	    activateActionStatus = null,
	    compareAction = null,
	    compareProductsAction = null,
	    deleteAction = null;

	beforeEach(module('RbsChange'));

	beforeEach(inject(['RbsChange.Actions', function (RbsChangeActions) {
		Actions = RbsChangeActions;

		printAction = {
			name        : 'printAction',
			models      : '*',
			selection   : 1,
		};

		deleteAction = {
			name        : 'deleteAction',
			models      : '*',
			selection   : '+',
		};

		activateAction = {
			name        : 'activatePage',
			models      : 'change_website_page',
			selection   : "+",
			isEnabled   : function ($docs) {
				// Assume this for the tests
				return $docs[0].status != 'PUBLISHABLE';
			},
			execute: ['pageId', 'status', function (pageId, status) {
				activateActionPageId = pageId;
				activateActionStatus = status;
				activateActionCalled = true;
			}]
		};

		compareAction = {
			name        : 'compare',
			models      : '*',
			selection   : 2,
		};

		compareProductsAction = {
			name        : 'compareProducts',
			models      : 'change_catalog_product',
			selection   : '2,4',
		};
	}]));


	it('should NOT allow registering two actions with the same name', function () {

		function addPrintAction () {
			Actions.register(printAction);
		}

		expect(addPrintAction).not.toThrow();
		expect(addPrintAction).toThrow();

	});


	it("should NOT enable an action if the input documents does not match the 'selection' parameter of the action", function () {

		Actions.register(printAction);
		Actions.register(compareAction);
		Actions.register(compareProductsAction);
		Actions.register(deleteAction);

		var page1 = {};
		var page2 = {};
		var page3 = {};

		// 'printAction' accepts only one document
		expect(Actions.isEnabled('printAction', [])).toBeFalsy();
		expect(Actions.isEnabled('printAction', [ page1 ])).toBeTruthy();
		expect(Actions.isEnabled('printAction', [ page1, page2 ])).toBeFalsy();

		// 'compare' accepts only two dcuments
		expect(Actions.isEnabled('compare', [])).toBeFalsy();
		expect(Actions.isEnabled('compare', [ page1 ])).toBeFalsy();
		expect(Actions.isEnabled('compare', [ page1, page2 ])).toBeTruthy();
		expect(Actions.isEnabled('compare', [ page1, page2, page3 ])).toBeFalsy();

		var product1 = { model: 'change_catalog_product' };
		var product2 = { model: 'change_catalog_product' };
		var product3 = { model: 'change_catalog_product' };
		var product4 = { model: 'change_catalog_product' };
		var product5 = { model: 'change_catalog_product' };

		expect(Actions.isEnabled('compareProducts', [ product1 ])).toBeFalsy();
		expect(Actions.isEnabled('compareProducts', [ product1, product2 ])).toBeTruthy();
		expect(Actions.isEnabled('compareProducts', [ product1, product2, product3 ])).toBeTruthy();
		expect(Actions.isEnabled('compareProducts', [ product1, product2, product3, product4 ])).toBeTruthy();
		expect(Actions.isEnabled('compareProducts', [ product1, product2, product3, product4, product5 ])).toBeFalsy();

		// 'delete' requires at least one document
		expect(Actions.isEnabled('deleteAction', [])).toBeFalsy();
		expect(Actions.isEnabled('deleteAction', [ product1 ])).toBeTruthy();
		expect(Actions.isEnabled('deleteAction', [ product1, product2, product3, product4, product5 ])).toBeTruthy();

	});


	it("should NOT enable an action if the input documents does not match the 'models' parameter of the action", function () {

		Actions.register(activateAction);
		Actions.register(compareProductsAction);

		var page = {};
		// 'activatePage' accepts only one 'change_website_page'
		expect(Actions.isEnabled('activatePage', [ page ])).toBeFalsy();
		// Wait! page is not yet a 'change_website_page'
		page.model = 'change_website_page';
		expect(Actions.isEnabled('activatePage', [ page ])).toBeTruthy();
		// But if page is 'PUBLISHABLE', the 'activate' action should be disabled (see activate's isEnabled() method).
		page.status = 'PUBLISHABLE';
		expect(Actions.isEnabled('activatePage', [ page ])).toBeFalsy();

	});


	it("should call the Action's execute method with the correct parameters", function () {

		Actions.register(activateAction);

		expect(activateActionCalled).toBeFalsy();
		Actions.execute('activatePage');
		expect(activateActionCalled).toBeTruthy();
		expect(activateActionPageId).toBe(null);
		expect(activateActionStatus).toBe(null);

		Actions.execute('activatePage', {'pageId':42, 'status':'PUBLISHABLE'});
		expect(activateActionPageId).toBe(42);
		expect(activateActionStatus).toBe('PUBLISHABLE');

		Actions.execute('activatePage', {'status':'ACTIVE', 'pageId':39});
		expect(activateActionPageId).toBe(39);
		expect(activateActionStatus).toBe('ACTIVE');

		Actions.execute('activatePage');
		expect(activateActionPageId).toBe(null);
		expect(activateActionStatus).toBe(null);

	});


	it("should return all the actions available for the given models", function () {

		Actions.reset();
		Actions.register(printAction);
		Actions.register(compareAction);
		Actions.register(compareProductsAction);
		Actions.register(deleteAction);
		Actions.register(activateAction);

		expect(Actions.getActionsForModels('change_website_page')).toEqual(['activatePage']);
		expect(Actions.getActionsForModels('change_catalog_product')).toEqual(['compareProducts']);

	});


	it("should return all the actions available for all the models", function () {

		Actions.reset();
		Actions.register(printAction);
		Actions.register(compareAction);
		Actions.register(compareProductsAction);
		Actions.register(deleteAction);
		Actions.register(activateAction);

		expect(Actions.getActionsForAllModels().sort()).toEqual(['printAction', 'deleteAction', 'compare'].sort());

	});

});