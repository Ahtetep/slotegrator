<?php


namespace App\Command;



use App\Services\ProfileService;
use App\Services\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PaymentOfMoneyCommand extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;

    protected static $defaultName = 'app:payout';

    private $userService;
    private $profileService;

    public function __construct(UserService $userService, ProfileService $profileService)
    {
        $this->userService = $userService;
        $this->profileService = $profileService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Payment of money.')
            ->setHelp('This command pays money to the user...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userService->getIdUsersWithMoney();

        foreach ($users as $user){
            $response = $this->profileService->paid($user['id']);
            $output->writeln([
                'Отправка денег юзеру - ' . $user['id'],
                'Код ответа - ' . $response['code'],
                'Сообщение - ' . $response['message'],
                '============',
                '',
            ]);
        }

        return self::SUCCESS;
    }
}