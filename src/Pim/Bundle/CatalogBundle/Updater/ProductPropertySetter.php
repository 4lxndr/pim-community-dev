<?php

namespace Pim\Bundle\CatalogBundle\Updater;

use Akeneo\Component\StorageUtils\Updater\PropertySetterInterface;
use Doctrine\Common\Util\ClassUtils;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Repository\AttributeRepositoryInterface;
use Pim\Bundle\CatalogBundle\Updater\Setter\SetterRegistryInterface;

/**
 * Sets a property of a product
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductPropertySetter implements PropertySetterInterface
{
    /** @var AttributeRepositoryInterface */
    protected $attributeRepository;

    /** @var SetterRegistryInterface */
    protected $setterRegistry;

    /** array */
    protected $attributesCache;

    /**
     * @param AttributeRepositoryInterface $repository
     * @param SetterRegistryInterface      $setterRegistry
     */
    public function __construct(
        AttributeRepositoryInterface $repository,
        SetterRegistryInterface $setterRegistry
    ) {
        $this->attributeRepository = $repository;
        $this->setterRegistry      = $setterRegistry;
        $this->attributesCache     = [];
    }

    /**
     * {@inheritdoc}
     */
    public function setData($product, $field, $data, array $options = [])
    {
        if (!$product instanceof ProductInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expects a "Pim\Bundle\CatalogBundle\Model\ProductInterface", "%s" provided.',
                    ClassUtils::getClass($product)
                )
            );
        }

        $attribute = $this->getAttribute($field);
        if (null !== $attribute) {
            $setter = $this->setterRegistry->getAttributeSetter($attribute);
        } else {
            $setter = $this->setterRegistry->getFieldSetter($field);
        }

        if (null === $setter) {
            throw new \LogicException(sprintf('No setter found for field "%s"', $field));
        }

        if (null !== $attribute) {
            $setter->setAttributeData($product, $attribute, $data, $options);
        } else {
            $setter->setFieldData($product, $field, $data, $options);
        }

        return $this;
    }

    /**
     * @param string $code
     *
     * @return AttributeInterface|null
     */
    protected function getAttribute($code)
    {
        if (!isset($this->attributesCache[$code])) {
            $attribute = $this->attributeRepository->findOneBy(['code' => $code]);
            $this->attributesCache[$code]= $attribute;
        }

        return isset($this->attributesCache[$code]) ? $this->attributesCache[$code] : null;
    }
}
