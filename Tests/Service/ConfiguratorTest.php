<?php

namespace Core\PrototypeBundle\Tests\Service;


use Core\PrototypeBundle\Service\Configurator;
use Core\PrototypeBundle\DependencyInjection\Compiler\ConfigPass;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

class ConfiguratorTest  extends \PHPUnit_Framework_TestCase  {

  protected $_application;
  
  
  public function getContainer()
  {
    return $this->_application->getKernel()->getContainer();
  }
 
  public function setUp()
  {
    require_once(__DIR__ . "/../../../../../app/AppKernel.php");
    $kernel = new \AppKernel("test", true);
    $kernel->boot();
    $this->_application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
    $this->_application->setAutoExit(false);
    
   
  
  } 
    
   public function  testGetService()
   {
       $route="core_prototype_associationcontroller_";
       $entity='PrototypeBundle\Entity\Test';
               
       $configPass=new ConfigPass($this->getContainer());
       $configuratorService=$this->getContainer()->get('prototype.configurator.service');
       $namesOfServices=$configuratorService->getNamesOfServices();
       $service=$configuratorService->getService($route, $entity);
       dump($configuratorService->getChosen());
       dump($service);
     
       
       
       $this->assertEquals(1,1);
   } 
    

}
