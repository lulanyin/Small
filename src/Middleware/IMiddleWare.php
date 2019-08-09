<?php
namespace Small\Middleware;

interface IMiddleWare{
    public function process($controller, ...$args);
}