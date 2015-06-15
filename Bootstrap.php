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
        $this->createEvents();
        $this->createForm();
        $this->createStripePayment();
        $this->createAttributes();

        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $this->secureUninstall();
        $this->removeAttributes();

        return true;
    }

    /**
     * @return bool
     */
    public function enable()
    {
        $payment = $this->getStripePayment();
        if ($payment !== null) {
            $payment->setActive(true);
            $this->get('models')->flush($payment);
        }
        return array(
            'success' => true,
            'invalidateCache' => array('config', 'backend', 'frontend')
        );
    }

    /**
     * @return bool
     */
    public function disable()
    {
        $payment = $this->getStripePayment();
        if ($payment !== null) {
            $payment->setActive(false);
            $this->get('models')->flush($payment);
        }
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

    private function createEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );
    }

    private function createForm()
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

        $this->addFormTranslations(require __DIR__ . '/Translations/Form.php');
    }

    private function registerPaymentTranslations()
    {
        $translations = require __DIR__ . '/Translations/Payment.php';
        $shops = $this->getTranslationShops();
        $module = new Shopware_Components_Translation();
        $payment = $this->getStripePayment();
        if ($payment !== null) {
            foreach ($translations as $locale => $translation) {
                if (isset($shops[$locale])) {
                    $language = $shops[$locale];
                    $module->write($language, 'config_payment', $payment->getId(), $translation, true);
                }
            }
        }
    }

    private function getTranslationShops()
    {
        $sql = '
            SELECT locale.locale, shop.id
            FROM s_core_locales locale, s_core_shops shop

            LEFT JOIN s_core_shops fallback
            ON fallback.id = shop.fallback_id
            AND fallback.id != shop.id
            AND fallback.locale_id != shop.locale_id

            WHERE shop.locale_id = locale.id
            AND fallback.id IS NULL
        ';
        return $this->get('db')->fetchPairs($sql);
    }

    private function createAttributes()
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

    private function removeAttributes()
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

    private function createStripePayment()
    {
        $this->createPayment(array(
            'name' => 'stripe',
            'description' => 'Kreditkarte von Stripe',
            'template' => 'stripe.tpl',
            'action' => 'payment_stripe',
            'additionalDescription' => ''
        ));
        $this->registerPaymentTranslations();
    }

    private function getStripePayment()
    {
        return $this->Payments()->findOneBy(
            array('name' => 'stripe')
        );
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
