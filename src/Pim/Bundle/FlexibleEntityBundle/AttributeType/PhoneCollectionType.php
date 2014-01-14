<?php

namespace Pim\Bundle\FlexibleEntityBundle\AttributeType;

use Pim\Bundle\FlexibleEntityBundle\Model\FlexibleValueInterface;

/**
 * attribute type
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PhoneCollectionType extends AbstractAttributeType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pim_flexibleentity_phone_collection';
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareValueFormData(FlexibleValueInterface $value)
    {
        return $value;
    }
}
