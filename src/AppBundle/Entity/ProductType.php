<?php 
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * @ORM\Entity
 * @ORM\Table(name="app_product_type")
 *
 */

class ProductType
{
     /**
      * @ORM\Id
      * @ORM\Column(type="integer")
      * @ORM\GeneratedValue(strategy="AUTO")
      */
     protected $id;
     
  
  
    
     /**
     * @ORM\ManyToMany(targetEntity="Product", mappedBy="productCategory")
     */
    protected $products;
    

    

    public function __construct()
    {
        $this->products = new ArrayCollection();
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
      * @ORM\Column(type="string", length=200)
      */
     protected $name1;
  
    
    /**
     * Add products
     *
     * @param \AppBundle\Entity\Product $products
     * @return ProductType
     */
    public function addProduct(\AppBundle\Entity\Product $products)
    {
        $this->products[] = $products;

        return $this;
    }

    /**
     * Remove products
     *
     * @param \AppBundle\Entity\Product $products
     */
    public function removeProduct(\AppBundle\Entity\Product $products)
    {
        $this->products->removeElement($products);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProducts()
    {
        return $this->products;
    }
    
    
    public function __toString()
    {
        return (string)$this->id;
    }
    

    /**
     * Set name1
     *
     * @param string $name1
     * @return ProductType
     */
    public function setName1($name1)
    {
        $this->name1 = $name1;

        return $this;
    }

    /**
     * Get name1
     *
     * @return string 
     */
    public function getName1()
    {
        return $this->name1;
    }
}
