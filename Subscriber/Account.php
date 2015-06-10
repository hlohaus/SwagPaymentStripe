<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagPaymentStripe\Subscriber;

use Enlight\Event\SubscriberInterface;
use \Enlight_Controller_Action as ControllerAction;
use \Shopware_Plugins_Frontend_SwagPaymentStripe_Bootstrap as Bootstrap;

/**
 * Class Checkout
 * @package Shopware\SwagPaymentStripe\Subscriber
 */
class Account implements SubscriberInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Frontend_Account' => 'onPostDispatchAccount'
        );
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchAccount($args)
    {
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();

        // Secure dispatch
        if (!$request->isDispatched()
            || $response->isException()
            || $response->isRedirect()
            || $request->getActionName() != 'confirm'
        ) {
            return;
        }

        // Paypal plus conditions
        $view = $action->View();
        $user = $view->sUserData;

//        if (!empty($this->session->PaypalResponse['TOKEN']) // PP-Express
//            || empty($user['additional']['payment']['name'])
//            || $user['additional']['payment']['name'] != 'paypal'
//            || !in_array($user['additional']['country']['id'], $countries)
//        ) {
//            return;
//        }

        /** @var $shopContext \Shopware\Models\Shop\Shop */
        $shopContext = $this->bootstrap->get('shop');
        $templateVersion = $shopContext->getTemplate()->getVersion();

        $this->bootstrap->registerMyTemplateDir($templateVersion >= 3);
    }
}