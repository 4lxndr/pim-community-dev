<?php

namespace Pim\Bundle\UserBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * User repository
 *
 * @author    Yohan Blain <yohan.blain@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getIdentifierProperties()
    {
        return ['username', 'email'];
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByIdentifier($identifier)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->where($qb->expr()->orX(
               $qb->expr()->eq('u.username', ':identifier'),
               $qb->expr()->eq('u.email', ':identifier')
           ))
           ->setParameter(':identifier', $identifier);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
