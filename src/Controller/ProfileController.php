<?php

namespace App\Controller;

use App\Entity\User as AppUser;
use App\Repository\PrizeRepository;
use App\Services\ProfileService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProfileController extends AbstractController
{
    private $_service;

    private $httpClient;

    /**
     * ProfileService constructor.
     *
     * @param ProfileService $profileService
     * @param HttpClientInterface $httpClient
     */
    public function __construct(ProfileService $profileService, HttpClientInterface $httpClient)
    {
        $this->_service = $profileService;
        $this->httpClient = $httpClient;
    }

    /**
     * @Route("/profile", name="profile")
     * @param UserInterface $user
     * @param PrizeRepository $prizeRepository
     * @return Response
     * @throws NonUniqueResultException
     */
    public function index(UserInterface $user, PrizeRepository $prizeRepository)
    {
        $prizes = $this->_service->getDataOnPrizes($user->getId());
        $prizes['stuff'] = explode(", ", $prizes['stuff']);

        if ($prizes['stuff'][0]) {
            foreach ($prizes['stuff'] as $k => $value) {
                $data = explode("/", $value);
                $prizes['stuff'][$k] = [];
                $prizes['stuff'][$k]['id'] = $data[0];
                $prizes['stuff'][$k]['stuff'] = $data[1];
            }
        }

        return $this->render('profile/index.html.twig', [
            'prizes' => $prizes,
        ]);
    }

    /**
     * @Route("/convertMoneyIntoPoints", methods={"POST"})
     * @param UserInterface $user
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    public function convertMoneyIntoPoints(UserInterface $user): JsonResponse
    {
        $output = [];

        if (!$user instanceof AppUser) {
            $output['status'] = 'error';
            $output['message'] = 'Ошибка авторизации';
            return new JsonResponse($output);
        }

        $amountMoney = $this->_service->getSumMoney($user->getId());

        if (!(bool)$amountMoney['money']) {
            $output['status'] = 'error';
            $output['message'] = 'У Вас отсутствуют деньги на счету!';
            return new JsonResponse($output);
        }

        $newPoints = $this->_service->convertMoneyIntoPoints((int)$amountMoney['money']);

        try {
            $this->_service->removeOffAllMoney($user->getId());
            $this->_service->addPrizePoints($newPoints, $user);

            $output['status'] = 'success';

            $message = 'Ваши ' . (int)$amountMoney['money'] . ' грн переведены в ' . $newPoints . ' баллов.';
            $output['message'] = $message;
        } catch (\Throwable $e) {
            $output['status'] = 'error';
            $output['message'] = $e->getMessage();
        }

        return new JsonResponse($output);
    }


    /**
     * @Route("/paid", methods={"POST"})
     * @param UserInterface $user
     * @return Response
     */
    public function paid(UserInterface $user): Response
    {
        $response = $this->_service->paid($user->getId());

        return new JsonResponse([
            'code' => $response['code'],
            'message' => $response['message']
        ]);
//        ], $statusCode);
    }

    /**
     * @Route("/removePrizeStuff", methods={"POST"})
     *
     * @param UserInterface $user
     * @param Request $request
     * @return JsonResponse
     */
    public function removePrizeStuff(UserInterface $user, Request $request): JsonResponse
    {
        $output = [];

        if (!$user instanceof AppUser) {
            $output['status'] = 'error';
            $output['message'] = 'Ошибка авторизации';
            return new JsonResponse($output);
        }

        $stuffId = (int) $request->get('stuff_id');

        try {
            $this->_service->removePrizeStuff($user->getId(), $stuffId);

            $output['status'] = 'success';
            $message = 'Приз успешно удален';
            $output['message'] = $message;
        } catch (\Throwable $e) {
            $output['status'] = 'error';
            $output['message'] = $e->getMessage();
        }

        return new JsonResponse($output);
    }
}
