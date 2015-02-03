<?php

namespace spec\Pim\Bundle\CatalogBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Model\ChannelInterface;
use Pim\Bundle\CatalogBundle\Repository\ChannelRepositoryInterface;

class ChannelManagerSpec extends ObjectBehavior
{
    function let(
        ObjectManager $objectManager,
        ChannelRepositoryInterface $repository,
        CompletenessManager $completenessManager
    ) {
        $this->beConstructedWith($objectManager, $repository, $completenessManager);
    }

    function it_is_a_saver()
    {
        $this->shouldHaveType('Akeneo\Component\StorageUtils\Saver\SaverInterface');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Bundle\CatalogBundle\Manager\ChannelManager');
    }

    function it_throws_exception_when_save_anything_else_than_a_channel()
    {
        $anythingElse = new \stdClass();
        $this
            ->shouldThrow(
                new \InvalidArgumentException(
                    sprintf(
                        'Expects a "Pim\Bundle\CatalogBundle\Model\ChannelInterface", "%s" provided.',
                        get_class($anythingElse)
                    )
                )
            )
            ->during('save', [$anythingElse]);
    }

    function it_provides_channels(ObjectManager $objectManager, ChannelRepositoryInterface $repository)
    {
        $repository->findBy(array())->willReturn(array('mobile', 'ecommerce'));
        $this->getChannels()->shouldBeArray();
        $this->getChannels()->shouldHaveCount(2);
    }

    function it_provides_channel_choices(ObjectManager $objectManager, ChannelRepositoryInterface $repository, ChannelInterface $mobile, ChannelInterface $ecommerce)
    {
        $repository->findBy(array())->willReturn(array($mobile, $ecommerce));
        $mobile->getCode()->willReturn('mobile');
        $mobile->getLabel()->willReturn('Mobile');
        $ecommerce->getCode()->willReturn('ecommerce');
        $ecommerce->getLabel()->willReturn('Ecommerce');
        $this->getChannelChoices()->shouldReturn(['mobile' => 'Mobile', 'ecommerce' => 'Ecommerce']);
    }

    function it_schedule_completeness_when_save_a_channel(ChannelInterface $channel, $completenessManager, $objectManager)
    {
        $objectManager->persist($channel)->shouldBeCalled();
        $completenessManager->scheduleForChannel($channel)->shouldBeCalled();
        $objectManager->flush()->shouldBeCalled();
        $this->save($channel);
    }
}
