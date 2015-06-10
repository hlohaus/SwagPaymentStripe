<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Shopware\SwagPaymentStripe\Subscriber;

class Shopware_Plugins_Frontend_SwagPaymentStripe_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Register the autoloader
     */
    public function afterInit()
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Installs the plugin
     *
     * @return bool
     */
    public function install()
    {
        $this->createMyEvents();
        $this->createMyForm();
        $this->createMyPayment();
        $this->createMyAttributes();

        return array(
            'success' => true,
            'invalidateCache' => array('config', 'backend', 'frontend')
        );
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $this->secureUninstall();
        $this->removeMyAttributes();

        return array(
            'success' => true,
            'invalidateCache' => array('config', 'backend', 'frontend')
        );
    }

    /**
     * @return bool
     */
    public function secureUninstall()
    {
        return true;
    }

    /**
     * @param string $version
     * @return bool|array
     */
    public function update($version)
    {
        return $this->install();
    }

    private function createMyEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );
    }

    private function createMyForm()
    {
        $form = $this->Form();

        $form->setElement('text', 'stripeSecretKey', array(
            'label' => 'Stripe Secret Key',
            'required' => true,
            'description' => 'Tragen Sie hier Ihren geheimen Schlüssel ("Secret Key") ein.',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
            'stripCharsRe' => ' '
        ));
        $form->setElement('text', 'stripePublishableKey', array(
            'label' => 'Stripe Publishable Key',
            'required' => true,
            'description' => 'Tragen Sie hier Ihren öffentlichen Schlüssel ("Publishable Key") ein.',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
            'stripCharsRe' => ' '
        ));
    }

    private function createMyAttributes()
    {
        /** @var $modelManager \Shopware\Components\Model\ModelManager */
        $modelManager = $this->get('models');
        try {
            $modelManager->addAttribute(
                's_user_attributes',
                'viison',
                'stripe_customer_id',
                'varchar(255)'
            );
        } catch (Exception $e) {
        }
        try {
            $modelManager->generateAttributeModels(array(
                's_user_attributes'
            ));
        } catch (Exception $e) {
        }
    }

    private function removeMyAttributes()
    {
        /** @var $modelManager \Shopware\Components\Model\ModelManager */
        $modelManager = $this->get('models');
        try {
            $modelManager->removeAttribute(
                's_user_attributes',
                'viison',
                'stripe_customer_id'
            );
        } catch (Exception $e) {
        }
    }

    private function createMyPayment()
    {
        $this->createPayment(array(
            'name' => 'stripe',
            'description' => 'Kreditkarte von Stripe',
            'template' => 'stripe.tpl',
            'action' => 'payment_stripe',
            'additionalDescription' => ''
        ));
    }

    /**
     * @param Enlight_Controller_EventArgs $args
     */
    public function onStartDispatch($args)
    {
        $request = $args->getRequest();

        $templateDir = __DIR__ . '/Views/';
        $subscriber = new Subscriber\ResponsiveTheme($templateDir);
        $this->get('events')->addSubscriber($subscriber);

        if ($request->getModuleName() == 'frontend') {
            $subscriber = new Subscriber\Checkout($this);
            $this->get('events')->addSubscriber($subscriber);
        } elseif ($request->getModuleName() == 'backend') {
            $subscriber = new Subscriber\Backend($this);
            $this->get('events')->addSubscriber($subscriber);
        }
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Stripe';
    }

    /**
     * Returns the version of plugin as string.
     *
     * @throws Exception
     * @return string
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel()
        );
    }
}
