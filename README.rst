-------------------
GlorpenPropelBundle
-------------------

.. image:: https://travis-ci.org/glorpen/GlorpenPropelBundle.png?branch=master


Additional Propel integration for Symfony.

Official repositories
=====================

For forking and other funnies


GitHub: https://github.com/glorpen/GlorpenPropelBundle - main repo


BitBucket: https://bitbucket.org/glorpen/glorpenpropelbundle

Supported Symfony versions
===========================

This bundle currently supports Symfony starting from version 2.3. 

You will have to specify dev dependency for `propel/propel-bundle` for Symfony 3.x projects, eg:

.. sourcecode:: json

   {
       "require": {
           "symfony/symfony": "^3.3",
           "propel/propel-bundle": "1.6.x-dev",
           "glorpen/propel-bundle": "^1.5"
       }
   }


How to install
==============

- add requirements to composer.json:

.. sourcecode:: json

   {
       "require": {
           "glorpen/propel-bundle": "@dev"
       }
   }
   

- enable the plugin in your **AppKernel** class

*app/AppKernel.php*

.. sourcecode:: php

    <?php
    
    class AppKernel extends AppKernel
    {
       public function registerBundles()
       {
           $bundles = array(
               ...
               new Glorpen\Propel\PropelBundle\GlorpenPropelBundle(),
               ...
           );
       }
    }


- add behavior configuration to propel config

To enable all behaviors at once you can import to your configuration *@GlorpenPropelBundle/Resources/config/config.yml* and *config_dev.yml* accordingly.


Example for *config.yml*:

.. sourcecode:: yaml

   imports:
       - { resource: @GlorpenPropelBundle/Resources/config/config.yml } 


Propel Events
=============

If you didn't import *config.yml* providen by this bundle, you have to add *event* behavior to your propel configuration and change *PropelPDO* class.


.. sourcecode:: yaml

   propel:
     build_properties:
       propel.behavior.event.class: 'vendor.glorpen.propel-bundle.src.Behaviors.EventBehavior'
       propel.behavior.default: "event"
     dbal:
       classname: Glorpen\Propel\PropelBundle\Connection\EventPropelPDO
 

And in *config_dev.yml*:

.. sourcecode:: yaml

   propel:
     dbal:
       classname: Glorpen\Propel\PropelBundle\Connection\EventDebugPDO


Listening for propel hooks
--------------------------

With subscriber:

.. sourcecode:: xml

	<service class="SomeBundle\Listeners\HistoryBehaviorListener">
		<argument type="service" id="security.context" />
		<tag name="propel.event" />
	</service>

With listener:

.. sourcecode:: xml
	
	<service id="my.listener" class="SomeBundle\Listeners\HistoryBehaviorListener">
		<tag name="propel.event" method="onPropelEventSave" event="model.save.post" priority="0" />
	</service>

The `priority` attribute is optional.

In both cases you can narrow receiving events to given class with `class` attribute:

.. sourcecode:: xml
   
   <service id="my.listener" class="SomeBundle\Listeners\HistoryBehaviorListener">
      <tag name="propel.event" method="onPropelEventSave" event="model.save.post" class="SomeBundle\Model\Example" />
   </service>


Available events
----------------

Event class: `ConnectionEvent`

- connection.create
- connection.begin.pre
- connection.begin.post
- connection.commit.pre
- connection.commit.post
- connection.rollback.post
- connection.rollback.pre

Event class: `ModelEvent`

- model.insert.post
- model.update.post
- model.delete.post
- model.save.post
- model.insert.pre
- model.update.pre
- model.delete.pre
- model.save.pre
- model.update.after
- model.insert.after
- model.save.after
- model.construct
- model.hydration.post (connection argument is always null)

Events named `model.*.after` are triggered after transaction is commited but before returning from `$model->save()` method.

Additionally it will trigger only if something was updated/inserted, it will NOT trigger on empty save, eg: `$model->save()->save()`.

Event class: `QueryEvent`

- query.delete.pre
- query.delete.post
- query.select.pre
- query.update.pre
- query.update.post
- query.construct

Event class: `PeerEvent`

- peer.construct

Will be called on model/query/peer construct/delete/update/etc

ContainerAwareInterface for model
---------------------------------

You can implement **ContainerAwareInterface** on your model to get access to *Container* through built-in service. Container is injected in *model.construct* event.

If you find yourself with error like `Serialization of 'Closure' is not allowed` it is probably about some not serializable services injected in model (since propel occasionally serializes and unserializes data).

.. sourcecode:: php

   <?php
   
   use Symfony\Component\DependencyInjection\ContainerAwareInterface;
   use Symfony\Component\DependencyInjection\ContainerInterface;
   
   class Something extends BaseSomething implements ContainerAwareInterface
   {
      private $someService;
      
      public function setContainer(ContainerInterface $container = null){
         if($container) $this->someService = $this->container->get("some_service");
      }  
   }

Transaction events
------------------

Just like with Doctrine *@ORM\HasLifecycleCallbacks* you can handle non db logic in model in db transaction.

Commit hooks will be run just before PDO transaction commit and rollback just before rolback and only on saved models (if exception was thrown in preCommit hook). Methods provided by **EventBehavior** are:

- preCommit
- preCommitSave
- preCommitUpdate
- preCommitInsert
- preCommitDelete
- preRollback
- preRollbackSave
- preRollbackUpdate
- preRollbackInsert
- preRollbackDelete

