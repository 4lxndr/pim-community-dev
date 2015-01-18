<?php

namespace Pim\Bundle\BaseConnectorBundle\Processor\Denormalization;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Bundle\StorageUtilsBundle\Doctrine\ObjectDetacherInterface;
use Pim\Bundle\CatalogBundle\Model\GroupInterface;
use Pim\Bundle\CatalogBundle\Model\ProductValueInterface;
use Pim\Bundle\CatalogBundle\Repository\ReferableEntityRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\ValidatorInterface;

/**
 * Variant group import processor, allows to,
 *  - create / update variant groups
 *  - bind values data into a product template linked to a variant group
 *  - validate values and save values in template (it erases existing values)
 *  - return the valid variant groups, throw exceptions to skip invalid ones
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class VariantGroupProcessor extends AbstractProcessor
{
    /** @staticvar string */
    const CODE_FIELD = 'code';

    /** @staticvar string */
    const TYPE_FIELD = 'type';

    /** @staticvar string */
    const AXIS_FIELD = 'axis';

    /** @staticvar string */
    const LABEL_FIELD = 'label';

    /** @var NormalizerInterface */
    protected $valueNormalizer;

    /** @var string */
    protected $templateClass;

    /** @var string */
    protected $format;

    /**
     * @param ReferableEntityRepositoryInterface $groupRepository
     * @param DenormalizerInterface              $groupValuesDenormalizer
     * @param ValidatorInterface                 $validator
     * @param NormalizerInterface                $valueNormalizer
     * @param ObjectDetacherInterface            $detacher
     * @param string                             $groupClass
     * @param string                             $templateClass
     * @param string                             $format
     */
    public function __construct(
        ReferableEntityRepositoryInterface $groupRepository,
        DenormalizerInterface $groupValuesDenormalizer,
        ValidatorInterface $validator,
        NormalizerInterface $valueNormalizer,
        ObjectDetacherInterface $detacher,
        $groupClass,
        $templateClass,
        $format
    ) {
        parent::__construct($groupRepository, $groupValuesDenormalizer, $validator, $detacher, $groupClass);
        $this->valueNormalizer = $valueNormalizer;
        $this->templateClass   = $templateClass;
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        $item[self::TYPE_FIELD] = 'VARIANT';
        $variantGroup = $this->findOrCreateVariantGroup($item);
        $this->updateVariantGroup($variantGroup, $item);
        $this->updateVariantGroupValues($variantGroup, $item);
        $this->validateVariantGroup($variantGroup, $item);

        return $variantGroup;
    }

    /**
     * Find or create the variant group
     *
     * @param array $groupData
     *
     * @return GroupInterface
     */
    protected function findOrCreateVariantGroup(array $groupData)
    {
        $variantGroup = $this->findOrCreateObject($this->repository, $groupData, $this->class);
        $isExistingGroup = $variantGroup->getId() !== null && $variantGroup->getType()->isVariant() === false;
        if ($isExistingGroup) {
            $this->skipItemWithMessage(
                $groupData,
                sprintf('Cannot process group "%s", only variant groups are accepted', $groupData[self::CODE_FIELD])
            );
        }

        return $variantGroup;
    }

    /**
     * Update the variant group fields
     *
     * @param GroupInterface $variantGroup
     * @param array          $item
     *
     * @return GroupInterface
     */
    protected function updateVariantGroup(GroupInterface $variantGroup, array $item)
    {
        $variantGroupData = $this->filterVariantGroupData($item, true);
        $variantGroup = $this->denormalizer->denormalize(
            $variantGroupData,
            $this->class,
            $this->format,
            ['entity' => $variantGroup]
        );

        return $variantGroup;
    }

    /**
     * Update the variant group values
     *
     * @param GroupInterface $variantGroup
     * @param array          $item
     */
    protected function updateVariantGroupValues(GroupInterface $variantGroup, array $item)
    {
        $valuesData = $this->filterVariantGroupData($item, false);
        if (!empty($valuesData)) {
            $values = $this->denormalizeValuesFromItemData($valuesData);
            $this->validateValues($variantGroup, $values, $item);
            $template = $this->getProductTemplate($variantGroup);
            $structuredValuesData = $this->normalizeValuesToStructuredData($values);
            $template->setValuesData($structuredValuesData);
        }
    }

    /**
     * @param GroupInterface $variantGroup
     * @param array          $item
     *
     * @throws InvalidItemException
     */
    protected function validateVariantGroup(GroupInterface $variantGroup, array $item)
    {
        $violations = $this->validator->validate($variantGroup);
        if ($violations->count() !== 0) {
            $this->detachObject($variantGroup);
            $this->skipItemWithConstraintViolations($item, $violations);
        }
    }

    /**
     * Filters the item data to keep only variant group fields (code, axis, labels) or template product values
     *
     * @param array $item
     * @param bool  $keepOnlyFields if true keep only code, axis, labels, else keep only values
     *
     * @return array
     */
    protected function filterVariantGroupData(array $item, $keepOnlyFields = true)
    {
        foreach (array_keys($item) as $field) {
            $isCodeOrAxis = in_array($field, [self::CODE_FIELD, self::TYPE_FIELD, self::AXIS_FIELD]);
            $isLabel = false !== strpos($field, self::LABEL_FIELD, 0);
            if ($keepOnlyFields && !$isCodeOrAxis && !$isLabel) {
                unset($item[$field]);
            } elseif (!$keepOnlyFields && ($isCodeOrAxis || $isLabel)) {
                unset($item[$field]);
            }
        }

        return $item;
    }

    /**
     * @param GroupInterface          $variantGroup
     * @param ProductValueInterface[] $values
     * @param array                   $item
     *
     * @throw InvalidItemException
     */
    protected function validateValues(GroupInterface $variantGroup, array $values, array $item)
    {
        foreach ($values as $value) {
            $violations = $this->validator->validate($value);
            if ($violations->count() !== 0) {
                $this->detachObject($variantGroup);
                $this->skipItemWithConstraintViolations($item, $violations);
            }
        }
    }

    /**
     * Filter empt values then denormalize the product values objects from CSV fields
     *
     * @param array $rawProductValues
     *
     * @return ProductValueInterface[]
     */
    protected function denormalizeValuesFromItemData(array $rawProductValues)
    {
        $nonEmptyValues = $rawProductValues;
        foreach ($nonEmptyValues as $index => $data) {
            if (trim($data) === "") {
                unset($nonEmptyValues[$index]);
            }
        }

        return $this->denormalizer->denormalize($nonEmptyValues, 'ProductValue[]', 'csv');
    }

    /**
     * Normalize product values objects to JSON format
     *
     * @param ProductValueInterface[] $values
     *
     * @return array
     */
    protected function normalizeValuesToStructuredData(array $values)
    {
        $normalizedValues = [];

        foreach ($values as $value) {
            $attributeCode = $value->getAttribute()->getCode();
            $normalizedValues[$attributeCode][] = $this->valueNormalizer->normalize(
                $value,
                'json',
                ['entity' => 'product']
            );
        }

        return $normalizedValues;
    }

    /**
     * @param GroupInterface $variantGroup
     *
     * @return \Pim\Bundle\CatalogBundle\Model\ProductTemplateInterface
     */
    protected function getProductTemplate(GroupInterface $variantGroup)
    {
        if ($variantGroup->getProductTemplate()) {
            $template = $variantGroup->getProductTemplate();
        } else {
            $template = new $this->templateClass();
            $variantGroup->setProductTemplate($template);
        }

        return $template;
    }
}
