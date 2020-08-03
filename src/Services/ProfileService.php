<?php

namespace App\Services;

use App\Entity\Prize;
use App\Entity\User;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProfileService
{
    const MONEY_TO_POINT_CONVERSION_FACTOR = 10;

    /**
     * @var EntityManagerInterface
     */
    protected $_em;

    protected $httpClient;

    /**
     * LotteryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param HttpClientInterface $httpClient
     */
    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient)
    {
        $this->_em = $entityManager;
        $this->httpClient = $httpClient;
    }

    /**
     * @param $userId
     * @return int|mixed|string|null
     * @throws NonUniqueResultException
     */
    public function getDataOnPrizes($userId)
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Prize::class, 'p');
        $rsm->addScalarResult( 'money', 'money');
        $rsm->addScalarResult( 'points', 'points');
        $rsm->addScalarResult( 'stuff', 'stuff');

        $query = $this->_em->createNativeQuery('
            SELECT 
                SUM(p.money) AS money,
                SUM(p.points) AS points,
                GROUP_CONCAT(CONCAT(p.id, \'/\', p.stuff) SEPARATOR ", ") AS stuff
            FROM prize p 
            where p.user_id = :p_user_id
            ', $rsm);
        $query->setParameter('p_user_id', $userId);

        return $query->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @return int|mixed|string|null
     * @throws NonUniqueResultException
     */
    public function getSumMoney($userId)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('SUM(_p.money) AS money')
            ->from(Prize::class, '_p')
            ->join('_p.user', '_u')
            ->where($qb->expr()->eq('_u.id', $userId));

        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     * @param $userId
     * @return int
     * @throws DBALException
     */
    public function removeOffAllMoney($userId)
    {
        return $this->_em->getConnection()->executeUpdate(
            '
            DELETE 
            FROM prize 
            WHERE user_id = :userId
            AND money IS NOT NULL
            ',
            ['userId' => (int) $userId]
        );
    }

    /**
     * @param $quantityPoints
     * @param User $user
     * @return bool
     */
    public function addPrizePoints($quantityPoints, User $user)
    {
        $prize = new Prize();
        $prize->setPoints($quantityPoints);
        $prize->setUser($user);

        $this->_em->persist($prize);
        $this->_em->flush();

        return true;
    }

    /**
     * @param int $amountMoney
     * @return int
     */
    public function convertMoneyIntoPoints(int $amountMoney)
    {
        return self::MONEY_TO_POINT_CONVERSION_FACTOR * $amountMoney;
    }

    /**
     * @param $userId
     * @param $stuffId
     * @return int
     * @throws DBALException
     */
    public function removePrizeStuff($userId, $stuffId)
    {
        return $this->_em->getConnection()->executeUpdate(
            '
            DELETE 
            FROM prize 
            WHERE user_id = :userId
            AND id = :stuffId
            ',
            [
                'userId'  => (int) $userId,
                'stuffId' => (int) $stuffId
            ]
        );
    }

    /**
     * @param $userId
     * @return array
     */
    public function paid($userId)
    {
        $statusMessage = 'OK';
        $statusCode = 200;
        $status = [];

        $body = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request version="1.0">
    <merchant>
        <id>75482</id>
        <signature>99730232b2f984c571507a0e74595e777afd0428</signature>
    </merchant>
    <data>
        <oper>cmt</oper>
        <wait>0</wait>
        <test>0</test>
        <payment id="1234567">
            <prop name="b_card_or_acc" value="4627081718568608" />
            <prop name="amt" value="1" />
            <prop name="ccy" value="UAH" />
            <prop name="details" value="test%20merch%20not%20active" />
        </payment>
    </data>
</request>
XML;

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://api.privatbank.ua/p24api/pay_pb',
                [
                    'body' => $body
                ]
            );

            if (200 === $response->getStatusCode()) {
                if ($xmlContent = simplexml_load_string($response->getContent())) {
                    if ($xmlContent->data->error) {
                        $statusCode = 400;
                        $statusMessage = (string) $xmlContent->data->error->attributes()->message;
                    }
                }
            }
        } catch (\Throwable $e) {
            $statusCode = 400;
            $statusMessage = $e->getMessage();
        }

        $status['code'] = $statusCode;
        $status['message'] = $statusMessage;

        return $status;
    }
}