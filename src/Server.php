<?php

namespace Encore\LaraTrace;

use React\EventLoop\Factory;
use React\Socket\Connection;
use React\Socket\Server as SocketServer;
use React\Stream\Stream;

class Server
{
    protected $host;

    protected $port;

    protected $input;

    /**
     * @var Stream
     */
    protected $output;

    protected $loop;

    protected $api;

    /**
     * @var Connection[]
     */
    protected $connections = [];

    protected $responseBuffer = '';

    public function __construct($port = 7777, $host = '127.0.0.1')
    {
        $this->host = $host;

        $this->port = $port;

        $this->api = new Api($this);
    }

    public function setupInput($loop)
    {
        $this->input = new Stream(STDIN, $loop);

        $this->input->on('data', function ($data) {
            $this->api->dispatch($data);
        });
    }

    protected function setupOutput($loop)
    {
        $this->output = new Stream(STDOUT, $loop);
    }

    /**
     * @return Stream
     */
    public function getOutput()
    {
        return $this->output;
    }

    public function run()
    {
        $this->loop = Factory::create();

        $socket = new SocketServer($this->loop);

        $this->setupInput($this->loop);
        $this->setupOutput($this->loop);

        $socket->on('connection', [$this, 'onConnection']);

        $socket->listen($this->port, $this->host);

        $this->loop->run();
    }

    protected function addConnection(Connection $conn)
    {
        array_push($this->connections, $conn);
    }

    /**
     * @return Connection[]
     */
    public function getConnections()
    {
        return $this->connections;
    }

    public function onConnection(Connection $conn)
    {
        $this->addConnection($conn);

        $this->api->applyBreakPoints($conn);

        $conn->on('data', function ($data) {

            if (substr(trim($data), -strlen('</response>')) !== (string) '</response>') {
                $this->responseBuffer .= $data;

            } else {
                $this->responseBuffer .= $data;

                preg_match_all('/<response.*<\/response>/', $this->responseBuffer, $arr);

                $arr = array_flatten($arr);

                $this->getOutput()->write(var_export($arr, true));
                //$this->getOutput()->write(var_export($this->api->parseResponse($arr), true));

                $this->api->call($arr);

                $this->responseBuffer = '';
            }
        });
    }
}
