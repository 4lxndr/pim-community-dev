<?php

namespace Strixos\CatalogBundle\Entity;

use Strixos\CoreBundle\Model\AbstractModel;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @author     Nicolas Dupont @ Strixos
 * @copyright  Copyright (c) 2012 Strixos SAS (http://www.strixos.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * Strixos\CatalogBundle\Entity\Set
 *
 * @ORM\Table(name="StrixosCatalog_Set")
 * @ORM\Entity
 */
class Set extends AbstractModel
{

    /**
    * @var integer $id
     *
    * @ORM\Column(name="id", type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
    * @var string $code
    *
    * @ORM\Column(name="code", type="string", length=255, unique=true)
    */
    private $code;

    /**
     * @ORM\OneToMany(targetEntity="Group",mappedBy="set", cascade={"persist", "remove"})
     */
    protected $groups;

    /**
    * Constructor
    */
    public function __construct()
    {
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set code
     *
     * @param string $code
     * @return AttributeSet
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Copy an attribute set
     *
     * @return AttributeSet $set
     */
    public function copy($newCode)
    {
        $copySet = new Set();
        $copySet->setCode($newCode);
        foreach ($this->getGroups() as $groupToCopy) {
            $copyGroup = new Group();
            $copyGroup->setCode($groupToCopy->getCode());
            foreach ($groupToCopy->getAttributes() as $attributeToLink) {
                $copyGroup->addAttribute($attributeToLink);
            }
            // add group to default set
            $copySet->addGroup($copyGroup);
            // link group to set
            $copyGroup->setSet($copySet);
        }
        return $copySet;
    }

    /**
     * Add groups
     *
     * @param Strixos\CatalogBundle\Entity\Group $groups
     * @return Set
     */
    public function addGroup(\Strixos\CatalogBundle\Entity\Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param Strixos\CatalogBundle\Entity\Group $groups
     */
    public function removeGroup(\Strixos\CatalogBundle\Entity\Group $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }
}