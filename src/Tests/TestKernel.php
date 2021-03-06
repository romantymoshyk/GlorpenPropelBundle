<?php
/**
 * This file is part of the GlorpenPropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license GPLv3
 */

namespace Glorpen\Propel\PropelBundle\Tests;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    protected $debug = true;
    private $containerBuilder;
    protected $name;

    private static $nameSeed = 0;

    public function getName()
    {
        if (null === $this->name) {
            self::$nameSeed++;
            $this->name = 'test_'.self::$nameSeed;
        }

        return $this->name;
    }

    public function getCacheDir()
    {
        return parent::getCacheDir().'/'.$this->getName();
    }

    public function registerBundles()
    {
        $bundles = array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Glorpen\Propel\PropelBundle\GlorpenPropelBundle(),
        );

        if (Kernel::MAJOR_VERSION < 4.4) {
            $bundles[] = new \Propel\PropelBundle\PropelBundle();
        } else {
            $bundles[] = new \Propel\PropelBundle\PropelBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $path =
            sprintf(
                '%s/Resources/config/config_%s%s.yml',
                __DIR__,
                $this->getEnvironment(),
                Kernel::MAJOR_VERSION >= 5 ? '_v5' : ''
            );

        $loader->load(realpath($path));
    }

    public function getRootDir()
    {
        return realpath(__DIR__.'/../..').'/test-app';
    }

    /**
     * For symfony 3.3 and Propel 1
     * @param ContainerBuilder $container
     */
    protected function build(ContainerBuilder $container)
    {
        // Support for Symfony 3.3
        if ($this->containerBuilder) {
            call_user_func($this->containerBuilder, $container);
        }

        // Fix services from Propel 1
        $container->addCompilerPass(new FixingCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING, 1000);
    }

    public function setContainerBuilder($callback)
    {
        $this->containerBuilder = $callback;
    }

    /**
     * For symfony 2.3
     * @param ContainerBuilder $container
     */
    protected function prepareContainer(ContainerBuilder $container)
    {
        parent::prepareContainer($container);
        $this->build($container);
    }

    public function shutdown()
    {
        parent::shutdown();
        $fs = new Filesystem();
        $fs->remove($this->getCacheDir());
    }
}
