//{namespace name=backend/plugins/viison_stripe/order_detail_position_refund}

//{block name="backend/order/view/detail/position" append}
Ext.define('Shopware.apps.ViisonStripe.Order.view.detail.Position', {

	override: 'Shopware.apps.Order.view.detail.Position',

	/**
	 * Add a new event, which is fired by this view.
	 */
	initComponent: function() {
		this.addEvents(
			/**
			 * Event will be fired when the user clicks the 'refund positions' button.
			 *
			 * @event openRefundWindow
			 * @param [Shopware.apps.ViisonStripe.Order.view.detail.Position] grid
			 */
			'openRefundWindow'
		);

		this.callParent(arguments);
	},

	/**
	 * Adds a 'refund' button to the toolbar.
	 *
	 * @return The toolbar created by the parent method, including the refund button.
	 */
	createGridToolbar: function() {
		var toolbar = this.callParent(arguments);

		// Check if the order was payed with Stripe
		if (this.record.getPaymentStore.first().raw.action === 'viison_stripe_payment') {
			// Add the refund button
			this.viisonStripeRefundPositionButton = Ext.create('Ext.button.Button', {
				iconCls: 'sprite-money--minus',
				text: '{s name=order/view/detail/position/refund_button}{/s}',
				disabled: true,
				scope: this,
				handler: function() {
					this.fireEvent('openRefundWindow', this);
				}
			});
			toolbar.add(this.viisonStripeRefundPositionButton);
		}

		return toolbar;
	},

	/**
	 * Adds a new listener to the selection model of the grid, which enables/disables
	 * the refund button based on the number of selected positions.
	 *
	 * @return The position grid created by the parent method.
	 */
	createPositionGrid: function() {
		var positionGrid = this.callParent(arguments);

		// Listen on changes in the selection model
		positionGrid.selModel.addListener('selectionchange', function (model, records) {
			if (this.viisonStripeRefundPositionButton !== undefined) {
				// Enable/disable the refund button based on the selection
				this.viisonStripeRefundPositionButton.setDisabled(records.length === 0);
			}
		}, this);

		return positionGrid;
	}

});
//{/block}

//{block name="backend/order/controller/detail" append}
	// Include the refund model and window
	//{include file="backend/plugins/viison_stripe/order_detail_position_refund/item.js"}
	//{include file="backend/plugins/viison_stripe/order_detail_position_refund/window.js"}

Ext.define('Shopware.apps.ViisonStripe.Order.controller.Detail', {

	override: 'Shopware.apps.Order.controller.Detail',

	/**
	 * Add new events, which are controlled by this controller.
	 */
	init: function() {
		this.control({
			'order-detail-window order-position-panel': {
				openRefundWindow: this.onOpenRefundWindow
			},
			'viison-stripe-refund-window': {
				performRefund: this.onPerformRefund
			}
		});

		this.callParent(arguments);
	},

	/**
	 * Collects the selected positions from the given panel and copies some of its
	 * grid columns. Finally a new refund window is created and displayed.
	 *
	 * @param positionPanel The panel providing the positions.
	 */
	onOpenRefundWindow: function(positionPanel) {
		// Collect all positions, which are currently selected and sum up their total values
		var data = [];
		var total = 0.0;
		Ext.each(positionPanel.orderPositionGrid.selModel.getSelection(), function(record) {
			if (record.get('quantity') <= 0) {
				return;
			}
			data.push({
				id: record.get('id'),
				articleNumber: record.get('articleNumber'),
				articleName: record.get('articleName'),
				quantity: record.get('quantity'),
				price: record.get('price'),
				total: record.get('total')
			});
			total += record.get('total');
		}, this);

		// Create a new store with the collected positions
		var store = Ext.create('Ext.data.Store', {
			model: 'Shopware.apps.ViisonStripe.Order.model.detail.position.refund.Item',
			data: data
		});

		// Copy some of the columns of the position grid
		var gridColumns = [];
		Ext.each(positionPanel.getColumns(positionPanel.orderPositionGrid), function(column) {
			if (['articleNumber', 'articleName', 'quantity', 'price', 'total'].indexOf(column.dataIndex) !== -1) {
				gridColumns.push(column);
			}
		});

		// Create and open a new window with the store, columns and total amount
		var refundWindow = Ext.create('Shopware.apps.ViisonStripe.Order.view.detail.position.refund.Window', {
			orderRecord: positionPanel.record,
			store: store,
			gridColumns: gridColumns,
			total: total
		});
		refundWindow.show();
	},

	/**
	 * Gets all the refund data from the given window and sends it to the
	 * backend controller to perform the refund with Stripe. On success, the refund
	 * window is closed and the internal comment in the communication tab is updated
	 * to reflect the changes maded in the backend.
	 *
	 * @param refundWindow The window, which triggered the refund event.
	 */
	onPerformRefund: function(refundWindow) {
		var values = refundWindow.form.getForm().getValues();

		// Gather some information about the refunded positions
		var positions = [];
		refundWindow.store.each(function(positionRecord) {
			positions.push(positionRecord.getData());
		});

		// Create the refund
		refundWindow.loadMask.show();
		Ext.Ajax.request({
			url: '{url controller="ViisonStripePayment" action="refund"}',
			jsonData: {
				orderId: refundWindow.orderRecord.get('id'),
				amount: refundWindow.total,
				comment: values['comment'],
				positions: positions
			},
			callback: function(options, success, response) {
				// Hide the loading mask
				refundWindow.loadMask.hide();
				// Try to decode the response
				var responseObject = Ext.JSON.decode(response.responseText, true);
				if (success && responseObject !== null && responseObject.success === true) {
					// Update the internal comment
					var communicationPanel = Ext.ComponentQuery.query('order-detail-window order-communication-panel')[0];
					communicationPanel.internalTextArea.setValue(responseObject.internalComment);
					// Show a growl notification and close the window
					Shopware.Notification.createGrowlMessage('{s name=order/controller/detail/success_notification/title}{/s}', '{s name=order/controller/detail/success_notification/message}{/s}', 'viison-stripe-refund');
					refundWindow.close()
				} else {
					// Show an alert
					var message = '{s name=order/controller/detail/error_alert/message}{/s} ';
					message += (responseObject.message !== undefined) ? responseObject.message : '{s name=order/controller/detail/error_alert/message/unknown}{/s}';
					Ext.MessageBox.alert('{s name=order/controller/detail/error_alert/title}{/s}', message);
				}
			}
		});
	}

});
//{/block}
