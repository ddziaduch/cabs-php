<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Unit;

use LegacyFighter\Cabs\Distance\Distance;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\AwardsAccount;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Transit;
use PHPUnit\Framework\TestCase;

final class ExpiringMilesTest extends TestCase
{
    /**
     * @test
     */
    public function shouldTakeIntoAccountExpiredMilesWhenCalculatingBalance(): void
    {
        //given
        $now = new \DateTimeImmutable();
        // and
        $client = new Client();
        // and
        $account = new AwardsAccount($client, true, $now);
        //and
        $defaultMilesBonus = 10;
        //and
        $address = new Address('Polska', 'Gdańsk', 'Nowe ogrody', 1);
        // and
        $transit = new Transit($address, $address, $client, CarType::CAR_CLASS_VAN, $now, Distance::zero());

        //when
        $account->addExpiringMiles($defaultMilesBonus, new \DateTimeImmutable('1990-12-12'), $transit, $now);
        //then
        self::assertEquals(10, $account->calculateBalance(new \DateTimeImmutable('1989-12-12')));
        //when
        $account->addExpiringMiles($defaultMilesBonus, new \DateTimeImmutable('1990-12-13'), $transit, $now);
        //then
        self::assertEquals(20, $account->calculateBalance(new \DateTimeImmutable('1989-12-12')));
        //when
        $account->addExpiringMiles($defaultMilesBonus, new \DateTimeImmutable('1990-12-14'), $transit, $now);
        //then
        self::assertEquals(30, $account->calculateBalance(new \DateTimeImmutable('1989-12-14')));
        self::assertEquals(30, $account->calculateBalance((new \DateTimeImmutable('1989-12-12'))->modify('+300 days')));
        self::assertEquals(20, $account->calculateBalance((new \DateTimeImmutable('1989-12-12'))->modify('+365 days')));
        self::assertEquals(20, $account->calculateBalance((new \DateTimeImmutable('1989-12-12'))->modify('+365 days')));
        self::assertEquals(10, $account->calculateBalance((new \DateTimeImmutable('1989-12-13'))->modify('+365 days')));
        self::assertEquals(0, $account->calculateBalance((new \DateTimeImmutable('1989-12-14'))->modify('+365 days')));
    }
}
