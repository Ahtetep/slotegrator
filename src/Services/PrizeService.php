<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;

class PrizeService
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
}