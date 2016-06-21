<?php

namespace Pim\Component\Connector\ArrayConverter\StandardToFlat\Product\ValueConverter;

use Pim\Component\Connector\ArrayConverter\FlatToStandard\Product\AttributeColumnsResolver;

/**
 * Simpleselect array converter.
 * Convert a standard simpleselect array format to a flat one.
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class SimpleSelectConverter extends AbstractValueConverter
{
    /**
     * @param AttributeColumnsResolver $columnsResolver
     * @param array                    $supportedFieldType
     */
    public function __construct(AttributeColumnsResolver $columnsResolver, array $supportedFieldType)
    {
        parent::__construct($columnsResolver);

        $this->supportedFieldType = $supportedFieldType;
    }

    /**
     * {@inheritdoc}
     *
     * Convert a standard simpleselect field to a flat one.
     *
     * Given a 'gift_type' $field with this $data:
     * [
     *     [
     *         'locale' => 'de_DE',
     *         'scope'  => 'ecommerce',
     *         'data'   => 'trip'
     *     ],
     * ]
     *
     * It will return:
     * [
     *     'gift_type-de_DE-ecommerce' => 'trip',
     * ]
     */
    public function convert($attributeCode, $data)
    {
        $convertedItem = [];

        foreach ($data as $value) {
            $flatName = $this->columnsResolver->resolveFlatAttributeName(
                $attributeCode,
                $value['locale'],
                $value['scope']
            );

            $convertedItem[$flatName] = (string) $value['data'];
        }

        return $convertedItem;
    }
}
