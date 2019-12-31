<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\View;

use Laminas\EventManager\EventManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\RouteNotFoundStrategy;
use Laminas\View\Model\ViewModel;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage UnitTest
 */
class RouteNotFoundStrategyTest extends TestCase
{
    public function setUp()
    {
        $this->strategy = new RouteNotFoundStrategy();
    }

    public function test404ErrorsInject404ResponseStatusCode()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $errors   = array(
            'error-controller-not-found' => Application::ERROR_CONTROLLER_NOT_FOUND,
            'error-controller-invalid'   => Application::ERROR_CONTROLLER_INVALID,
            'error-router-no-match'      => Application::ERROR_ROUTER_NO_MATCH,
        );
        $event->setResponse($response);
        foreach ($errors as $key => $error) {
            $response->setStatusCode(200);
            $event->setError($error);
            $this->strategy->detectNotFoundError($event);
            $this->assertTrue($response->isNotFound(), 'Failed asserting against ' . $key);
        }
    }

    public function testRouterAndDispatchErrorsInjectReasonInViewModelWhenAllowed()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $errors   = array(
            'error-controller-not-found' => Application::ERROR_CONTROLLER_NOT_FOUND,
            'error-controller-invalid'   => Application::ERROR_CONTROLLER_INVALID,
            'error-router-no-match'      => Application::ERROR_ROUTER_NO_MATCH,
        );
        $event->setResponse($response);
        foreach (array(true, false) as $allow) {
            $this->strategy->setDisplayNotFoundReason($allow);
            foreach ($errors as $key => $error) {
                $response->setStatusCode(200);
                $event->setError($error);
                $this->strategy->detectNotFoundError($event);
                $this->strategy->prepareNotFoundViewModel($event);
                $viewModel = $event->getResult();
                $this->assertInstanceOf('Laminas\View\Model\ModelInterface', $viewModel);
                $variables = $viewModel->getVariables();
                if ($allow) {
                    $this->assertTrue(isset($variables['reason']));
                    $this->assertEquals($key, $variables['reason']);
                } else {
                    $this->assertFalse(isset($variables['reason']));
                }
            }
        }
    }

    public function testNon404ErrorsInjectNoStatusCode()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $errors   = array(
            Application::ERROR_EXCEPTION,
            'custom-error',
            null,
        );
        foreach ($errors as $error) {
            $response->setStatusCode(200);
            $event->setError($error);
            $this->strategy->detectNotFoundError($event);
            $this->assertFalse($response->isNotFound());
        }
    }

    public function testResponseAsResultDoesNotPrepare404ViewModel()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $event->setResponse($response)
              ->setResult($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        if ($model instanceof ViewModel) {
            $this->assertNotEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
            $variables = $model->getVariables();
            $this->assertArrayNotHasKey('message', $variables);
        }
    }

    public function testNon404ResponseDoesNotPrepare404ViewModel()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $response->setStatusCode(200);
        $event->setResponse($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        if ($model instanceof ViewModel) {
            $this->assertNotEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
            $variables = $model->getVariables();
            $this->assertArrayNotHasKey('message', $variables);
        }
    }

    public function test404ResponsePrepares404ViewModelWithTemplateFromStrategy()
    {
        $response = new Response();
        $event    = new MvcEvent();
        $response->setStatusCode(404);
        $event->setResponse($response);

        $this->strategy->prepareNotFoundViewModel($event);
        $model = $event->getResult();
        $this->assertInstanceOf('Laminas\View\Model\ModelInterface', $model);
        $this->assertEquals($this->strategy->getNotFoundTemplate(), $model->getTemplate());
        $variables = $model->getVariables();
        $this->assertTrue(isset($variables['message']));
    }

    public function test404ResponsePrepares404ViewModelWithReasonWhenAllowed()
    {
        $response = new Response();
        $event    = new MvcEvent();

        foreach (array(true, false) as $allow) {
            $this->strategy->setDisplayNotFoundReason($allow);
            $response->setStatusCode(404);
            $event->setResponse($response);
            $this->strategy->prepareNotFoundViewModel($event);
            $model = $event->getResult();
            $this->assertInstanceOf('Laminas\View\Model\ModelInterface', $model);
            $variables = $model->getVariables();
            if ($allow) {
                $this->assertTrue(isset($variables['reason']));
                $this->assertEquals(Application::ERROR_CONTROLLER_CANNOT_DISPATCH, $variables['reason']);
            } else {
                $this->assertFalse(isset($variables['reason']));
            }
        }
    }

    public function test404ResponsePrepares404ViewModelWithExceptionWhenAllowed()
    {
        $response  = new Response();
        $event     = new MvcEvent();
        $exception = new \Exception();
        $event->setParam('exception', $exception);

        foreach (array(true, false) as $allow) {
            $this->strategy->setDisplayExceptions($allow);
            $response->setStatusCode(404);
            $event->setResponse($response);
            $this->strategy->prepareNotFoundViewModel($event);
            $model = $event->getResult();
            $this->assertInstanceOf('Laminas\View\Model\ModelInterface', $model);
            $variables = $model->getVariables();
            if ($allow) {
                $this->assertTrue(isset($variables['exception']));
                $this->assertSame($exception, $variables['exception']);
            } else {
                $this->assertFalse(isset($variables['exception']));
            }
        }
    }

    public function test404ResponsePrepares404ViewModelWithControllerWhenAllowed()
    {
        $response        = new Response();
        $event           = new MvcEvent();
        $controller      = 'some-or-other';
        $controllerClass = 'Some\Controller\OrOtherController';
        $event->setController($controller);
        $event->setControllerClass($controllerClass);

        foreach (array('setDisplayNotFoundReason', 'setDisplayExceptions') as $method) {
            foreach (array(true, false) as $allow) {
                $this->strategy->$method($allow);
                $response->setStatusCode(404);
                $event->setResponse($response);
                $this->strategy->prepareNotFoundViewModel($event);
                $model = $event->getResult();
                $this->assertInstanceOf('Laminas\View\Model\ModelInterface', $model);
                $variables = $model->getVariables();
                if ($allow) {
                    $this->assertTrue(isset($variables['controller']));
                    $this->assertEquals($controller, $variables['controller']);
                    $this->assertTrue(isset($variables['controller_class']));
                    $this->assertEquals($controllerClass, $variables['controller_class']);
                } else {
                    $this->assertFalse(isset($variables['controller']));
                    $this->assertFalse(isset($variables['controller_class']));
                }
            }
        }
    }

    public function testInjectsHttpResponseIntoEventIfNoneAlreadyPresent()
    {
        $event    = new MvcEvent();
        $errors   = array(
            'not-found' => Application::ERROR_CONTROLLER_NOT_FOUND,
            'invalid'   => Application::ERROR_CONTROLLER_INVALID,
        );
        foreach ($errors as $key => $error) {
            $event->setError($error);
            $this->strategy->detectNotFoundError($event);
            $response = $event->getResponse();
            $this->assertInstanceOf('Laminas\Http\Response', $response);
            $this->assertTrue($response->isNotFound(), 'Failed asserting against ' . $key);
        }
    }

    public function testNotFoundTemplateDefaultsToError()
    {
        $this->assertEquals('error', $this->strategy->getNotFoundTemplate());
    }

    public function testNotFoundTemplateIsMutable()
    {
        $this->strategy->setNotFoundTemplate('alternate/error');
        $this->assertEquals('alternate/error', $this->strategy->getNotFoundTemplate());
    }

    public function testAttachesListenersAtExpectedPriorities()
    {
        $events = new EventManager();
        $events->attachAggregate($this->strategy);

        foreach (array(MvcEvent::EVENT_DISPATCH => -90, MvcEvent::EVENT_DISPATCH_ERROR => 1) as $event => $expectedPriority) {
            $listeners        = $events->getListeners($event);
            $expectedCallback = array($this->strategy, 'prepareNotFoundViewModel');
            $found            = false;
            foreach ($listeners as $listener) {
                $callback = $listener->getCallback();
                if ($callback === $expectedCallback) {
                    if ($listener->getMetadatum('priority') == $expectedPriority) {
                        $found = true;
                        break;
                    }
                }
            }
            $this->assertTrue($found, 'Listener not found');
        }

        $listeners        = $events->getListeners(MvcEvent::EVENT_DISPATCH_ERROR);
        $expectedCallback = array($this->strategy, 'detectNotFoundError');
        $expectedPriority = 1;
        $found            = false;
        foreach ($listeners as $listener) {
            $callback = $listener->getCallback();
            if ($callback === $expectedCallback) {
                if ($listener->getMetadatum('priority') == $expectedPriority) {
                    $found = true;
                    break;
                }
            }
        }
        $this->assertTrue($found, 'Listener not found');
    }

    public function testDetachesListeners()
    {
        $events = new EventManager();
        $events->attachAggregate($this->strategy);
        $listeners = $events->getListeners(MvcEvent::EVENT_DISPATCH);
        $this->assertEquals(1, count($listeners));
        $listeners = $events->getListeners(MvcEvent::EVENT_DISPATCH_ERROR);
        $this->assertEquals(2, count($listeners));
        $events->detachAggregate($this->strategy);
        $listeners = $events->getListeners(MvcEvent::EVENT_DISPATCH);
        $this->assertEquals(0, count($listeners));
        $listeners = $events->getListeners(MvcEvent::EVENT_DISPATCH_ERROR);
        $this->assertEquals(0, count($listeners));
    }
}
