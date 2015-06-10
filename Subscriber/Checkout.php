<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\SwagPaymentStripe\Subscriber;

use Enlight\Event\SubscriberInterface;
use \Shopware_Plugins_Frontend_SwagPaymentStripe_Bootstrap as Bootstrap;

/**
 * Class Checkout
 * @package Shopware\SwagPaymentStripe\Subscriber
 */
class Checkout implements SubscriberInterface
{
    /**
     * @var Bootstrap
     */
    private $bootstrap;
    private $session;

    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->session = $bootstrap->get('session');
    }

    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onPostDispatchCheckout',
            'Enlight_Controller_Action_PostDispatch_Frontend_Account' => 'onPostDispatchCheckout',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentStripe' => 'onGetControllerPathPaymentStripe',
        );
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchCheckout($args)
    {
        $action = $args->getSubject();
        $request = $action->Request();
        $db = $this->bootstrap->get('db');

        $token = $request->getPost('stripeToken');
        if (empty($token)) {
            return;
        }

        if ($request->getPost('stripeCreateAccount')) {
            $apiKey = $this->bootstrap->Config()->get('stripeSecretKey');
            \Stripe\Stripe::setApiKey($apiKey);

            $sql = 'SELECT firstname, lastname, customernumber FROM s_user_billingaddress WHERE userID = ?';
            $customer = $db->fetchRow($sql, array($this->session->sUserId));

            $customer = \Stripe\Customer::create(array(
                "source" => $token,
                "email" => $this->session->sUserMail,
                "description" => implode(' ', $customer)
            ));
            $customerId = $customer->id;
            unset($this->session->stripeToken);
        } else {
            $this->session->stripeToken = $token;
            $customerId = null;
        }
        $db->update(
            's_user_attributes',
            array('viison_stripe_customer_id' => $customerId),
            array('userID =' .(int)$this->session->sUserId)
        );
    }

    public function onGetControllerPathPaymentStripe()
    {
        return __DIR__ . '/../Controllers/Frontend/PaymentStripe.php';
    }
}
