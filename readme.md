我们在写代码时，都想自己的代码尽可能的不影响现有的代码。

或者说，最大化不改动任何代码的情况下，如何嵌入我们的新功能？这是我们常说的「非侵入式」的开发方式。

使用「非侵入式」的开发模式，主要在提供第三方插件和功能中最为常见。今天借助「Rollbar」第三方工具来说说如何做到「非侵入式」开发。

本文主要能学到:

> 1. Laravel Event / Listener 原理；
> 2. Rollbar for Laravel 的使用
> 3. 创建一个 Log to Dingding 群的功能

## Laravel Event / Listener 原理

在 Laravel，主要利用 `EventServiceProvider` 来加载 `Events / Listeners`:

```php
<?php

namespace Illuminate\Events;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }
}
```

`EventServiceProvider` 返回的是 `Dispatcher` 对象。我们看看 `Dispatcher` 类：

```php
<?php

namespace Illuminate\Events;

use Exception;
use ReflectionClass;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Dispatcher implements DispatcherContract
{
    /**
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The registered event listeners.
     *
     * @var array
     */
    protected $listeners = [];

    /**
     * The wildcard listeners.
     *
     * @var array
     */
    protected $wildcards = [];

    /**
     * The queue resolver instance.
     *
     * @var callable
     */
    protected $queueResolver;

    /**
     * Create a new event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(ContainerContract $container = null)
    {
        $this->container = $container ?: new Container;
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener)
    {
        foreach ((array) $events as $event) {
            if (Str::contains($event, '*')) {
                $this->setupWildcardListen($event, $listener);
            } else {
                $this->listeners[$event][] = $this->makeListener($listener);
            }
        }
    }

...

}
```
主要作用是绑定 `Events` 和 `Listeners`，当 `Events`触发时，直接执行 `Listeners`。


我们希望 log 除了在本地文件存储输出外，也想把 log 信息实时发到其他平台和渠道上，这时候我们就需要借助 `LogServiceProvider` 的 `events / listeners`绑定实现了。现在来看看 `LogServiceProvider`:


```php
<?php

namespace Illuminate\Log;

use Monolog\Logger as Monolog;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('log', function () {
            return $this->createLogger();
        });
    }

    /**
     * Create the logger.
     *
     * @return \Illuminate\Log\Writer
     */
    public function createLogger()
    {
        $log = new Writer(
            new Monolog($this->channel()), $this->app['events']
        );

        if ($this->app->hasMonologConfigurator()) {
            call_user_func($this->app->getMonologConfigurator(), $log->getMonolog());
        } else {
            $this->configureHandler($log);
        }

        return $log;
    }

   ...
}
```

这里将 `$this->app['events']` 也就是 `Dispatcher` 传入，用户事件的注册：


```php
    /**
     * Register a new callback handler for when a log event is triggered.
     *
     * @param  \Closure  $callback
     * @return void
     *
     * @throws \RuntimeException
     */
    public function listen(Closure $callback)
    {
        if (! isset($this->dispatcher)) {
            throw new RuntimeException('Events dispatcher has not been set.');
        }

        $this->dispatcher->listen(MessageLogged::class, $callback);
    }
```

有了 `ServiceProvider` 和 `listen` 就可以做到「非入侵」开发了。

## Rollbar

