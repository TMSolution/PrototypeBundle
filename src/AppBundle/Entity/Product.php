<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="app_product")
 *
 */
class Product {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=200)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="ProductCategory", inversedBy="products")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected $productCategory;

    /**
     * @ORM\OneToMany(targetEntity="Param", mappedBy="product")
     */
    protected $params;

    public function __construct() {
        $this->params = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Product
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set productCategory
     *
     * @param \AppBundle\Entity\ProductCategory $productCategory
     * @return Product
     */
    public function setProductCategory(\AppBundle\Entity\ProductCategory $productCategory = null) {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * Get productCategory
     *
     * @return \AppBundle\Entity\ProductCategory 
     */
    public function getProductCategory() {
        return $this->productCategory;
    }


    /**
     * Add params
     *
     * @param \AppBundle\Entity\Param $params
     * @return Product
     */
    public function addParam(\AppBundle\Entity\Param $params)
    {
        $this->params[] = $params;

        return $this;
    }

    /**
     * Remove params
     *
     * @param \AppBundle\Entity\Param $params
     */
    public function removeParam(\AppBundle\Entity\Param $params)
    {
        $this->params->removeElement($params);
    }

    /**
     * Get params
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getParams()
    {
        return $this->params;
    }
    
    public function __toString()
    {
        
        return $this->name;
        
    }
    
}
