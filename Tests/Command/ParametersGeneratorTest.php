<?php

namespace Core\PrototypeBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Core\PrototypeBundle\Command\GenerateTwigCommand as GenerateTwigCommand;
use Symfony\Component\Console\Tester\CommandTester;
use \Core\PrototypeBundle\Generator\ParametersGenerator as ParametersGenerator; 
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use \Core\PrototypeBundle\Generator\ServiceGenerator as ServiceGenerator;

 class ParametersGeneratorTest extends KernelTestCase {

   
    private $container;

    public function setUp()
    {
       self::bootKernel();
       $this->container = self::$kernel->getContainer();
    }
    
    protected function getContainer()
    {
        return $this->container;
    }

    public function testGeneration() {

      
        $parametersGenerator=new ParametersGenerator($this->getContainer(),"CCO\CallCenterBundle\Entity\JobMarketAd","Test","list","Conatiner", "test.html.twig");
        $parametersGenerator->generate();
        $parametersGenerator=new ParametersGenerator($this->getContainer(),"CCO\CallCenterBundle\Entity\JobMarketAd","Test","list","Element", "element.html.twig");
        $parametersGenerator->generate();
        
        $parametersGenerator=new ParametersGenerator($this->getContainer(),"CCO\CallCenterBundle\Entity\JobMarketAd","Test","update","Element", "update.html.twig");
        $parametersGenerator->generate();
        
        $parametersGenerator=new ParametersGenerator($this->getContainer(),"CCO\CallCenterBundle\Entity\JobMarketAd","Test","create","Element", "update.html.twig");
        $parametersGenerator->generate();
        
        
        
        
        $parametersGenerator = new ServiceGenerator($this->getContainer(), "CCO\CallCenterBundle\Entity\JobMarketAd", "Test", "ListConfig","supervisor","subsupervisor","parentsupervisor", ["%jakie_tam%"]);
        $parametersGenerator->generate();
        
        $parametersGenerator = new ServiceGenerator($this->getContainer(), "CCO\CallCenterBundle\Entity\JobMarketAd", "Test", "Config","supervisor","subsupervisor","parentsupervisor");
        $parametersGenerator->generate();
        
      
  
    }

    protected function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        $this->rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    protected function tearDown() {
   }

}
