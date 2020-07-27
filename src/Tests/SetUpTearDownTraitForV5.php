<?php

namespace Glorpen\Propel\PropelBundle\Tests;

/**
 * @internal
 */
trait SetUpTearDownTraitForV5
{
    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::doSetUpBeforeClass();
    }

    /**
     * @return void
     */
    public static function tearDownAfterClass()
    {
        self::doTearDownAfterClass();
    }

    /**
     * @return void
     */
    protected function setUp()
    {
        self::doSetUp();
    }

    /**
     * @return void
     */
    protected function tearDown()
    {
        self::doTearDown();
    }

    private static function doSetUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    private static function doTearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }

    private function doSetUp()
    {
        parent::setUp();
    }

    private function doTearDown()
    {
        parent::tearDown();
    }
}
