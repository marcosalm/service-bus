<?php
/*
 * This file is part of the prooph/service-bus.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.10.14 - 22:47
 */

namespace Prooph\ServiceBusTest\Plugin\Router;

use Prooph\Common\Event\DefaultActionEvent;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\MessageBus;
use Prooph\ServiceBus\Plugin\Router\RegexRouter;
use Prooph\ServiceBusTest\TestCase;

/**
 * Class RegexRouterTest
 *
 * @package Prooph\ServiceBusTest\Router
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class RegexRouterTest extends TestCase
{
    /**
     * @test
     */
    public function it_matches_pattern_with_command_name_to_detect_appropriate_handler()
    {
        $regexRouter = new RegexRouter();

        $regexRouter->route('/^'.preg_quote('Prooph\ServiceBusTest\Mock\Do').'.*/')->to("DoSomethingHandler");

        $actionEvent = new DefaultActionEvent(MessageBus::EVENT_ROUTE, new CommandBus(), [
            MessageBus::EVENT_PARAM_MESSAGE_NAME => 'Prooph\ServiceBusTest\Mock\DoSomething',
        ]);

        $regexRouter->onRoute($actionEvent);

        $this->assertEquals("DoSomethingHandler", $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLER));
    }

    /**
     * @test
     */
    public function it_does_not_allow_that_two_pattern_matches_with_same_command_name()
    {
        $regexRouter = new RegexRouter();

        $regexRouter->route('/^'.preg_quote('Prooph\ServiceBusTest\Mock\Do').'.*/')->to("DoSomethingHandler");
        $regexRouter->route('/^'.preg_quote('Prooph\ServiceBusTest\Mock\\').'.*/')->to("DoSomethingHandler2");

        $this->setExpectedException('\Prooph\ServiceBus\Exception\RuntimeException');

        $actionEvent = new DefaultActionEvent(MessageBus::EVENT_ROUTE, new CommandBus(), [
            MessageBus::EVENT_PARAM_MESSAGE_NAME => 'Prooph\ServiceBusTest\Mock\DoSomething',
        ]);

        $regexRouter->onRoute($actionEvent);
    }

    /**
     * @test
     */
    public function it_matches_pattern_with_event_name_and_routes_to_multiple_listeners()
    {
        $regexRouter = new RegexRouter();

        $regexRouter->route('/^'.preg_quote('Prooph\ServiceBusTest\Mock\\').'.*Done$/')->to("SomethingDoneListener1");
        $regexRouter->route('/^'.preg_quote('Prooph\ServiceBusTest\Mock\\').'.*Done$/')->to("SomethingDoneListener2");

        $actionEvent = new DefaultActionEvent(MessageBus::EVENT_ROUTE, new EventBus(), [
            MessageBus::EVENT_PARAM_MESSAGE_NAME => 'Prooph\ServiceBusTest\Mock\SomethingDone',
        ]);

        $regexRouter->onRoute($actionEvent);

        $this->assertEquals(["SomethingDoneListener1", "SomethingDoneListener2"], $actionEvent->getParam(EventBus::EVENT_PARAM_EVENT_LISTENERS));
    }

    /**
     * @test
     */
    public function it_fails_on_routing_a_second_pattern_before_first_definition_is_finished()
    {
        $router = new RegexRouter();

        $router->route('Prooph\ServiceBusTest\Mock\DoSomething');

        $this->setExpectedException('\Prooph\ServiceBus\Exception\RuntimeException');

        $router->route('/.*/');
    }

    /**
     * @test
     */
    public function it_fails_on_setting_a_handler_before_a_pattern_is_set()
    {
        $router = new RegexRouter();

        $this->setExpectedException('\Prooph\ServiceBus\Exception\RuntimeException');

        $router->to('DoSomethingHandler');
    }

    /**
     * @test
     */
    public function it_takes_a_routing_definition_on_instantiation()
    {
        $router = new RegexRouter(array(
            '/^'.preg_quote('Prooph\ServiceBusTest\Mock\Do').'.*/' => 'DoSomethingHandler',
            '/^'.preg_quote('Prooph\ServiceBusTest\Mock\\').'.*Done$/' => ["SomethingDoneListener1", "SomethingDoneListener2"]

        ));

        $actionEvent = new DefaultActionEvent(MessageBus::EVENT_ROUTE, new CommandBus(), [
            MessageBus::EVENT_PARAM_MESSAGE_NAME => 'Prooph\ServiceBusTest\Mock\DoSomething',
        ]);

        $router->onRoute($actionEvent);

        $this->assertEquals("DoSomethingHandler", $actionEvent->getParam(MessageBus::EVENT_PARAM_MESSAGE_HANDLER));

        $actionEvent = new DefaultActionEvent(MessageBus::EVENT_ROUTE, new EventBus(), [
            MessageBus::EVENT_PARAM_MESSAGE_NAME => 'Prooph\ServiceBusTest\Mock\SomethingDone',
        ]);

        $router->onRoute($actionEvent);

        $this->assertEquals(["SomethingDoneListener1", "SomethingDoneListener2"], $actionEvent->getParam(EventBus::EVENT_PARAM_EVENT_LISTENERS));
    }
}
 