/**
 * A model, which represents a simple version of an order item, which are used
 * to refund some of the items of an order.
 */
Ext.define('Shopware.apps.ViisonStripe.Order.model.detail.position.refund.Item', {

	extend : 'Ext.data.Model',

	fields : [
		{ name: 'id', type: 'int' },
		{ name: 'articleNumber', type: 'string' },
		{ name: 'articleName', type: 'string' },
		{ name: 'quantity', type: 'int' },
		{ name: 'price', type: 'decimal' },
		{ name: 'total', type: 'decimal' }
	]

});
