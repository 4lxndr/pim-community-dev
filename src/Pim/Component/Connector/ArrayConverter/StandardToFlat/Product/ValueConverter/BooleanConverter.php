<?php

namespace Pim\Component\Connector\ArrayConverter\StandardToFlat\Product\ValueConverter;

use Pim\Component\Connector\ArrayConverter\FlatToStandard\Product\AttributeColumnsResolver;

/**
 * Boolean array converter.
 * Convert a standard boolean array format to a flat one.
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class BooleanConverter extends AbstractValueConverter implements ValueConverterInterface
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
     * Convert a standard formatted boolean product value to a flat one.
     *
     * Given a 'auto_lock' $property with this $data:
     * [
     *     [
     *         'locale' => 'de_DE',
     *         'scope'  => 'ecommerce',
     *         'data'   => false
     *     ],
     * ]
     *
     * It will return:
     * [
     *     'auto_lock-de_DE-ecommerce' => '0',
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

            $convertedItem[$flatName] = (true === $value['data']) ? '1' : '0';
        }

        return $convertedItem;
    }
}
