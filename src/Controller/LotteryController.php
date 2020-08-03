<?php

namespace App\Controller;

use App\Entity\Prize;
use App\Entity\User as AppUser;
use App\Services\LotteryService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class LotteryController extends AbstractController
{
    const PRIZE_TYPE_MONEY = 'money';
    const PRIZE_TYPE_POINTS = 'points';
    const PRIZE_TYPE_STUFF = 'stuff';

    /*
     * var LotteryService
     */
    private $_service;

    /**
     * LotteryController constructor.
     *
     * @param LotteryService $lotteryService
     */
    public function __construct(LotteryService $lotteryService)
    {
        $this->_service = $lotteryService;
    }

    /**
     * @Route("/lottery", name="lottery")
     */
    public function index(): Response
    {
        return $this->render('lottery/index.html.twig', [
            'controller_name' => 'HomeController',
            'current' => 'homepage'
        ]);
    }

    /**
     * @Route("/receivePrize", methods={"POST"})
     *
     * @param UserInterface $user
     * @return JsonResponse
     * @throws Exception
     */
    public function receivePrize(UserInterface $user): JsonResponse
    {
        $output = [];
        if (!$user instanceof AppUser) {
            $output['status']  = 'error';
            return new JsonResponse($output);
        }

        $randomPrize = $this->_service->getRandomPrize();

        if (!$this->_service->checkPrizesLimit((string) array_keys($randomPrize)[0], (int)array_values($randomPrize)[0], $user->getId())) {
            if (self::PRIZE_TYPE_MONEY === array_keys($randomPrize)[0]) {
                $message = 'Вам выпало ' . array_values($randomPrize)[0] . 'грн. Но, к сожалению, мы не можем зачислить эти средства Вам на счет, так как будет превышен допустимый лимит средств на счету.';
            } else {
                $message = 'Вам выпал ' . array_values($randomPrize)[0] . '. Но, к сожалению, мы не можем Вам его выдать так как у Вас максимальное количество вещей.';
            }

            $output['status'] = 'error';
            $output['message'] = $message;

            return new JsonResponse($output);
        }

        $prize = new Prize();

        foreach ($randomPrize as $k => $value){
            $output['prizeType']  = $k;
            $output['prize']  = $value;

            switch ($k){
                case self::PRIZE_TYPE_MONEY:
                    $prize->setMoney($value);
                    break;
                case self::PRIZE_TYPE_POINTS:
                    $prize->setPoints($value);
                    break;
                case self::PRIZE_TYPE_STUFF:
                    $prize->setStuff($value);
                    break;
                default:
                    throw new \Exception('Unexpected value');
            }
        }

        $output['userName'] = $user->getUsername();
        $prize->setUser($user);

        try{
            $this->_service->savePrize($prize);
            $output['status'] = 'success';
        } catch (\Exception $e) {
            $output['status'] = 'error';
            $output['message'] = $e->getMessage();
        }

        return new JsonResponse($output);
    }
}
