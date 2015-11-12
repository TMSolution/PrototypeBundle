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

class EntityTest extends WebTestCase {

    private static $entitiesNames;
    protected static $kernel;
    protected static $container;
    protected $webDriver;
    protected static $generator;
    protected $manager;
    protected $modelFactory;
    protected $model;

    public function __call($method, $arguments) {
        if (!method_exists($method)) {
            throw new \BadMethodCallException(sprintf("Undefined method %s", $method));
        }
        return $this->$method($arguments);
    }

    public function typeText($elementName, $guess) {
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeNumber($elementName, $guess) {
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeColor($elementName, $guess) {
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeEditor($elementName, $guess) {
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeDate($elementName, $guess) {
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeDatetime($elementName, $guess) {
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeTextarea($elementName, $guess) {
//        $this->webDriver->executeScript("jQuery('.note-editor').click();");
//        $this->webDriver->findElement(WebDriverBy::cssSelector("div.note-editable.panel-body"))->clear();
//        $this->webDriver->findElement(WebDriverBy::cssSelector("div.note-editable.panel-body"))->sendKeys($guess());
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeEntity($elementName, $guess) {
        $this->webDriver->findElement(WebDriverBy::id($elementName))->click();
        $this->webDriver->executeScript("jQuery('#" . $elementName . "').val(10);");
    }

    public function typeHidden() {
        
    }

    public function getEntityName($entity) {
        $pathToEntity = get_class($entity);
        $pieces = explode('\\', $pathToEntity);
        $last_word = array_pop($pieces);
        $entityName = strtolower($last_word);
        return $entityName;
    }

    public function createForm($entityFriendlyName, $entity, $typeOfFormAsString) {
        $this->modelFactory = $this->get('model_factory');
        $this->model = $this->modelFactory->getModel(get_class($entity));
        $parameters = $this->get('router')->match('/panel/' . $entityFriendlyName . $typeOfFormAsString);
        $configuratorService = $this->get('prototype.formtype.configurator.service');
        $formType = $configuratorService->getService($parameters["_route"], $entity);
        if (get_class($formType) == 'Core\PrototypeBundle\Form\FormType') {
            $formType->setModel($this->model);
        }
        $form = $this->get('form.factory')->create($formType, $entity, []);
        return $form;
    }

    public function guessTypeColumn() {
        $generator = new Generator();
        $generator->addProvider(new \Faker\Provider\Lorem($generator));
        $generator->addProvider(new \Faker\Provider\Color($generator));
        $generator->addProvider(new \Faker\Provider\DateTime($generator));
        $columnGuesser = new ColumnTypeGuesser($generator);
        return $columnGuesser;
    }

    public function getEntities() {
        self::$kernel = new \TestAppKernel('test', true);
        self::$kernel->boot();
        self::$container = self::$kernel->getContainer();
        $getAllEntities = self::$container->get('phantom.entities.getallentities');
        $entitiesNames = $getAllEntities->checkEntities();
        return $entitiesNames;
    }

    public function getLastWordEntity($entity) {
        return ucfirst(
                mb_strtolower((
                        new \ReflectionClass($entity)
                        )->getShortName()
        ));
    }

    public function get($serviceId) {
        return self::$container->get($serviceId);
    }

    public function setUp() {
        $capabilities = array(WebDriverCapabilityType::BROWSER_NAME => 'firefox');
        $this->webDriver = RemoteWebDriver::create('http://127.0.0.1:4444/wd/hub', $capabilities);
        $this->webDriver->manage()->timeouts()->implicitlyWait(4);
    }
    
     public function tearDown()
    {
        $this->webDriver->quit();
    }

    /**
     * @dataProvider testEntitiesProvider
     */
    public function testNew($entity) {
        if (get_class($entity != "PhantomBundle\Entity\File")) {
            $entityFriendlyName = $this->getEntityName($entity);
            $this->modelFactory = $this->get('model_factory');
            $this->model = $this->modelFactory->getModel(get_class($entity));
            $typeForm = '/new';
            $entityForm = $this->createForm($entityFriendlyName, $entity, $typeForm);
            $this->webDriver->get("http://localhost/makeapp/web/app_dev.php/panel/" . $entityFriendlyName . "/new");
            foreach ($entityForm as $item) {
                $fieldName = $item->getConfig()->getName();
                $type = $item->getConfig()->getType()->getName();

                $guess = $this->guessTypeColumn()->guessFormat(
                        $fieldName, $this->model->getMetadata()
                );
                $name = $item->getConfig()->getName();
                $elementName = $entityFriendlyName . '_' . $name;
                $elementHandlerName = \sprintf("type%s", ucfirst($type));
                $this->$elementHandlerName($elementName, $guess);
            }
            $this->webDriver->executeScript("jQuery('.btn-success').click();");
            sleep(3);
            $this->assertResponseOK(\sprintf(
                            'localhost/makeapp/web/app_dev.php/panel/%s/new', $entityFriendlyName
            ));
        }
    }

    public function testEntitiesProvider() {
        self::$kernel = new \TestAppKernel('test', true);
        self::$kernel->boot();
        self::$container = self::$kernel->getContainer();
        $getAllEntities = self::$container->get('phantom.entities.getallentities');
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

    /**
     * @dataProvider testEntitiesProvider
     */
    public function testEdit($entity) {
        $entityId = 1;
        $entityFriendlyName = $this->getEntityName($entity);
        $this->modelFactory = $this->get('model_factory');
        $this->model = $this->modelFactory->getModel(get_class($entity));
        $typeForm = '/new';
        $entityForm = $this->createForm($entityFriendlyName, $entity, $typeForm);
        $this->webDriver->get("http://localhost/makeapp/web/app_dev.php/panel/" . $entityFriendlyName . '/edit/' . $entityId);
        foreach ($entityForm as $item) {
            $fieldName = $item->getConfig()->getName();
            $type = $item->getConfig()->getType()->getName();
            $guess = $this->guessTypeColumn()->guessFormat(
                    $fieldName, $this->model->getMetadata()
            );
            $name = $item->getConfig()->getName();
            $elementName = $entityFriendlyName . '_' . $name;
            $elementHandlerName = \sprintf("type%s", ucfirst($type));
            $this->$elementHandlerName($elementName, $guess);
        }
        $this->webDriver->executeScript("jQuery('.btn-success').click();");
        sleep(4);
        $this->assertSaveToDB($entity);
        $this->assertResponseOK(\sprintf(
                        'localhost/makeapp/web/app_dev.php/panel/%s/edit/%d', $entityFriendlyName, $entityId
        ));
    }

    /**
     * @dataProvider testReadProvider
     */
    public function testRead($entity, $entityId = 1) {
        $this->assertResponseOK(\sprintf(
                        'localhost/makeapp/web/app_dev.php/panel/%s/read/%d', $this->get('classmapperservice')->getEntityName($entity), $entityId
        ));
    }

    public function testReadProvider() {
        $entitiesNames = $this->getEntities();
        return $entitiesNames;
    }

    /**
     * @dataProvider testListProvider
     */
    public function testList($entity) {
        $this->assertResponseOK(\sprintf(
                        'localhost/makeapp/web/app_dev.php/panel/%s/list', $this->get('classmapperservice')->getEntityName($entity)
        ));
    }

    public function testListProvider() {
        $entitiesNames = $this->getEntities();
        return $entitiesNames;
    }

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

    public function assertSaveToDB($entity) {
        $word = $this->getLastWordEntity($entity);
        $headerWord = 'Show ' . $word . ':';
        $headerValue = $this->webDriver->findElement(WebDriverBy::tagName("h2"))->getText();
        $this->assertSame($headerWord, $headerValue);
    }

}
