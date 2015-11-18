<?php

/**
 * Description of EntityTest
 *
 * Testing all entities from the bundles.
 * 
 *  
 * @author Łukasz Sobieraj
 */

namespace CCO\CallCenterBundle\Tests;

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
//        die('jestem w color');
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeEditor($elementName, $guess) {
//        die('jestem w editor');
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeDate($elementName, $guess) {
//        die('jestem w date');
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeDatetime($elementName, $guess) {
        $month = $elementName . '_date_month_chosen';
        $day = $elementName . '_date_day_chosen';
        $year = $elementName . '_date_year_chosen';
        $this->webDriver->findElement(WebDriverBy::id($month))->click();
        $this->webDriver->executeScript("jQuery('#" . $month . "').val(10);");
        $this->webDriver->findElement(WebDriverBy::id($day))->click();
        $this->webDriver->executeScript("jQuery('#" . $day . "').val(10);");
        $this->webDriver->findElement(WebDriverBy::id($year))->click();
        $this->webDriver->executeScript("jQuery('#" . $year . "').val(10);");
    }

    public function typeTextarea($elementName, $guess) {
//        $this->webDriver->executeScript("jQuery('.note-editor').click();");
//        $this->webDriver->findElement(WebDriverBy::cssSelector("div.note-editable.panel-body"))->clear();
//        $this->webDriver->findElement(WebDriverBy::cssSelector("div.note-editable.panel-body"))->sendKeys($guess());
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeEntity($elementName, $guess) {
        //jQuery("#campaign_campaigntype_chosen").prev().trigger('chosen:open');
        $correctEelementName = $elementName . '_chosen';
        $this->webDriver->findElement(WebDriverBy::id($correctEelementName))->click();
        $this->webDriver->executeScript("jQuery('#" . $correctEelementName . "').prev().trigger('chosen:open');");
        $this->webDriver->executeScript("jQuery('#" . $correctEelementName . "').prev().val(10);");
        //jQuery("#campaign_campaigntype_chosen").prev().trigger('chosen:open'); - dopracowac jutro
        //jQuery("#campaign_campaigntype_chosen").prev().val(9);
    }

    public function typeInteger($elementName, $guess) {
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }
    
    public function typeCheckbox($elementName, $guess){
        $this->webDriver->findElement(WebDriverBy::id($elementName))->clear();
        $this->webDriver->findElement(WebDriverBy::id($elementName))->sendKeys($guess());
    }

    public function typeHidden() {
        
    }

    public function getShortName($entity) {
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

    public function getShortNameBundle($entityName) {
        $fullName = $entityName[0];
        $pieces = explode('\\', $fullName);
        $shortName = $pieces[0];
        return $shortName;
    }

    public function get($serviceId) {
        return self::$container->get($serviceId);
    }

    public function setUp() {
        $capabilities = array(WebDriverCapabilityType::BROWSER_NAME => 'firefox');
        $this->webDriver = RemoteWebDriver::create('http://127.0.0.1:4444/wd/hub', $capabilities);
        $this->webDriver->manage()->timeouts()->implicitlyWait(4);
    }

    public function tearDown() {
        $this->webDriver->quit();
    }

    /**
     * @dataProvider entitiesProvider
     */
    public function testNew($entity) {
        $friendlyName = $this->getShortName($entity);
        $this->modelFactory = $this->get('model_factory');
        $this->model = $this->modelFactory->getModel(get_class($entity));
        $form = $this->createForm($friendlyName, $entity, '/container/default/new');
        $this->webDriver->get("http://localhost/callcenterproject/web/app_dev.php/panel/" . $friendlyName . '/container/default/new');
        foreach ($form as $item) {
            $fieldName = $item->getConfig()->getName();
            $type = $item->getConfig()->getType()->getName();
            $guess = $this->guessTypeColumn()->guessFormat(
                    $fieldName, $this->model->getMetadata()
            );
            $name = $item->getConfig()->getName();
            $elementName = $friendlyName . '_' . $name;
            $elementHandlerName = \sprintf("type%s", ucfirst($type));
            $this->$elementHandlerName($elementName, $guess);
        }
        $this->webDriver->executeScript("jQuery('.btn-primary ').click();");
        sleep(4);
        $this->assertSaveToDB($entity);
        $this->assertResponseOK(\sprintf(
                        'localhost/callcenterproject/web/app_dev.php/panel/%s/container/default/new', $friendlyName
        ));
    }

    public function entitiesProvider() {
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
            $shortName = $this->getShortNameBundle($name);
            if ($shortName == "CCO") {
                $entity = $model->getModel($name[0])->getEntity();
                if (property_exists($entity, "name")) {
                    $entity->setName($faker->firstName);
                }
                $entities[] = [$entity];
            }
        }
        return $entities;
    }

    /**
     * @dataProvider entitiesProvider
     */
    public function testEdit($entity) {
        $entityId = 1;
        $friendlyName = $this->getShortName($entity);
        $this->modelFactory = $this->get('model_factory');
        $this->model = $this->modelFactory->getModel(get_class($entity));
        $form = $this->createForm($friendlyName, $entity, '/container/default/edit/1');
        $this->webDriver->get("http://localhost/callcenterproject/web/app_dev.php/panel/" . $friendlyName . '/container/default/edit/' . $entityId);
        foreach ($form as $item) {
            $fieldName = $item->getConfig()->getName();
            $type = $item->getConfig()->getType()->getName();
            $guess = $this->guessTypeColumn()->guessFormat(
                    $fieldName, $this->model->getMetadata()
            );
            $name = $item->getConfig()->getName();
            $elementName = $friendlyName . '_' . $name;
            $elementHandlerName = \sprintf("type%s", ucfirst($type));
            $this->$elementHandlerName($elementName, $guess);
        }
        $this->webDriver->executeScript("jQuery('.btn-primary ').click();");
        sleep(4);
        $this->assertSaveToDB($entity);
        $this->assertResponseOK(\sprintf(
                        'localhost/callcenterproject/web/app_dev.php/panel/%s/container/default/edit/%d', $friendlyName, $entityId
        ));
    }

    /**
     * @dataProvider response200Provider
     */
    public function testRead($entity, $entityId = 1) {
        $this->assertResponseOK(\sprintf(
                        'localhost/callcenterproject/web/app_dev.php/panel/%s/container/default/read/%d', $this->get('classmapperservice')->getEntityName($entity), $entityId
        ));
    }

    /**
     * @dataProvider response200Provider
     */
    public function testUpdate($entity, $entityId = 1) {
        $this->assertResponseOK(\sprintf(
                        'localhost/callcenterproject/web/app_dev.php/panel/%s/container/default/update/%d', $this->get('classmapperservice')->getEntityName($entity), $entityId
        ));
    }

    /**
     * @dataProvider response200Provider
     */
    public function testList($entity) {
        $this->assertResponseOK(\sprintf(
                        'localhost/callcenterproject/web/app_dev.php/panel/%s/list', $this->get('classmapperservice')->getEntityName($entity)
        ));
    }

    public function response200Provider() {
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
        $upper = strtoupper($word);
        $headerWord = $upper . ' / ALA MA KOTA A KOT MAALE';
        $headerValue = $this->webDriver->findElement(WebDriverBy::tagName("h2"))->getText();
        $this->assertSame($headerWord, $headerValue);
    }

}