Be aware that when using transaction on big amount of model objects with on-demand formatter they still will be cached inside service so you can exhaust available php memory. 

And example how you can use available hooks (code mostly borrowed from Symfony2 cookbook):

.. sourcecode:: php

   <?php
   class SomeModel extends BaseSomeModel {
      public function preCommitSave(\PropelPDO $con = null){
         $this->upload();
      }
      public function preCommitDelete(\PropelPDO $con = null){
         $this->removeUpload();
      }
      
      public function preSave(\PropelPDO $con = null){
         $this->preUpload();
         return parent::preSave($con);
      }
      
      // code below is copied from http://symfony.com/doc/2.1/cookbook/doctrine/file_uploads.html
      
      public $file;
      
      public function preUpload(){
         if (null !== $this->file){
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->path = $filename.'.'.$this->file->guessExtension();
         }
      }
      
      public function upload(){
         if (null === $this->path) return;
      
         // if there is an error when moving the file, an exception will
         // be automatically thrown by move(). This will properly prevent
         // the entity from being persisted to the database on error
         $this->file->move($this->getUploadRootDir(), $this->path);
         throw new \RuntimeException("file cannot be saved");
      
         unset($this->path);
      }
      
      public function removeUpload(){
         if ($file = $this->getAbsolutePath()){
            unlink($file);
         }
      }
   }

Custom events
-------------

You can trigger events with generic or custom Event class, in following example **ValidationEvent**. 

- create **ValidationEvent** event

.. sourcecode:: php

   <?php
   
   namespace YourBundle\Events;
   use Symfony\Component\Validator\Mapping\ClassMetadata;
   use Symfony\Component\EventDispatcher\Event;
   
   class ValidationEvent extends Event {
      private $metadata;
      
      public function __construct(ClassMetadata $metadata){
         $this->metadata = $metadata;
      }
      
      /**
       * @return \Symfony\Component\Validator\Mapping\ClassMetadata
       */
      public function getMetadata(){
         return $this->metadata;
      }
   }

- register listener in **services.xml**

.. sourcecode:: xml

   <service id="your.service" class="%your.service.class%">
      <argument>%your.service.argument%</argument>
      <tag name="propel.event" method="onProductLoadValidatorMetadata" event="product.validation" />
   </service>

- and then use it within model class

.. sourcecode:: php

   <?php
   
   namespace YourBundle\Model;
   use YourBundle\Events\ValidationEvent;
   use Glorpen\Propel\PropelBundle\\Dispatcher\EventDispatcherProxy;
   use Symfony\Component\Validator\Mapping\ClassMetadata;
   use YourBundle\Model\om\BaseProduct;
   
   class Product extends BaseProduct {
      public static function loadValidatorMetadata(ClassMetadata $metadata)
      {
         EventDispatcherProxy::trigger('product.validation', new ValidationEvent($metadata));
      }
   }


Model Extending
===============

If you didn't import *config.yml* providen by this bundle, you have to add *extend* behavior to your propel configuration.

.. sourcecode:: yaml

   propel:
     build_properties:
       propel.behavior.extend.class: 'vendor.glorpen.propel-bundle.Glorpen.Propel.PropelBundle.Behaviors.ExtendBehavior'
       propel.behavior.default: "extend"

With behavior enabled you can define custom model classes for use with Propel.

You can extend only Model classes this way (extending Peers/Queries shouldn't be needed).

Calls to Query::find(), Peer::populateObject() etc. will now return your extended class objects.

In short it fixes:

-  extending Model classes used by other bundles (eg. FOSUserBundle)
-  queries/peer's returning proper isntances
-  creating proper Query instance when calling `SomeQuery::create()` 


Mapping usage
-------------

In *config.yml*:

.. sourcecode:: yaml

   glorpen_propel:
     extended_models:
       FOS\UserBundle\Propel\User: MyApp\MyBundle\Propel\User


Dynamic/Services usage
----------------------

You can create dynamic extends by using services.

Your service should implement *Glorpen\Propel\PropelBundle\Provider\OMClassProvider* interface.

In *services.xml*:

.. sourcecode:: xml

   <service id="your.service" class="%your.service.class%">
      <argument>%your.service.argument%</argument>
      <tag name="propel.om" />
   </service>


FOSUserBundle and AdminGenerator
--------------------------------

With above config, you can generate backend with **AdminGenerator** for **FOSUser** edit/creation/etc. For now you have to create empty UserQuery and UserPeer classes and then whole backend for user model should work :)


Other goodies
=============

PlainModelJoin
--------------

Allows to inject data into `ON` clause for eg. comparing field to date or field from other joined table.

*Remember that provided values are added as-is, without parsing for aliases and escaping.*

Usage:

.. sourcecode:: php

      <?php
      $relationAlias = 'WithoutCurrentSubscription';
      
      $join = PlainModelJoin::create($this, 'Subscription', $relationAlias, \Criteria::LEFT_JOIN);
      
      //active items...
      $join->addCondition($relationAlias.'.starts_at', '"'.$now->format('Y-m-d H:i:s').'"', \Criteria::LESS_EQUAL);
      $join->addCondition($relationAlias.'.ends_at', '"'.$now->format('Y-m-d H:i:s').'"', \Criteria::GREATER_EQUAL);
      
      //...and inversion
      $this->where('WithoutCurrentSubscription.Id is null');
