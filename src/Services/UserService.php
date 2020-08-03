<?php

namespace App\Services;

use App\Entity\Prize;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class UserService
{
    /**
     * @var EntityManagerInterface
     */
    protected $_em;

    /**
     * LotteryService constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->_em = $entityManager;
    }

    /**
     * @return array
     */
    public function getIdUsersWithMoney()
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(User::class, 'u');
        $rsm->addScalarResult( 'id', 'id');

        $query = $this->_em->createNativeQuery('
            SELECT 
                DISTINCT u.id AS id
            FROM user u 
            INNER JOIN prize p ON u.id = p.user_id
            where p.money IS NOT NULL
            ', $rsm);

        return $query->getArrayResult();
    }
}