<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller;

use Laminas\Mvc\Controller\PluginManager;
use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit_Framework_TestCase as TestCase;

class PluginManagerTest extends TestCase
{
    public function testPluginManagerThrowsExceptionForMissingPluginInterface()
    {
        $this->setExpectedException('Laminas\Mvc\Exception\InvalidPluginException');

        $pluginManager = new PluginManager;
        $pluginManager->setInvokableClass('samplePlugin', 'stdClass');

        $plugin = $pluginManager->get('samplePlugin');
    }

    public function testPluginManagerInjectsControllerInPlugin()
    {
        $controller    = new SampleController;
        $pluginManager = new PluginManager;
        $pluginManager->setInvokableClass('samplePlugin', 'LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin');
        $pluginManager->setController($controller);

        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($controller, $plugin->getController());
    }

    public function testPluginManagerInjectsControllerForExistingPlugin()
    {
        $controller1   = new SampleController;
        $pluginManager = new PluginManager;
        $pluginManager->setInvokableClass('samplePlugin', 'LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin');
        $pluginManager->setController($controller1);

        // Plugin manager registers now instance of SamplePlugin
        $pluginManager->get('samplePlugin');

        $controller2   = new SampleController;
        $pluginManager->setController($controller2);

        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($controller2, $plugin->getController());
    }
}
