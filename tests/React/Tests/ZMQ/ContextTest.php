<?php

namespace React\Tests\ZMQ;

use React\ZMQ\Context;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldWrapContext()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $innerContext = $this->getMockBuilder('ZMQContext')->disableOriginalConstructor()->getMock();
        $innerContext
            ->expects($this->once())
            ->method('getSocket')
            ->with(\ZMQ::SOCKET_PULL, null);

        $context = new Context($loop, $innerContext);
        $context->getSocket(\ZMQ::SOCKET_PULL, null);
    }

    public function testShouldWrapSockets()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $context = new Context($loop);
        $socket = $context->getSocket(\ZMQ::SOCKET_PULL);

        $this->assertInstanceOf('React\ZMQ\SocketWrapper', $socket);
    }

    public function testShouldAddReadListener()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->once())
            ->method('addReadStream');

        $context = new Context($loop);
        $socket = $context->getSocket(\ZMQ::SOCKET_PULL);
    }

    public function testShouldNotAddReadListenerForNonReadableSocketType()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->never())
            ->method('addReadStream');

        $context = new Context($loop);
        $socket = $context->getSocket(\ZMQ::SOCKET_PUSH);
    }
}
