<?php

namespace Pim\Bundle\BaseConnectorBundle\Reader\ORM;

use Symfony\Component\Validator\Constraints as Assert;
use Pim\Bundle\BaseConnectorBundle\Validator\Constraints\Channel as ChannelConstraint;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Entity\Channel;

/**
 * Reads products one by one
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductReader extends Reader
{
    /**
     * @var string
     *
     * @Assert\NotBlank(groups={"Execution"})
     * @ChannelConstraint
     */
    protected $channel;

    /** @var ProductManager */
    protected $productManager;

    /** @var ChannelManager */
    protected $channelManager;

    /** @var CompletenessManager */
    protected $completenessManager;

    /* @var MetricConverter */
    protected $metricConverter;

    /**
     * @param ProductManager      $productManager
     * @param ChannelManager      $channelManager
     * @param CompletenessManager $completenessManager
     * @param MetricConverter     $metricConverter
     */
    public function __construct(
        ProductManager $productManager,
        ChannelManager $channelManager,
        CompletenessManager $completenessManager,
        MetricConverter $metricConverter
    ) {
        $this->productManager      = $productManager;
        $this->channelManager      = $channelManager;
        $this->completenessManager = $completenessManager;
        $this->metricConverter     = $metricConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (!$this->query) {
            $code = $this->channel;
            $this->channel = current($this->channelManager->getChannels(array('code' => $this->channel)));
            if (!$this->channel) {
                throw new \InvalidArgumentException(
                    sprintf('Could not find the channel "%s"', $code)
                );
            }

            $this->completenessManager->generateMissingForChannel($this->channel);

            $this->query = $this->getProductRepository()
                ->buildByChannelAndCompleteness($this->channel)
                ->getQuery();
        }

        $product = parent::read();

        if ($product) {
            $this->metricConverter->convert($product, $this->channel);
        }

        return $product;
    }

    /**
     * Set channel
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * Get channel
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return array(
            'channel' => array(
                'type'    => 'choice',
                'options' => array(
                    'choices'  => $this->channelManager->getChannelChoices(),
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_base_connector.export.channel.label',
                    'help'     => 'pim_base_connector.export.channel.help'
                )
            )
        );
    }

    /**
     * Get the product repository
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getProductRepository()
    {
        return $this->productManager->getProductRepository();
    }
}
