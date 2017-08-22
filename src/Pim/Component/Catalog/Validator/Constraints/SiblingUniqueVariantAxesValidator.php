<?php

namespace Pim\Component\Catalog\Validator\Constraints;

use Pim\Component\Catalog\FamilyVariant\EntityWithFamilyVariantAttributesProvider;
use Pim\Component\Catalog\Model\EntityWithFamilyVariantInterface;
use Pim\Component\Catalog\Model\EntityWithValuesInterface;
use Pim\Component\Catalog\Repository\EntityWithVariantFamilyRepositoryInterface;
use Pim\Component\Catalog\Validator\UniqueAxesCombinationSet;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * TODO: Rename to UniqueVariantAxisValidator once VariantGroup are dropped.
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class SiblingUniqueVariantAxesValidator extends ConstraintValidator
{
    /** @var EntityWithFamilyVariantAttributesProvider */
    private $axesProvider;

    /** @var EntityWithVariantFamilyRepositoryInterface */
    private $repository;

    /** @var UniqueAxesCombinationSet */
    private $uniqueAxesCombinationSet;

    /**
     * @param EntityWithFamilyVariantAttributesProvider  $axesProvider
     * @param EntityWithVariantFamilyRepositoryInterface $repository
     * @param UniqueAxesCombinationSet                   $uniqueAxesCombinationSet
     */
    public function __construct(
        EntityWithFamilyVariantAttributesProvider $axesProvider,
        EntityWithVariantFamilyRepositoryInterface $repository,
        UniqueAxesCombinationSet $uniqueAxesCombinationSet
    ) {
        $this->axesProvider = $axesProvider;
        $this->repository = $repository;
        $this->uniqueAxesCombinationSet = $uniqueAxesCombinationSet;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof EntityWithFamilyVariantInterface) {
            throw new UnexpectedTypeException($constraint, EntityWithFamilyVariantInterface::class);
        }

        if (!$constraint instanceof SiblingUniqueVariantAxes) {
            throw new UnexpectedTypeException($constraint, SiblingUniqueVariantAxes::class);
        }

        if (null === $entity->getFamilyVariant()) {
            return;
        }

        $valueAlreadyExists = $this->alreadyExists($entity);
        $valueAlreadyProcessed = $this->hasAlreadyValidatedTheSameValue($entity);

        if ($valueAlreadyExists || $valueAlreadyProcessed) {
            $axes = $this->axesProvider->getAxes($entity);
            $axesCodes = implode(',', array_map(function ($axis) {
                return $axis->getCode();
            }, $axes));
            $duplicateCombination = $this->buildAxesCombination($entity, $axes);

            $this->context->buildViolation(
                SiblingUniqueVariantAxes::DUPLICATE_VALUE_IN_SIBLING,
                ['%values%' => $duplicateCombination, '%attributes%' => $axesCodes]
            )->addViolation();
        }
    }

    /**
     * This method builds "combinations" of the given $entityWithValues for its $axes.
     * A combination is the concatenation of all values for an axis.
     *
     * For example, the axis is made of 2 attributes: color and size.
     * Let say we have [blue] for color and [xl] for size.
     * The combination of this entity will be "[blue],[xl]".
     *
     * This allows use to compare multiple combinations, to look for a potential duplicate.
     *
     * @param EntityWithValuesInterface $entityWithValues
     * @param array                     $axes
     *
     * @return string
     */
    private function buildAxesCombination(EntityWithValuesInterface $entityWithValues, array $axes): string
    {
        $combination = [];

        foreach ($axes as $axis) {
            $value = $entityWithValues->getValue($axis->getCode());
            $stringValue = '';

            if (null !== $value) {
                $stringValue = $value->__toString();
            }

            $combination[] = $stringValue;
        }

        return implode(',', $combination);
    }

    /**
     * This method returns TRUE if there is a duplicate value in siblings of $entity in database, FALSE otherwise
     *
     * @param $entity
     *
     * @return bool
     */
    private function alreadyExists(EntityWithFamilyVariantInterface $entity): bool
    {
        $brothers = $this->repository->findSiblings($entity);

        if (empty($brothers)) {
            return false;
        }

        $axes = $this->axesProvider->getAxes($entity);

        if (empty($axes)) {
            return false;
        }

        $brothersCombinations = [];
        foreach ($brothers as $brother) {
            $brothersCombinations[] = $this->buildAxesCombination($brother, $axes);
        }

        $ownCombination = $this->buildAxesCombination($entity, $axes);

        return in_array($ownCombination, $brothersCombinations);
    }

    /**
     * This method returns TRUE if there is a duplicate value in an already parsed entity (so it has to be stateful),
     * FALSE otherwise
     *
     * @param EntityWithFamilyVariantInterface $entity
     *
     * @return bool
     */
    private function hasAlreadyValidatedTheSameValue(EntityWithFamilyVariantInterface $entity): bool
    {
        $axes = $this->axesProvider->getAxes($entity);

        if (empty($axes)) {
            return false;
        }

        $combination = $this->buildAxesCombination($entity, $axes);

        return false === $this->uniqueAxesCombinationSet->addCombination($entity, $combination);
    }
}
