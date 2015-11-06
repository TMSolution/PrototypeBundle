<?php

/**
 * Description of EntityTest
 *
 * Testing all entities from the bundles.
 * 
 *  
 * @author Lukasz Sobieraj
 */

namespace TMSolution\PhantomBundle\Tests;

use \PHPUnit_Framework_TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use WebDriverCapabilityType;
use RemoteWebDriver;
use WebDriverBy;
use URLChecker;
use TMSolution\GeneratorBundle\Generator\Faker;
use Faker\Factory;
use TMSolution\GeneratorBundle\Generator\Faker\Populator;
use TMSolution\GeneratorBundle\Generator\Faker\Generator;
use TMSolution\GeneratorBundle\Generator\Faker\EntityPopulator;
use TMSolution\GeneratorBundle\Generator\Faker\ColumnTypeGuesser;
use \ColumnMap;
use TMSolution\PhantomBundle\Tests\Doctrine\ORM\Mapping\ClassMetadata as ClassMetaDataTM;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadata;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Core\ModelBundle\Model\Model;
use PhantomBundle\Tests\Util\Ghost;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class EntityTest extends WebTestCase {

    private static $entitiesNames;
    protected static $kernel;
    protected static $container;
    protected $webDriver;
    protected static $generator;
    protected $manager;
    protected $modelFactory;
    protected $model;

// do not needed - may it be in an every dataProvider
//    public static function setUpBeforeClass() {
//        die('setup beforeclass');
//        self::$kernel = new \TestAppKernel('test', true);
//        self::$kernel->boot();
//        self::$container = self::$kernel->getContainer();
//    }

    public function get($serviceId) {
        return self::$container->get($serviceId);
    }

    public function setUp() {
        $capabilities = array(WebDriverCapabilityType::BROWSER_NAME => 'firefox');
        $this->webDriver = RemoteWebDriver::create('http://127.0.0.1:4444/wd/hub', $capabilities);
        $this->webDriver->manage()->timeouts()->implicitlyWait(4);
    }

    /**
     * @dataProvider testNewProvider
     */
    public function testNew($entity) {
        //creating the faker object
        $faker = Factory::create();
        //part to prepare a correct entity name
        $pathToEntity = get_class($entity);
        $pieces = explode('\\', $pathToEntity);
        $last_word = array_pop($pieces);
        $entityName = strtolower($last_word);

        //create a model
        $this->modelFactory = $this->get('model_factory');
        $this->model = $this->modelFactory->getModel($pathToEntity);

        //create a form
        $parameters = $this->get('router')->match('/panel/' . $entityName . '/new');
        $configuratorService = $this->get('prototype.formtype.configurator.service');
        $formType = $configuratorService->getService($parameters["_route"], $entity);

        if (get_class($formType) == 'Core\PrototypeBundle\Form\FormType') {
            $formType->setModel($this->model);
        }
        //check the amount of records in the begin of test
        //build entity manager
//        $em = self::$container->get('doctrine.orm.entity_manager');
//        $queryFirstMax = $em->createQuery("SELECT MAX(pr.id) FROM phantom.productcategory pr");
//        $resultFirstMax = $queryFirstMax->getResult();
//        dump($resultFirstMax);
//        die('first');

        $form = $this->get('form.factory')->create($formType, $entity, []);

        $this->webDriver->get("http://localhost/makeapp/web/app_dev.php/panel/" . $entityName . "/new");
        foreach ($form as $item) {
            $fieldName = $item->getConfig()->getName();
            $type = $item->getConfig()->getType()->getName();
            $attr = $item->getConfig()->getAttributes();
//            $options = $form->get('name')->getConfig()->getOptions();
            //prepare the correct name for recognizing the element
            $pathClassName = get_class($form->getData());


            $pathLowerCase = strtolower($pathClassName);
            $str = substr($pathLowerCase, 0, strrpos($pathLowerCase, '\\'));
            $correctPath = str_replace("\\", "_", $str);


            //columnTypeGuesser      
            $generator = new Generator();
            //add a data providers to the generator
            $generator->addProvider(new \Faker\Provider\Lorem($generator));
            $generator->addProvider(new \Faker\Provider\Color($generator));
            $generator->addProvider(new \Faker\Provider\DateTime($generator));
            $columnGuesser = new ColumnTypeGuesser($generator);


            //---------------guess a format--------------------------------------

            $guess = $columnGuesser->guessFormat(
                    $fieldName, $this->model->getMetadata()
            );


//            $typeMetaData = $classMetaData->getTypeOfField($fieldName);
            //-----------------annotation reader----------------------------------------
            //class annotation
            $annotationReader = new AnnotationReader();
            //get class antotation---------------------------------------------
            $reflectionClass = new \ReflectionClass('PhantomBundle\Entity\ProductDescription');
            $classAnotations = $annotationReader->getClassAnnotations($reflectionClass);
            dump($classAnotations);
            die('ok');
            //-----------------------------------------------------------------------------
            //property annotation-----------------------------------------------
            $reflectionProperty = new \ReflectionProperty('PhantomBundle\Entity\ProductCategory', 'name');
            $propertyAnnotations = $annotationReader->getPropertyAnnotations($reflectionProperty);
//            dump($propertyAnnotations);
//            die('ok');
            //------------------------------------------------------------------
            //method annotation
            $reflectionMethod = new \ReflectionMethod('PhantomBundle\Entity\ProductCategory', 'getName');
            $methodAnnotations = $annotationReader->getMethodAnnotations($reflectionMethod);
//            dump($methodAnnotations);
//            die('ok');
            //------------------------------------------------------------------
            //object annotation
            $annotationDemoObject = new \PhantomBundle\Entity\ProductCategory();
            $reflectionObject = new \ReflectionObject($annotationDemoObject);
//            dump($reflectionObject);
//            die('ok');
            //------------------------------------------------------------------

            if ($type == "text" || $type == "textarea") {
                if ($entityName == "product") {
                    $name = $item->getConfig()->getName();
                    $param = $correctPath . '_' . $name;

                    if ($fieldName == "color") {
                        $this->webDriver->findElement(WebDriverBy::id($param))->click();
                        $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                        $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                    }
                    if ($fieldName == "editor") {
                        $this->webDriver->executeScript("jQuery('.note-editor').click();");
                        $this->webDriver->findElement(WebDriverBy::cssSelector("div.note-editable.panel-body"))->clear();
                        $this->webDriver->findElement(WebDriverBy::cssSelector("div.note-editable.panel-body"))->sendKeys($guess());
                        //type in the additional editor field
                        $this->webDriver->findElement(WebDriverBy::id($param))->click();
                        $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                        $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                    }
                } else {
                    $name = $item->getConfig()->getName();
                    $param = $entityName . '_' . $name;
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                }
            }
            if ($type == "number") {
                if ($entityName == "product") {
                    $name = $item->getConfig()->getName();
                    $param = $correctPath . '_' . $name;
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                } else {
                    $name = $item->getConfig()->getName();
                    $param = $entityName . '_' . $name;
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                }
            }
            if ($type == "datetime") {
                if ($entityName == "product") {
                    $name = $item->getConfig()->getName();
                    $param = $correctPath . '_' . $name;
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                } else {
                    $name = $item->getConfig()->getName();
                    $param = $entityName . '_' . $name;
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                }
            }
            if ($type == "date") {
                if ($entityName == "product") {
                    $name = $item->getConfig()->getName();
                    $param = $correctPath . '_' . $name;
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                } else {
                    $name = $item->getConfig()->getName();
                    $param = $entityName . '_' . $name;
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
                }
            }
            if ($type == "entity") {
                if ($entityName == "product") {
                    $name = $item->getConfig()->getName();
                    $param = $correctPath . '_' . $name;
                    //$this->webDriver->findElement(WebDriverBy::cssSelector('#' . $param . ' + div button'))->click();
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    $this->webDriver->executeScript("jQuery('#" . $param . "+ div a:eq(2)').click();");
//                    $this->webDriver->executeScript("jQuery('#" . $param . "+ div a:eq(2)').click();");
                    $this->webDriver->executeScript("jQuery('#" . $param . "').val(10);");
                } else {
                    $name = $item->getConfig()->getName();
                    $param = $entityName . '_' . $name;
                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
                    sleep(2);
//                    $this->webDriver->executeScript("jQuery('#" . $param . "+ div a:eq(2)').click();");
//                    $this->webDriver->executeScript("jQuery('#" . $param . "+ div option:eq(2)').click();");
//                    $this->webDriver->executeScript("jQuery('#" . $param . "').val(10).prop('selected', true);");
                    $this->webDriver->executeScript("jQuery('#" . $param . "').val(10).change();");
                }
            }
        }

//        
//      
        $this->webDriver->executeScript("jQuery('.btn-success').click();");
        $this->webDriver->close();

//----------------test insert to DB---------------------------------------------
        //check the amount of records in the begin of test
        //Save into DB - check the amount  
        $em = self::$container->get('doctrine.orm.entity_manager');
        $product = new \PhantomBundle\GridConfig\Product();
//        $em->persist($product);
//        $em->flush();
//        $product->getId();
//        dump($product);
        $connection = self::$container->get('doctrine')->getConnection();

        $lastInsertID = $connection->lastInsertId();
//        dump($lastInsertID);
//        exit;
//        $query = $em->createNativeQuery('SELECT id FROM phantom_product');
//        $user = $query->getResult();
//        dump($user);
//        exit;

        $manager = self::$container->get('doctrine')->getManager();
        $repository = $manager->getRepository($pathToEntity);
        $id = 1;


        $query = $em->createQuery("SELECT pr FROM PhantomBundle\Entity\Product pr WHERE pr.id = 160 ");
        $queryLast = $em->createQuery("SELECT COUNT(pr.id) FROM PhantomBundle\Entity\ProductCategory pr");
        $queryLastMax = $em->createQuery("SELECT MAX(pr.id) FROM PhantomBundle\Entity\ProductCategory pr");


        $result = $query->getResult();
        $resultLast = $queryLast->getResult();
        $resultLastMax = $queryLastMax->getResult();
        //----------------------------------------------------------------------------
        //assertion for check edit response 200
        $this->assertResponseOK(\sprintf(
                        'localhost/makeapp/web/app_dev.php/panel/%s/new', $entityName
        ));
    }

    public function testNewProvider() {



        self::$kernel = new \TestAppKernel('test', true);
        self::$kernel->boot();
        self::$container = self::$kernel->getContainer();
        $getAllEntities = self::$container->get('phantom.entities.giveback');
        $entitiesNames = $getAllEntities->checkEntities();


        $faker = Factory::create();
        $app = new \AppKernel('test', true);
        $app->boot();
        $dic = $app->getContainer();
        $model = $dic->get('model_factory');

        foreach ($entitiesNames as $name) {
            $entity = $model->getModel($name[0])->getEntity();
            if (property_exists($entity, "name")) {
                $entity->setName($faker->firstName);
            }
            $entities[] = [$entity];
        }

        return $entities;
    }

//    /**
//     * @dataProvider testEditProvider
//     */
//    public function testEdit($entity) {
//        $entityId = 1;
//        //creating the faker object
//        $faker = Factory::create();
//        //part to prepare a correct entity name
//        //does not need here!
//        $pathToEntity = get_class($entity);
//
//        $pieces = explode('\\', $pathToEntity);
//        $last_word = array_pop($pieces);
//        $entityName = strtolower($last_word);
//
//        //create a model
//        $this->modelFactory = $this->get('model_factory');
//        $this->model = $this->modelFactory->getModel($pathToEntity);
//
//        //create a form
//        $parameters = $this->get('router')->match('/panel/' . $entityName . '/edit/' . $entityId);
//
//
//        $configuratorService = $this->get('prototype.formtype.configurator.service');
//        $formType = $configuratorService->getService($parameters["_route"], $entity);
//
//
//        if (get_class($formType) == 'Core\PrototypeBundle\Form\FormType') {
//            $formType->setModel($this->model);
//        }
//
//        //check the amount of records in the begin of test
//        //build entity manager
////        $em = self::$container->get('doctrine.orm.entity_manager');
////        $queryFirstMax = $em->createQuery("SELECT MAX(pr.id) FROM phantom.productcategory pr");
////        $resultFirstMax = $queryFirstMax->getResult();
////        dump($resultFirstMax);
////        die('first');
//
//        $form = $this->get('form.factory')->create($formType, $entity, []);
//
//        $this->webDriver->get("http://localhost/makeapp/web/app_dev.php/panel/" . $entityName . "/edit/" . $entityId);
//        foreach ($form as $item) {
//            $fieldName = $item->getConfig()->getName();
//            $type = $item->getConfig()->getType()->getName();
//            
//            $attr = $item->getConfig()->getAttributes();
////            $options = $form->get('name')->getConfig()->getOptions();
//            //prepare the correct name for recognizing the element
//            $pathClassName = get_class($form->getData());
//
//            $pathLowerCase = strtolower($pathClassName);
//            $str = substr($pathLowerCase, 0, strrpos($pathLowerCase, '\\'));
//            $correctPath = str_replace("\\", "_", $str);
//
//            //columnTypeGuesser      
//            $generator = new Generator();
//            //add a data providers to the generator
//            $generator->addProvider(new \Faker\Provider\Lorem($generator));
//            $generator->addProvider(new \Faker\Provider\Color($generator));
//            $generator->addProvider(new \Faker\Provider\DateTime($generator));
//            $columnGuesser = new ColumnTypeGuesser($generator);
//
//
//            //---------------guess a format--------------------------------------
//
//            $guess = $columnGuesser->guessFormat(
//                    $fieldName, $this->model->getMetadata()
//            );
//
//
//
////            $typeMetaData = $classMetaData->getTypeOfField($fieldName);
//          
//            if ($type == "text" || $type == "textarea") {
//                if ($entityName == "product") {
//                    $name = $item->getConfig()->getName();
//                    $param = $correctPath . '_' . $name;
//
//                    if ($fieldName == "color") {
//                        $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                        $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                        $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                    }
//                    if ($fieldName == "editor") {
//                        $this->webDriver->executeScript("jQuery('.note-editor').click();");
//                        $this->webDriver->findElement(WebDriverBy::cssSelector("div.note-editable.panel-body"))->clear();
//                        $this->webDriver->findElement(WebDriverBy::cssSelector("div.note-editable.panel-body"))->sendKeys($guess());
//                        //type in the additional editor field
//                        $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                        $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                        $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                    }
//                } else {
//                    $name = $item->getConfig()->getName();
//                    $param = $entityName . '_' . $name;
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                }
//            }
//            if ($type == "number") {
//                if ($entityName == "product") {
//                    $name = $item->getConfig()->getName();
//                    $param = $correctPath . '_' . $name;
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                } else {
//                    $name = $item->getConfig()->getName();
//                    $param = $entityName . '_' . $name;
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                }
//            }
//            if ($type == "datetime") {
//                if ($entityName == "product") {
//                    $name = $item->getConfig()->getName();
//                    $param = $correctPath . '_' . $name;
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                } else {
//                    $name = $item->getConfig()->getName();
//                    $param = $entityName . '_' . $name;
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                }
//            }
//            if ($type == "date") {
//                if ($entityName == "product") {
//                    $name = $item->getConfig()->getName();
//                    $param = $correctPath . '_' . $name;
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                } else {
//                    $name = $item->getConfig()->getName();
//                    $param = $entityName . '_' . $name;
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->clear();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->sendKeys($guess());
//                }
//            }
//            if ($type == "entity") {
//                if ($entityName == "product") {
//                    $name = $item->getConfig()->getName();
//                    $param = $correctPath . '_' . $name;
//                    //$this->webDriver->findElement(WebDriverBy::cssSelector('#' . $param . ' + div button'))->click();
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
////                    $this->webDriver->executeScript("jQuery('#" . $param . "+ div a:eq(2)').click();");
////                    $this->webDriver->executeScript("jQuery('#" . $param . "+ div a:eq(2)').click();");
//                    $this->webDriver->executeScript("jQuery('#" . $param . "').val(10);");
//                } else {
//                    $name = $item->getConfig()->getName();
//                    $param = $entityName . '_' . $name;
//                    $this->webDriver->findElement(WebDriverBy::id($param))->click();
//                    sleep(2);
////                    $this->webDriver->executeScript("jQuery('#" . $param . "+ div a:eq(2)').click();");
////                    $this->webDriver->executeScript("jQuery('#" . $param . "+ div option:eq(2)').click();");
////                    $this->webDriver->executeScript("jQuery('#" . $param . "').val(10).prop('selected', true);");
//                    $this->webDriver->executeScript("jQuery('#" . $param . "').val(10).change();");
//                }
//            }
//        }
//
////        
////      
//        $this->webDriver->executeScript("jQuery('.btn-success').click();");
//        $this->webDriver->close();
//
////----------------test insert to DB---------------------------------------------
//        //check the amount of records in the begin of test
//        //Save into DB - check the amount  
//        $em = self::$container->get('doctrine.orm.entity_manager');
//        $product = new \PhantomBundle\GridConfig\Product();
////        $em->persist($product);
////        $em->flush();
////        $product->getId();
////        dump($product);
//        $connection = self::$container->get('doctrine')->getConnection();
//
//        $lastInsertID = $connection->lastInsertId();
////        dump($lastInsertID);
////        exit;
////        $query = $em->createNativeQuery('SELECT id FROM phantom_product');
////        $user = $query->getResult();
////        dump($user);
////        exit;
//
//        $manager = self::$container->get('doctrine')->getManager();
//        $repository = $manager->getRepository($pathToEntity);
//        $id = 1;
//
//
//        $query = $em->createQuery("SELECT pr FROM PhantomBundle\Entity\Product pr WHERE pr.id = 160 ");
//        $queryLast = $em->createQuery("SELECT COUNT(pr.id) FROM PhantomBundle\Entity\ProductCategory pr");
//        $queryLastMax = $em->createQuery("SELECT MAX(pr.id) FROM PhantomBundle\Entity\ProductCategory pr");
//
//
//        $result = $query->getResult();
//        $resultLast = $queryLast->getResult();
//        $resultLastMax = $queryLastMax->getResult();
////------------------------------------------------------------------------------
//            
//        //assertion for check edit response 200
//        $this->assertResponseOK(\sprintf(
//                        'localhost/makeapp/web/app_dev.php/panel/%s/edit/%d', $entityName, $entityId
//        ));
//    }
//
//    public function testEditProvider() {
//
//        self::$kernel = new \TestAppKernel('test', true);
//        self::$kernel->boot();
//        self::$container = self::$kernel->getContainer();
//        $getAllEntities = self::$container->get('phantom.entities.giveback');
//        $entitiesNames = $getAllEntities->checkEntities();
//
//
//        $faker = Factory::create();
//        $app = new \AppKernel('test', true);
//        $app->boot();
//        $dic = $app->getContainer();
//        $model = $dic->get('model_factory');
//
//        foreach ($entitiesNames as $name) {
//            $entity = $model->getModel($name[0])->getEntity();
//            if (property_exists($entity, "name")) {
//                $entity->setName($faker->firstName);
//            }
//            $entities[] = [$entity];
//        }
//
//        return $entities;
//        //------------------------------------------------
//    }
//    /**
//     * @dataProvider testReadProvider
//     */
//    public function testRead($entity, $entityId = 1) {
//       
//        $this->assertResponseOK(\sprintf(
//                        'localhost/makeapp/web/app_dev.php/panel/%s/read/%d', $this->get('classmapperservice')->getEntityName($entity), $entityId
//        ));
//    }
//    public function testReadProvider() {
////        $entitiesNames = [
////            ['PhantomBundle\Entity\Product', 1],
////        ];
//
//        self::$kernel = new \TestAppKernel('test', true);
//        self::$kernel->boot();
//        self::$container = self::$kernel->getContainer();
//
//        $getAllEntities = self::$container->get('phantom.entities.giveback');
//        $entitiesNames = $getAllEntities->checkEntities();
//
//        return $entitiesNames;
//    }
//    /**
//     * @dataProvider testListProvider
//     */
//    public function testList($entity) {
//        //assertion checks response 200
//        $this->assertResponseOK(\sprintf(
//                        'localhost/makeapp/web/app_dev.php/panel/%s/list', $this->get('classmapperservice')->getEntityName($entity)
//        ));
//    }
//
//    public function testListProvider() {
//        //create a container here for a test working without issues
//        self::$kernel = new \TestAppKernel('test', true);
//        self::$kernel->boot();
//        self::$container = self::$kernel->getContainer();
//
//        $getAllEntities = self::$container->get('phantom.entities.giveback');
//        $entitiesNames = $getAllEntities->checkEntities();
//
//        return $entitiesNames;
//    }
//-----------------------OLD------------------------------------------------------
//    public function provideNewFixtures() {
//
//
//        $entitiesNames = [
//        ];
//
//
//        $entities = [
//        ];
//
//        $app = new \AppKernel('test', true);
//        $app->boot();
//        $dic = $app->getContainer();
//        $model = $dic->get('model_factory');
//        $faker = Factory::create();
//
//        foreach ($entitiesName as $name) {
//            $entity = $model->getModel($name)->getEntity();
//            $entity->setName($faker->firstName);
//            $entity->setPrice($faker->numerify());
//            $entity->setDate($faker->date());
//            $entity->setDatetime($faker->time());
//            $entities[] = [$entity];
//        }
//
//        return $entities;
//    }
//-----------------------------OLD------------------------------------------------


    public function assertResponseOK($url) {
        $urlChecker = new UrlChecker();
        $this->assertSame($urlChecker, $urlChecker->waitUntilAvailable(200, $url));
    }

    public function byCss($css) {
        return $this->webDriver->findElement(WebDriverBy::cssSelector($css))->click();
    }

    public function assertByCss($css) {
        $this->assertNotNull(
                $this->byCss($css), \sprintf('Element with css selector \'%s\' was not found', $css)
        );
    }

    public function byId($id) {
        return $this->webDriver->findElement(WebDriverBy::id($id))->click();
    }

    public function byXpath($xpath) {
        return $this->webDriver->findElement(WebDriverBy::xpath($id))->click();
    }
    
    
    
    
    
    
}

