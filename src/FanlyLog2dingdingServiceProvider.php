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