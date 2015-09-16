<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Description of County
 * 
 *
 * 
 * @ORM\Entity()
 * @ORM\Table(name="county")
 */
class County
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options= {"comment":"[PODSTAWOWE ELEMENTY SYSTEMU]Tabela zawiera dane słownikowe województw."})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    //put your code here

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $name;
    
    
    protected $county;

    
    public function __toString()
    {
        return $this->name;
    }
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return County
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
    
    
  
}
