<?php


namespace App\Tests\Service;


use App\Services\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProfileTest extends TestCase
{
    const MONEY_TO_POINT_CONVERSION_FACTOR = 10;

    /**
     * @var ProfileService
     */
    private $profileService;

    public function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $httpClient = $this->createMock(HttpClientInterface::class);

        $this->profileService = new ProfileService($em, $httpClient);
    }

    public function testAdd()
    {
        $cost = 10;
        $result = $this->profileService->convertMoneyIntoPoints($cost);

        $this->assertEquals(self::MONEY_TO_POINT_CONVERSION_FACTOR * $cost, $result);
    }
}