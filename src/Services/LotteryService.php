<?php

namespace App\Services;

use App\Entity\Prize;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;

class LotteryService
{
    const MONEY_PRIZE  = 1;
    const POINTS_PRIZE = 2;
    const STUFF_PRIZE  = 3;

    const STUFF = [
        'наушники',
        'шампунь',
        'парфюм',
        'телескоп',
        'органайзер',
        'термостакан',
        'фотоальбом',
        'ремень',
        'смартфон',
        'рюкзак',
        'мангал',
        'геймпад',
        'глобус',
        'аудиоплеер',
        'фен',
        'чайник',
        'будильник'
    ];

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
     * @throws \Exception
     */
    public function getRandomPrize()
    {
        $prize = [];

        $prizeNumber = random_int(1, 3);

        switch ($prizeNumber){
            case self::MONEY_PRIZE:
                $prize['money'] = (random_int(10, 500));
                break;
            case self::POINTS_PRIZE:
                $prize['points'] = (random_int(100, 5000));
                break;
            case self::STUFF_PRIZE:
                $prize['stuff'] = (self::STUFF[array_rand( self::STUFF, 1 )]);
                break;
            default:
                throw new \Exception('Unexpected value');
        }
        return $prize;
    }

    /**
     * @param Prize $prize
     * @return bool
     */
    public function savePrize(Prize $prize)
    {
        $this->_em->persist($prize);
        $this->_em->flush();

        return true;
    }

    /**
     * @param $typePrize
     * @param $prize
     * @param $userId
     * @return bool
     * @throws NonUniqueResultException
     */
    public function checkPrizesLimit($typePrize, $prize, $userId)
    {
        $prizeTypeMoney  = 'money';
        $prizeTypePoints = 'points';
        $prizeTypeStuff  = 'stuff';

        $moneyLimit = 2000;
        $stuffLimit = 10;

        if ($prizeTypePoints === $typePrize) return true;

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Prize::class, 'p');
        $rsm->addScalarResult( 'money', 'money');
        $rsm->addScalarResult( 'stuff', 'stuff');


        $query = $this->_em->createNativeQuery('
            SELECT 
                SUM(p.money) AS money,
                COUNT(p.stuff) AS stuff
            FROM prize p 
            where p.user_id = :p_user_id
            ', $rsm);
        $query->setParameter('p_user_id', $userId);

        $result = $query->getOneOrNullResult();

        if ($prizeTypeMoney === $typePrize) {
            return $moneyLimit >= (int) $result[$prizeTypeMoney] + $prize;
        } else {
            return $stuffLimit > (int) $result[$prizeTypeStuff];
        }
    }
}