> Rollbar error monitoring integration for Laravel projects. This library adds a listener to Laravel's logging component. Laravel's session information will be sent in to Rollbar, as well as some other helpful information such as 'environment', 'server', and 'session'.
> 
> 参考：[https://docs.rollbar.com/docs/laravel](https://docs.rollbar.com/docs/laravel)

### 简单使用

使用该工具，只要在其官网注册账号，并产生一个 `access token` 即可

安装该工具，也只需要简单的两步：

```bash
composer require rollbar/rollbar-laravel

// .env
ROLLBAR_TOKEN=[your Rollbar project access token]

// 如果 < Laravel 5.5，则需要在 app.php 中添加
Rollbar\Laravel\RollbarServiceProvider::class,
```

测试，只要有 Log 输出，rollbar 后台都可以收到信息，方便查看，而再也不需要去看 log 文件了。

![](http://ow20g4tgj.bkt.clouddn.com/2018-05-13-15261913480148.jpg)


### 剖析实现原理

我们来看看 rollbar 是不是我们所设想的那样实现的？

![](http://ow20g4tgj.bkt.clouddn.com/2018-05-13-15261914714008.jpg)

我们先看看 `RollbarServiceProvider`


```php
<?php namespace Rollbar\Laravel;

use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Rollbar\Rollbar;
use Rollbar\Laravel\RollbarLogHandler;

class RollbarServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        // Don't boot rollbar if it is not configured.
        if ($this->stop() === true) {
            return;
        }

        $app = $this->app;

        // Listen to log messages.
        $app['log']->listen(function () use ($app) {
            $args = func_get_args();

            // Laravel 5.4 returns a MessageLogged instance only
            if (count($args) == 1) {
                $level = $args[0]->level;
                $message = $args[0]->message;
                $context = $args[0]->context;
            } else {
                $level = $args[0];
                $message = $args[1];
                $context = $args[2];
            }

            $app['Rollbar\Laravel\RollbarLogHandler']->log($level, $message, $context);
        });
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Don't register rollbar if it is not configured.
        if ($this->stop() === true) {
            return;
        }

        $app = $this->app;

        $this->app->singleton('Rollbar\RollbarLogger', function ($app) {

            $defaults = [
                'environment'       => $app->environment(),
                'root'              => base_path(),
                'handle_exception'  => true,
                'handle_error'      => true,
                'handle_fatal'      => true,
            ];
            $config = array_merge($defaults, $app['config']->get('services.rollbar', []));
            $config['access_token'] = getenv('ROLLBAR_TOKEN') ?: $app['config']->get('services.rollbar.access_token');

            if (empty($config['access_token'])) {
                throw new InvalidArgumentException('Rollbar access token not configured');
            }

            $handleException = (bool) array_pull($config, 'handle_exception');
            $handleError = (bool) array_pull($config, 'handle_error');
            $handleFatal = (bool) array_pull($config, 'handle_fatal');

            Rollbar::init($config, $handleException, $handleError, $handleFatal);

            return Rollbar::logger();
        });

        $this->app->singleton('Rollbar\Laravel\RollbarLogHandler', function ($app) {

            $level = getenv('ROLLBAR_LEVEL') ?: $app['config']->get('services.rollbar.level', 'debug');

            return new RollbarLogHandler($app['Rollbar\RollbarLogger'], $app, $level);
        });
    }

    /**
     * Check if we should prevent the service from registering
     *
     * @return boolean
     */
    public function stop()
    {
        $level = getenv('ROLLBAR_LEVEL') ?: $this->app->config->get('services.rollbar.level', null);
        $token = getenv('ROLLBAR_TOKEN') ?: $this->app->config->get('services.rollbar.access_token', null);
        $hasToken = empty($token) === false;

        return $hasToken === false || $level === 'none';
    }
}
```

这个比较好理解，先利用 `register` 注册两个 `singleton`，然后在 `boot` 方法中，注册 `listener`


```php
    $app['log']->listen(function () use ($app){});
```

其中 `$app['log']`，就是我们的上文说的 `LogServiceProvider`，将 `listener` 注册到 `EventServiceProvider` 中。

```php
$this->dispatcher->listen(MessageLogged::class, $callback);
```

最后我们看看 `Rollbar` facades 返回的是：`RollbarLogHandler` 对象

```php
<?php namespace Rollbar\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class Rollbar extends Facade
{
    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Rollbar\Laravel\RollbarLogHandler
     */
    protected static function getFacadeAccessor()
    {
        return 'Rollbar\Laravel\RollbarLogHandler';
    }
}

```

看看 `RollbarLogHandler` 实现，也主要是将 log 信息反馈到Rollbar 中，此处不做分析了。

## 模拟实现

通过对 `Rollbar` 简单的分析，就会发现原来通过简单 `Listener`，不用改现在的任何功能和代码，就能实现将 log 实时发到你想接收的地方。

所以我们可以尝试也写一个这样的功能，将 log 信息发到钉钉上。

好了，我们开始写 `Log2Dingding` 插件。

根据之前的文章我们可以很方便的组织好插件结构:

![](http://ow20g4tgj.bkt.clouddn.com/2018-05-13-15261990158468.jpg)

`composer.json` 设置:

```php
{
    "name": "fanly/log2dingding",
    "description": "Laravel Log to DingDing",
    "license": "MIT",
    "authors": [
        {
            "name": "fanly",
            "email": "yemeishu@126.com"
        }
    ],
    "require": {},
    "extra": {
        "laravel": {
            "providers": [
                "Fanly\\Log2dingding\\FanlyLog2dingdingServiceProvider"
            ]
        }
    },
    "autoload": {
        "psr-4": {
            "Fanly\\Log2dingding\\": "src/"
        }
    }
}

```

我们定义 `ServiceProvider`:

```php
<?php
/**
 * User: yemeishu
 * Date: 2018/5/13
 * Time: 下午2:56
 */
namespace Fanly\Log2dingding;

use Fanly\Log2dingding\Dingtalk\Messager;
use Illuminate\Support\ServiceProvider;
use Fanly\Log2dingding\Support\Client;

class FanlyLog2dingdingServiceProvider extends ServiceProvider {

    protected function registerFacade()
    {
        // Don't register rollbar if it is not configured.
        if ($this->stop() === true) {
            return;
        }

        $this->app->singleton('fanlylog2dd', function ($app) {
            $config['access_token'] = getenv('FANLYLOG_TOKEN') ?: $app['config']->get('services.fanly.log2dd.access_token');

            if (empty($config['access_token'])) {
                throw new InvalidArgumentException('log2dd access token not configured');
            }

            return (new Messager(new Client()))->accessToken($config['access_token']);
        });
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Don't boot rollbar if it is not configured.
        if ($this->stop() === true) {
            return;
        }

        $app = $this->app;

        // Listen to log messages.
        $app['log']->listen(function () use ($app) {
            $args = func_get_args();

            // Laravel 5.4 returns a MessageLogged instance only
            if (count($args) == 1) {
                $level = $args[0]->level;
                $message = $args[0]->message;
                $context = $args[0]->context;
            } else {
                $level = $args[0];
                $message = $args[1];
                $context = $args[2];
            }

            $app['fanlylog2dd']->message("[ $level ] $message\n".implode($context))->send();
        });

    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->registerFacade();
    }

    private function stop()
    {
        $level = getenv('FANLYLOG_LEVEL') ?: $this->app->config->get('services.rollbar.level', null);
        $token = getenv('FANLYLOG_TOKEN') ?: $this->app->config->get('services.rollbar.access_token', null);
        $hasToken = empty($token) === false;

        return $hasToken === false || $level === 'none';
    }
}
```

我们主要是创建一个发钉钉消息的单例，然后再注册 `listener`，只要获取 log 信息，就发送信息到钉钉上。

测试一下：

![](http://ow20g4tgj.bkt.clouddn.com/2018-05-13-15261992511566.jpg)


## 总结

最后做成插件，和 `Rollbar` 一样，引入：

```bash
composer require "fanly/log2dingding"

// .env
FANLYLOG_TOKEN=56331868f7056a3e645e7dba034c5550e7af***
```

同样的，其他信息都不需要设置，跑一个测试：

![](http://ow20g4tgj.bkt.clouddn.com/2018-05-13-15262016735782.jpg)

Laravel 框架的一大好处在于，可以以友好的方式实现我们「非入侵」开发，只要借助「`ServiceProvider`」和「`Events/Listner`」，就可以扩展我们的功能。

*参考*

* 「12步」制作 Laravel 插件 (一)[https://mp.weixin.qq.com/s/AD05BiKjPsI2ehC-mhQJQw](https://mp.weixin.qq.com/s/AD05BiKjPsI2ehC-mhQJQw)
* 「3步」发布 Laravel 插件 (二)[https://mp.weixin.qq.com/s/RSYeHU7aR4gyJyLNwdjbJg](https://mp.weixin.qq.com/s/RSYeHU7aR4gyJyLNwdjbJg)
* fanly/log2dingding [https://packagist.org/packages/fanly/log2dingding](https://packagist.org/packages/fanly/log2dingding)