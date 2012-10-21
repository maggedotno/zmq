<?php

namespace React\ZMQ;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

class Buffer extends EventEmitter
{
    public $socket;
    public $closed = false;
    public $listening = false;
    private $loop;
    private $fd;
    private $messages = array();

    public function __construct(\ZMQSocket $socket, $fd, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->fd = $fd;
        $this->loop = $loop;
    }

    public function send($message)
    {
        if ($this->closed) {
            return;
        }

        $this->messages[] = $message;

        if (!$this->listening) {
            $this->listening = true;
            $this->loop->addWriteStream($this->fd, array($this, 'handleWrite'));
        }
    }

    public function end()
    {
        $this->closed = true;

        if (!$this->listening) {
            $this->emit('end');
        }
    }

    public function handleWrite()
    {
        if (!$this->socket->getSockOpt(\ZMQ::SOCKOPT_EVENTS) & \ZMQ::POLL_OUT) {
            return;
        }

        // send while we have messages and the socket can receive messages
        while ($this->hasMessages() && $this->socket->getSockOpt(\ZMQ::SOCKOPT_EVENTS) & \ZMQ::POLL_OUT) {

            $message = array_pop($this->messages);
            try {
                if (is_array($message))
                    $this->socket->sendmulti($message, \ZMQ::MODE_NOBLOCK);
                else
                    $this->socket->send($message, \ZMQ::MODE_NOBLOCK);
            }
            catch (\ZMQSocketException $e) {
                $this->emit('error', array($e));
            }
        }

        if (!$this->hasMessages())
            $this->removeFromLoop();

        $this->emit('written');
    }

    public function hasMessages() {
        return (count($this->messages) > 0);
    }

    private function removeFromLoop() {
        $this->loop->removeWriteStream($this->fd);
        $this->listening = false;
        $this->emit('end');
    }
}
