<?php
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\SwagPaymentStripe\Subscriber;

use Enlight\Event\SubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Theme\LessDefinition;
use Enlight_Controller_ActionEventArgs as ActionEventArgs;

class ResponsiveTheme implements SubscriberInterface
{
    private $templateDir;

    public function __construct($templateDir)
    {
        $this->templateDir = $templateDir;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'Theme_Compiler_Collect_Plugin_Less' => 'onCollectLessFiles',
            'Theme_Compiler_Collect_Plugin_Javascript' => 'onCollectJavascriptFiles',
            'Enlight_Controller_Action_PostDispatchSecure' => 'addAddTemplateDir'
        );
    }

    /**
     * Provide the file collection for less
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function onCollectLessFiles()
    {
        $less = new LessDefinition(
            //configuration
            array(),
            //less files to compile
            array(
                $this->templateDir . '/frontend/_public/src/less/all.less'
            ),
            //import directory
            $this->templateDir
        );
        return new ArrayCollection(array($less));
    }


    /**
     * @return ArrayCollection
     */
    public function onCollectJavascriptFiles()
    {
        $jsDir = $this->templateDir . '/frontend/_public/src/js/';

        return new ArrayCollection(array(
            $jsDir . 'jquery.stripe.js'
        ));
    }

    /**
     * @param ActionEventArgs $args
     * @return array
     */
    public function addAddTemplateDir($args)
    {
        $args->getSubject()->View()->addTemplateDir($this->templateDir);
    }
}