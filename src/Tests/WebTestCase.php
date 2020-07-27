<?php

namespace Glorpen\Propel\PropelBundle\Tests;

// A trait to provide forward compatibility with newest PHPUnit versions
$r = new \ReflectionClass('\Symfony\Bundle\FrameworkBundle\Test\WebTestCase');
if (PHP_VERSION_ID < 70000 || !$r->getMethod('tearDown')->hasReturnType()) {
    abstract class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
    {
        use SetUpTearDownTraitForV5;
    }
} else {
    abstract class WebTestCase extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
    {
        use SetUpTearDownTraitForV8;
    }
}
