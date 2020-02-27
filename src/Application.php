<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace BEdita\App;

use Cake\Core\Configure;
use Cake\Core\Exception\MissingPluginException;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap()
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        }

        // Load more plugins here
        $this->addPlugin('BEdita/Core', ['bootstrap' => true, 'routes' => true, 'ignoreMissing' => true]);
        $this->addPlugin('BEdita/API', ['bootstrap' => true, 'routes' => true, 'ignoreMissing' => true]);

        $this->loadFromConfig();
    }

    /**
     * @return void
     */
    protected function bootstrapCli()
    {
        $this->addPlugin('Migrations');
        try {
            $this->addPlugin('Bake');
        } catch (MissingPluginException $e) {
            // intentionally empty catch block
            // allow missing `bake` plugin in no-dev environment
        }
    }

    /**
     * Load plugins from 'Plugins' configuration
     *
     * @return void
     */
    protected function loadFromConfig(): void
    {
        $plugins = (array)Configure::read('Plugins');
        if (empty($plugins)) {
            return;
        }

        $_defaults = [
            'debugOnly' => false,
            'autoload' => false,
            'bootstrap' => true,
            'routes' => true,
            'ignoreMissing' => true
        ];
        foreach ($plugins as $plugin => $options) {
            if (!is_string($plugin) && is_string($options)) {
                // plugin listed not in form 'PluginName' => [....]
                // but as non associative array like 0 => 'PluginName'
                $plugin = $options;
                $options = [];
            }
            $options = array_merge($_defaults, $options);
            if (!$options['debugOnly'] || ($options['debugOnly'] && Configure::read('debug'))) {
                $this->addPlugin($plugin, $options);
            }
        }
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware($middlewareQueue)
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(ErrorHandlerMiddleware::class)

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(AssetMiddleware::class)

            // Add routing middleware.
            ->add(new RoutingMiddleware($this));

        return $middlewareQueue;
    }
}
