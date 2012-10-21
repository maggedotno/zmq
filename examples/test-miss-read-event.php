<?php

// Test script demonstrating ticket #2
// A vanilla push socket sends lots of data
// A react pull socket reads this data
// one of the edge-triggered events would be missed
// causing the pull socket to stop reading events
//
// This example forks off a pull process which
// echoes out "-" for every received message.
// The main process connects to it and pushes
// messages in random-ish intervals and echoes
// out "+" for each one of them.
//
// What should happen: You get shitloads of + and -
// dumped on your screen, looking like brainfuck
// code. This goes on for ever and ever.
//
// What might happen: You get shitloads of + and -
// dumped on your screen, but at some point the -
// stop and you only get shitloads of +. At some
// point the + also stop. The program keeps running,
// but does no longer spit out anything new.

require __DIR__.'/../vendor/autoload.php';

function pull_routine()
{
    $loop = React\EventLoop\Factory::create();

    $context = new React\ZMQ\Context($loop);
    $socket = $context->getSocket(ZMQ::SOCKET_PULL);
    $socket->bind('ipc://test.ipc');
    $socket->on('message', function($msg) {
        if (is_array($msg))
            echo "M";
        else
            echo "S";
    });

    $loop->run();
}

function push_routine()
{
    $zmq = new ZMQContext(1);
    $socket = $zmq->getSocket(ZMQ::SOCKET_PUSH, 'xyz');
    $socket->connect('ipc://test.ipc');

    while (true) {
        $msgs = rand(1, 300);
        for ($n = 0; $n < $msgs; $n++) {
            if (rand(0,100) >= 50) {
                echo "s";
                $socket->send('bogus-'.$n);
            }
            else {
                echo "m";
                $socket->sendmulti(array("bogus$n-1", "bogus$n-2", "bogus$n-3"));
            }
        }

        usleep(rand(0, 1000000));
    }
}

$pid = pcntl_fork();
if ($pid == 0) {
    pull_routine();
    exit;
}

push_routine();
