<?php
namespace Small\middleware;

interface IMiddleWare{
    public function process($controller, ...$args);
}