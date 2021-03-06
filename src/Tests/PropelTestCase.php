<?php
namespace Glorpen\Propel\PropelBundle\Tests;

use Glorpen\Propel\PropelBundle\Connection\EventPropelPDO;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @author Arkadiusz Dzięgiel <arkadiusz.dziegiel@glorpen.pl>
 */
class PropelTestCase extends TestCase
{
    
    protected static function getRoot()
    {
        return __DIR__ . '/../..';
    }
    
    protected static $schema = <<<SCHEMA
<database name="books" defaultIdMethod="native" namespace="Glorpen\\Propel\\PropelBundle\\Tests\\Fixtures\\Model">
    <table name="book">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="title" type="varchar" size="255" primaryString="true" />
			
		<behavior name="event" />
		<behavior name="extend" />
    </table>
	<table name="person">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" size="255" primaryString="true" />
			
		<column name="class_key" type="INTEGER" inheritance="single">
			<inheritance key="1" class="LongPerson"/>
			<inheritance key="2" class="TrianglePerson"/>
			<inheritance key="3" class="RectanglePerson"/>
		</column>
			
		<behavior name="event" />
		<behavior name="extend" />
    </table>
	<table name="si_thing">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
        <column name="name" type="varchar" size="255" primaryString="true" />
			
		<column name="class_key" type="INTEGER" inheritance="single">
			<inheritance key="1" class="SiThingA"/>
			<inheritance key="2" class="SiThingB"/>
		</column>
			
		<behavior name="event" />
		<behavior name="extend" />
    </table>
	<table name="softdelete_table">
		<column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true" />
		<behavior name="soft_delete" /> <!-- note it is before "event" -->
		<behavior name="event" />
	</table>
</database>
SCHEMA;
    
    public static function setUpBeforeClass()
    {
        if (!file_exists($file = static::getRoot() . '/vendor/propel/propel1/runtime/lib/Propel.php')) {
            self::markTestSkipped('Propel is not available.');
        }
    
        require_once $file;
    }
    
    
    public function getContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
                'kernel.debug'      => false,
                'kernel.root_dir'   => static::getRoot() . '/test-app',
        )));
    }
    
    protected function loadPropelQuickBuilder()
    {
        require_once static::getRoot() . '/vendor/propel/propel1/runtime/lib/Propel.php';
        require_once static::getRoot() . '/vendor/propel/propel1/runtime/lib/adapter/DBAdapter.php';
        require_once static::getRoot() . '/vendor/propel/propel1/runtime/lib/adapter/DBSQLite.php';
        require_once static::getRoot() . '/vendor/propel/propel1/runtime/lib/connection/PropelPDO.php';
        require_once static::getRoot() . '/vendor/propel/propel1/generator/lib/util/PropelQuickBuilder.php';
    }
    
    protected $builder;
    
    protected function loadAndBuild()
    {
        $this->loadPropelQuickBuilder();
    
        if (!class_exists('Glorpen\Propel\PropelBundle\Tests\Fixtures\Model\Book', false)) {
            $builder = new \PropelQuickBuilder();
            
            $builder->getConfig()->setBuildProperty('behaviorEventClass', 'src.Behaviors.EventBehavior');
            $builder->getConfig()->setBuildProperty('behaviorExtendClass', 'src.Behaviors.ExtendBehavior');
                
            $builder->setSchema(static::$schema);
            $builder->setClassTargets(array('tablemap', 'peer', 'object', 'query', 'peerstub', 'querystub'));
            //file_put_contents("/tmp/a.php",$builder->getClasses());
            $builder->build();
            
            $con = new EventPropelPDO('sqlite::memory:');
            $con->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
            
            $name = $builder->getDatabase()->getName();
            \Propel::setConnection($name, $con, \Propel::CONNECTION_READ);
            \Propel::setConnection($name, $con, \Propel::CONNECTION_WRITE);
            
            $builder->buildSQL($con);
        }
    }
}
