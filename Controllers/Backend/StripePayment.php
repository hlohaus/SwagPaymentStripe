<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Shopware_Controllers_Backend_StripePayment extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var Shopware_Plugins_Frontend_SwagPaymentStripe_Bootstrap
     */
    private $plugin;

    /**
     * {@inheritdoc}
     */
    public function preDispatch()
    {
        $this->plugin = $this->get('plugins')->Frontend()->SwagPaymentStripe();
        parent::preDispatch();
    }

    /**
     * Gets the order id, total amount, refunded positions and an optional comment
     * from the request and uses them to create a new refund with Stripe.
     * If successful, the information abouth the refund are added to the internal coment
     * of ther order. Finally the new internal comment is added to the response.
     */
    public function refundAction()
    {
        // Get order id, total amount, positions and comment from the request
        $orderId = $this->Request()->getParam('orderId');
        if ($orderId === null) {
            // Missing orderId
            $this->View()->success = false;
            $this->View()->message = 'Required parameter "orderId" not found';
            $this->Response()->setHttpResponseCode(400);
            return;
        }
        $amount = floatval($this->Request()->getParam('amount'));
        if ($amount <= 0.0) {
            // Invalid amount
            $this->View()->success = false;
            $this->View()->message = 'Required parameter "amount" must be greater zero';
            $this->Response()->setHttpResponseCode(400);
            return;
        }
        $positions = $this->Request()->getParam('positions', array());
        if (count($positions) === 0) {
            // Missing positions
            $this->View()->success = false;
            $this->View()->message = 'Required parameter "positions" not found or empty';
            $this->Response()->setHttpResponseCode(400);
            return;
        }
        $comment = $this->Request()->getParam('comment');

        // Try to get order
        /** @var Shopware\Models\Order\Order $order */
        $order = $this->get('models')->getRepository('Shopware\Models\Order\Order')->findOneById($orderId);
        if ($order === null) {
            // Order does not exist
            $this->View()->success = false;
            $this->View()->message = 'Order with id ' . $orderId . ' not found';
            $this->Response()->setHttpResponseCode(404);
            return;
        }
        if ($order->getTransactionId() === null) {
            // Order wasn't payed with Stripe
            $this->View()->success = false;
            $this->View()->message = 'Order with id ' . $orderId . ' has no Stripe charge';
            $this->Response()->setHttpResponseCode(404);
            return;
        }

        // Set the Stripe API key
        $apiKey = $this->plugin->Config()->get('stripeSecretKey');
        \Stripe\Stripe::setApiKey($apiKey);

        // Load the charge and add new refund to it
        try {
            $charge = \Stripe\Charge::retrieve($order->getTransactionId());
            $charge->refund(array(
                'amount' => intval($amount * 100)
            ));
        } catch (Exception $e) {
            // Try to get the error response
            if ($e->getJsonBody() !== null) {
                $body = $e->getJsonBody();
                $message = $body['error']['message'];
            } else {
                $message = $e->getMessage();
            }

            $this->View()->success = false;
            $this->View()->message = $message;
            $this->Response()->setHttpResponseCode(500);
            return;
        }

        // Add a new refund comment to the internal comment of the order
        $internalComment = $order->getInternalComment();
        $internalComment .= "\n--------------------------------------------------------------\n"
            . 'Stripe Rückerstattung (' . date('d.m.Y, G:i:s') . ")\n"
            . 'Betrag: ' . number_format($amount, 2, ',', '.') . " €\n"
            . "Kommentar: $comment\n"
            . "Positionen:\n";
        foreach ($positions as $position) {
            $price = number_format($position['price'], 2, ',', '.');
            $totalPrice = number_format($position['total'], 2, ',', '.');
            $internalComment .= ' - ' . $position['quantity'] . ' x ' . $position['articleNumber'] . ', je ' . $price . ' €, Gesamt: ' . $totalPrice . " €\n";
        }
        $internalComment .= "--------------------------------------------------------------\n";
        $order->setInternalComment($internalComment);
        $this->get('models')->flush($order);

        // Respond with the new internal comment
        $this->View()->success = true;
        $this->View()->internalComment = $internalComment;
    }
}
