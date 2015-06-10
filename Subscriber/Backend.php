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
class Backend implements SubscriberInterface
{
    /**
     * @var Bootstrap
     */
    private $bootstrap;

    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onPostDispatchBackendOrder',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_StripePayment' => 'onGetControllerPathPaymentStripe',
        );
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchBackendOrder($args)
    {
        // Add View directory
        $view = $args->getSubject()->View();
        $view->addTemplateDir(__DIR__ . '/../Views/');
        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('backend/plugins/payment_stripe/order_detail_position_refund.js');
        }
    }

    public function onGetControllerPathPaymentStripe()
    {
        return __DIR__ . '/../Controllers/Backend/PaymentStripe.php';
    }
}
