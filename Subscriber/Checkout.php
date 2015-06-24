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
use \Enlight_Controller_Request_Request as Request;

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
        $view = $action->View();

        $apiKey = $this->bootstrap->Config()->get('stripeSecretKey');
        \Stripe\Stripe::setApiKey($apiKey);

        $token = $request->getPost('stripeToken');
        if (!empty($token)) {
            try {
                $this->onStripeToken($request);
            } catch (\Stripe\Error\Card $e) {
                $eJson = $e->getJsonBody();
                $error = $eJson['error'];
                $view->assign('sErrorMessages', [$error['message']]);
                if ($request->getControllerName() == 'checkout') {
                    $action->forward('shippingPayment');
                } else {
                    $action->forward('payment');
                }
                $request->setPost('stripeToken', null);
                $action->Response()->clearHeader('Location')->setHttpResponseCode(200);
                return;
            }
        }

        if (!empty($view->sPayments) && !empty($view->sUserData['additional']['user']['viisonStripeCustomerId'])) {
            $customerId = $view->sUserData['additional']['user']['viisonStripeCustomerId'];
            $customer = \Stripe\Customer::retrieve($customerId);
            $view->stripeSources = $this->convertCards($customer['sources']['data']);
        }
    }

    /**
     * @param array $cards
     * @return array
     */
    private function convertCards($cards)
    {
        $cards = array_map(function ($card) {
            return array(
                'id' => $card->id,
                'holder' => $card->name,
                'brand' => $card->brand,
                'last4' => $card->last4,
                'expMonth' => $card->exp_month,
                'expYear' => $card->exp_year,
            );
        }, $cards);
        return $cards;
    }

    /**
     * @param Request $request
     */
    public function onStripeToken($request)
    {
        $token = $request->getPost('stripeToken');
        $db = $this->bootstrap->get('db');
        if ($request->getPost('stripeCreateAccount')) {

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
