<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Shopware_Controllers_Frontend_PaymentStripe extends Shopware_Controllers_Frontend_Payment
{
    /**
     * @var Shopware_Plugins_Frontend_SwagPaymentStripe_Bootstrap
     */
    private $plugin;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * {@inheritdoc}
     */
    public function preDispatch()
    {
        $this->plugin = $this->get('plugins')->Frontend()->SwagPaymentStripe();
        $this->session = $this->get('session');
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (version_compare(Shopware::VERSION, '4.2.0', '<') && Shopware::VERSION != '___VERSION___') {
            if($name == 'pluginlogger') {
                $name = 'log';
            }
            $name = ucfirst($name);
            return Shopware()->Bootstrap()->getResource($name);
        }
        return parent::get($name);
    }

    public function indexAction()
    {
        if ($this->getPaymentShortName() != 'stripe') {
            $this->redirect(array('controller' => 'checkout'));
            return;
        }

        $apiKey = $this->plugin->Config()->get('stripeSecretKey');
        \Stripe\Stripe::setApiKey($apiKey);

        $currency = $this->getCurrencyShortName();
        $amount = $this->getAmount() * 100;
        $user = $this->getUser();
        $email = $user['additional']['user']['email'];
        $customerNumber = $user['billingaddress']['customernumber'];
        $stripeCustomerId = $user['additional']['user']['viisonStripeCustomerId'];

        $chargeData = array(
            "amount" => $amount,
            "currency" => $currency,
            "customer" => $stripeCustomerId,
            "description" => ($email . ' / Kunden-Nr.: ' . $customerNumber),
            "metadata" => array("platform_name" => "UMXJ4nBknsWR3LN_shopware_v50")
        );

        if (!empty($this->session->stripeToken)) {
            unset($this->session->stripeToken);
            $chargeData['card'] = $this->session->stripeToken;
        } elseif (!empty($stripeCustomerId)) {
            $chargeData['customer'] = $stripeCustomerId;
        } else {
            $this->redirect(array('controller' => 'checkout', 'action' => 'shippingPayment'));
            return;
        }

        try {
            $charge = \Stripe\Charge::create($chargeData);
        } catch(\Stripe\Error\Card $e) {
            // The card has been declined
            return;
        }

        $uniqueId = sha1($charge->balance_transaction);
        $orderNumber = $this->saveOrder($charge->id, $uniqueId, 12);

        try {
            $charge->description .= ' / Bestell-Nr.: ' . $orderNumber;
            $charge->invoice = $orderNumber;
            $charge->save();
        } catch(\Stripe\Error\Base $e) {

        }

        $this->redirect(array(
            'controller' => 'checkout',
            'action' => 'finish',
            'sUniqueID' => $uniqueId
        ));
    }
}
