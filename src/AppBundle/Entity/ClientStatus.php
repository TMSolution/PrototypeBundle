<?php
namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="owca_client_status")
 *
 */


class ClientStatus
{
     /**
      * @ORM\Id
      * @ORM\Column(type="integer", options= {"comment":"[PODSTAWOWE ELEMENTY SYSTEMU]Tabela zawiera dane słownikowe statusów klientów."})
      * @ORM\GeneratedValue(strategy="AUTO")
      */
     protected $id;
     /**
      * @ORM\Column(type="string", length=200)
      */
     protected $name;

    
     /**
      * @ORM\Column(type="string", length=200)
      */
     protected $name2;
     
     
    /**
     * @ORM\OneToMany(targetEntity="County", mappedBy="county" , cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $countys;
     
    
    public function __construct()
   {
        $this->countys== new \Doctrine\Common\Collections\ArrayCollection();
        
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
     * @return ClientStatus
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

    /**
     * Set name2
     *
     * @param string $name2
     * @return ClientStatus
     */
    public function setName2($name2)
    {
        $this->name2 = $name2;

        return $this;
    }

    /**
     * Get name2
     *
     * @return string 
     */
    public function getName2()
    {
        return $this->name2;
    }

    /**
     * Add countys
     *
     * @param \AppBundle\Entity\County $countys
     * @return ClientStatus
     */
    public function addCounty(\AppBundle\Entity\County $countys)
    {
        $this->countys[] = $countys;

        return $this;
    }

    /**
     * Remove countys
     *
     * @param \AppBundle\Entity\County $countys
     */
    public function removeCounty(\AppBundle\Entity\County $countys)
    {
        $this->countys->removeElement($countys);
    }

    /**
     * Get countys
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCountys()
    {
        return $this->countys;
    }
}
