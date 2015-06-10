//{namespace name=backend/plugins/payment_stripe/order_detail_position_refund}

/**
 * A simple window, displaying a list of the selected psitions as well as their total
 * value and a field for adding a comment to the refund transaction.
 */
Ext.define('Shopware.apps.PaymentStripe.Order.view.detail.position.refund.Window', {

	extend: 'Ext.window.Window',

	alias: 'widget.payment-stripe-refund-window',
	title: '{s name=order/view/detail/position/refund/window/title}{/s}',
	modal: true,
	height: 440,
	width: 800,
	layout: 'fit',

	/**
	 * Creates the view and store of this window.
	 */
	initComponent: function() {
		// Create a new loadmask
		this.loadMask = new Ext.LoadMask(this, {
			msg: '{s name=order/view/detail/position/refund/window/load_mask}{/s}',
		});
		this.loadMask.hide();

		this.addEvents(
			/**
			 * Event will be fired when the user clicks the 'refund' button.
			 *
			 * @event performRefund
			 * @param window This window.
			 */
			'performRefund'
		);

		this.items = {
			layout: 'border',
			border: false,
			items: [
				{
					xtype: 'grid',
					region: 'center',
					layout: 'fit',
					store: this.store,
					columns: this.gridColumns,
					border: false
				},
				this.createForm()
			],
			dockedItems: [
				{
					xtype: 'toolbar',
					dock: 'bottom',
					ui: 'shopware-ui',
					padding: 10,
					items: [
						{
							xtype: 'component',
							flex: 1
						}, {
							xtype: 'button',
							text: '{s name=order/view/detail/position/refund/window/cancel_button}{/s}',
							cls: 'secondary',
							scope: this,
							handler: function() {
								this.close();
							}
						}, {
							xtype: 'button',
							text: '{s name=order/view/detail/position/refund/window/confirm_button}{/s}',
							cls: 'primary',
							scope: this,
							handler: function() {
								this.fireEvent('performRefund', this);
							}
						}
					]
				}
			]
		};

		this.callParent(arguments);
	},

	/**
	 * Creates and returns a new form panel consisting of a label for the total amount and
	 * a text field for the comment.
	 *
	 * @return The created form panel.
	 */
	createForm: function() {
		this.form = Ext.create('Ext.form.Panel', {
			region: 'south',
			layout: 'vbox',
			padding: 10,
			border: false,
			items: [
				{
					xtype: 'displayfield',
					fieldLabel: '{s name=order/view/detail/position/refund/window/form/total_amount}{/s}',
					name: 'total',
					value: Ext.util.Format.currency(this.total, ' &euro;', 2, true)
				}, {
					xtype: 'textfield',
					fieldLabel: '{s name=order/view/detail/position/refund/window/form/comment}{/s}',
					name: 'comment',
					width: '100%'
				}
			]
		});

		return this.form;
	}

});
