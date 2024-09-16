<?php

use ReactphpX\LaravelReactphp\Facades\Route;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;

Route::get('/', function (ServerRequestInterface $request) {
    return Response::plaintext(
        "Hello wörld!\n"
    );
});

$class = new class {
    public function index(ServerRequestInterface $request) {
        return Response::plaintext(
            "Hello wörld!\n"
        );
    }
};


Route::get('/concurrent', new class {

    protected $concurrent;
    public function __construct() {
        // 100个请求同时过来了，只处理前10个请求，让另外90个等待着去处理,第102个请求等待或丢弃掉(一个请求完成：header 和body 发送完毕)。
        $this->concurrent = new \ReactphpX\Concurrent\Concurrent(10, 100, true);
    }
    public function __invoke(ServerRequestInterface $request, $next) {
       return $this->concurrent->concurrent(fn () => $next($request))->then(null, function ($error) {
        // 第101 和 102 请求会返回 503 状态码
        if ($error instanceof \OverflowException) {
            \Log::info('Server busy');
            return new Response(503, [], 'Server busy');
        }
        throw $error;
    });
    }
}, function (ServerRequestInterface $request) {
    $stream = new \React\Stream\ThroughStream();
    \React\EventLoop\Loop::get()->addTimer(1, function () use ($stream) {
        $stream->end("Hello wörld!\n");
    });
    return new Response(200, ['Content-Type' => 'text/plain; charset=utf-8'], $stream);
});


Route::get('/limiter', new class {
    protected $limiterConcurrent;

    public function __construct() {
        // 一秒内100个请求过来了，只处理10个请求，其他90个请求发出429状态码或者等待1秒后在继续处理10个。当第102个请求过来时等待或丢弃掉 （一个请求完成：header 和body 发送完毕）。
        $this->limiterConcurrent = new \ReactphpX\LimiterConcurrent\LimiterConcurrent(10, 1000, false, 100, true);
    }
    public function __invoke(ServerRequestInterface $request, $next) {

        // 立即 429
        // if (!$this->limiterConcurrent->tryRemoveTokens(1)) {
        //     return new Response(429, [], 'Too many requests');
        // }
        // return $next($request);

        // 一秒内100个请求过来了，只处理10个请求，其他90个请求等待1秒后在继续处理10个。当第102个请求过来时等待或丢弃掉 （一个请求完成：header 和body 发送完毕）
        return $this->limiterConcurrent->concurrent(fn () => $next($request))->then(null, function ($error) {
            // 第101 和 102 请求会返回 503 状态码
            if ($error instanceof \OverflowException) {
                \Log::info('limiter Server busy');
                return new Response(503, [], 'Server busy');
            }
            throw $error;
        });
    }
}, function (ServerRequestInterface $request) {
    $stream = new \React\Stream\ThroughStream();
    \React\EventLoop\Loop::get()->addTimer(1, function () use ($stream) {
        $stream->end("Hello wörld!\n");
    });
    return new Response(200, ['Content-Type' => 'text/plain; charset=utf-8'], $stream);
});

Route::get('/at', get_class($class).'@index');