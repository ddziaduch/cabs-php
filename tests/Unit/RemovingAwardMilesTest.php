<?php

declare(strict_types=1);

namespace LegacyFighter\Cabs\Tests\Unit;

use LegacyFighter\Cabs\Distance\Distance;
use LegacyFighter\Cabs\Entity\Address;
use LegacyFighter\Cabs\Entity\AwardsAccount;
use LegacyFighter\Cabs\Entity\CarType;
use LegacyFighter\Cabs\Entity\Client;
use LegacyFighter\Cabs\Entity\Miles\AwardedMiles;
use LegacyFighter\Cabs\Entity\Transit;
use PHPUnit\Framework\TestCase;

final class RemovingAwardMilesTest extends TestCase
{
    /**
     * @test
     */
    public function byDefaultRemoveOldestFirstEvenWhenTheyAreNonExpiring(): void
    {
        //given
        $when = $this->dayBeforeYesterday();
        //and
        $account = $this->anActiveAwardsAccount(Client::TYPE_NORMAL, $when);
        //and
        $transit = $this->aTransit($account->getClient(), $when);
        //and
        $middle = $this->grantedMilesThatWillExpireInDays(10, 365, $this->yesterday(), $account, $transit);
        $youngest = $this->grantedMilesThatWillExpireInDays(10, 365, $this->today(), $account, $transit);
        $oldestNonExpiringMiles = $this->grantedNonExpiringMiles(5, $this->dayBeforeYesterday(), $account);

        //when
        $account->remove(16, $this->dayBeforeYesterday(), 0, 0, Client::TYPE_NORMAL, false);

        //then
        $awardedMiles = $account->getMiles();
        self::assertThatMilesWereReducedTo($oldestNonExpiringMiles, 0, $awardedMiles);
        self::assertThatMilesWereReducedTo($middle, 0, $awardedMiles);
        self::assertThatMilesWereReducedTo($youngest, 9, $awardedMiles);
    }

    /**
     * @test
     */
    public function shouldRemoveOldestMilesFirstWhenManyTransits(): void
    {
        //given
        $when = $this->dayBeforeYesterday();
        //and
        $awardsAccount = $this->anActiveAwardsAccount(Client::TYPE_NORMAL, $when);
        //and
        $transit = $this->aTransit($awardsAccount->getClient(), $when);
        //add
        $oldest = $this->grantedMilesThatWillExpireInDays(10, 60, $this->dayBeforeYesterday(), $awardsAccount, $transit);
        $middle = $this->grantedMilesThatWillExpireInDays(10, 365, $this->yesterday(), $awardsAccount, $transit);
        $youngest = $this->grantedMilesThatWillExpireInDays(10, 30, $this->today(), $awardsAccount, $transit);

        //when
        $awardsAccount->remove(15, $when, 15, 0, Client::TYPE_NORMAL, false);

        //then
        $awardedMiles = $awardsAccount->getMiles();
        self::assertThatMilesWereReducedTo($oldest, 0, $awardedMiles);
        self::assertThatMilesWereReducedTo($middle, 5, $awardedMiles);
        self::assertThatMilesWereReducedTo($youngest, 10, $awardedMiles);
    }

    /**
     * @test
     */
    public function shouldRemoveNoNExpiringMilesLastWhenManyTransits(): void
    {
        //given
        $when = $this->dayBeforeYesterday();
        //and
        $awardsAccount = $this->anActiveAwardsAccount(Client::TYPE_NORMAL, $when);
        //and
        $transit = $this->aTransit($awardsAccount->getClient(), $when);
        //add
        $regularMiles = $this->grantedMilesThatWillExpireInDays(10, 365, $this->today(), $awardsAccount, $transit);
        $oldestNonExpiringMiles = $this->grantedNonExpiringMiles(5, $this->dayBeforeYesterday(), $awardsAccount);

        //when
        $awardsAccount->remove(13, $when, 15, 0, $awardsAccount->getClient()->getType(), false);

        //then
        $awardedMiles = $awardsAccount->getMiles();
        self::assertThatMilesWereReducedTo($regularMiles, 0, $awardedMiles);
        self::assertThatMilesWereReducedTo($oldestNonExpiringMiles, 2, $awardedMiles);
    }

    /**
     * @test
     */
    public function shouldRemoveSoonToExpireMilesFirstWhenClientIsVIP(): void
    {
        //given
        $when = $this->dayBeforeYesterday();
        //and
        $awardsAccount = $this->anActiveAwardsAccount(Client::TYPE_VIP, $when);
        //and
        $transit = $this->aTransit($awardsAccount->getClient(), $when);
        //add
        $secondToExpire = $this->grantedMilesThatWillExpireInDays(10, 60, $this->yesterday(), $awardsAccount, $transit);
        $thirdToExpire = $this->grantedMilesThatWillExpireInDays(5, 365, $this->dayBeforeYesterday(), $awardsAccount, $transit);
        $firstToExpire = $this->grantedMilesThatWillExpireInDays(15, 30, $this->today(), $awardsAccount, $transit);
        $nonExpiring = $this->grantedNonExpiringMiles(1, $this->dayBeforeYesterday(), $awardsAccount);

        //when
        $awardsAccount->remove(21, $when, 0, 0, $awardsAccount->getClient()->getType(), false);

        //then
        $awardedMiles = $awardsAccount->getMiles();
        self::assertThatMilesWereReducedTo($nonExpiring, 1, $awardedMiles);
        self::assertThatMilesWereReducedTo($firstToExpire, 0, $awardedMiles);
        self::assertThatMilesWereReducedTo($secondToExpire, 4, $awardedMiles);
        self::assertThatMilesWereReducedTo($thirdToExpire, 5, $awardedMiles);
    }

    /**
     * @test
     */
    public function shouldRemoveSoonToExpireMilesFirstWhenRemovingOnSundayAndClientHasDoneManyTransits(): void
    {
        //given
        $when = $this->dayBeforeYesterday();
        //and
        $awardsAccount = $this->anActiveAwardsAccount(Client::TYPE_NORMAL, $when);
        //and
        $transit = $this->aTransit($awardsAccount->getClient(), $when);
        //add
        $secondToExpire = $this->grantedMilesThatWillExpireInDays(10, 60, $this->yesterday(), $awardsAccount, $transit);
        $thirdToExpire = $this->grantedMilesThatWillExpireInDays(5, 365, $this->dayBeforeYesterday(), $awardsAccount, $transit);
        $firstToExpire = $this->grantedMilesThatWillExpireInDays(15, 10, $this->today(), $awardsAccount, $transit);
        $nonExpiring = $this->grantedNonExpiringMiles(100, $this->yesterday(), $awardsAccount);

        //when
        $awardsAccount->remove(21, $when, 15, 0, $awardsAccount->getClient()->getType(), true);

        //then
        $awardedMiles = $awardsAccount->getMiles();
        self::assertThatMilesWereReducedTo($nonExpiring, 100, $awardedMiles);
        self::assertThatMilesWereReducedTo($firstToExpire, 0, $awardedMiles);
        self::assertThatMilesWereReducedTo($secondToExpire, 4, $awardedMiles);
        self::assertThatMilesWereReducedTo($thirdToExpire, 5, $awardedMiles);
    }

    /**
     * @test
     */
    public function shouldRemoveExpiringMilesFirstWhenClientHasManyClaims(): void
    {
        //given
        $when = $this->dayBeforeYesterday();
        //and
        $awardsAccount = $this->anActiveAwardsAccount(Client::TYPE_NORMAL, $when);
        //and
        $transit = $this->aTransit($awardsAccount->getClient(), $when);
        //add
        $secondToExpire = $this->grantedMilesThatWillExpireInDays(4, 60, $this->yesterday(), $awardsAccount, $transit);
        $thirdToExpire = $this->grantedMilesThatWillExpireInDays(10, 365, $this->dayBeforeYesterday(), $awardsAccount, $transit);
        $firstToExpire = $this->grantedMilesThatWillExpireInDays(5, 10, $this->yesterday(), $awardsAccount, $transit);
        $nonExpiring = $this->grantedNonExpiringMiles(10, $this->yesterday(), $awardsAccount);

        //when
        $awardsAccount->remove(21, $when, 0, 3, $awardsAccount->getClient()->getType(), false);

        //then
        $awardedMiles = $awardsAccount->getMiles();
        self::assertThatMilesWereReducedTo($nonExpiring, 0, $awardedMiles);
        self::assertThatMilesWereReducedTo($firstToExpire, 5, $awardedMiles);
        self::assertThatMilesWereReducedTo($secondToExpire, 3, $awardedMiles);
        self::assertThatMilesWereReducedTo($thirdToExpire, 0, $awardedMiles);
    }

    /**
     * @param AwardedMiles[] $allMiles
     */
    private static function assertThatMilesWereReducedTo(AwardedMiles $firstToExpire, int $milesAfterReduction, array $allMiles): void
    {
        $actual = array_values(array_map(
            static fn(AwardedMiles $am) => $am->getMilesAmount(new \DateTimeImmutable('0000-01-01')),
            array_filter($allMiles, static fn(AwardedMiles $am) => $firstToExpire == $am)
        ));
        self::assertEquals($milesAfterReduction, $actual[0]);
    }

    private function grantedMilesThatWillExpireInDays(
        int $miles,
        int $expirationDays,
        \DateTimeImmutable $when,
        AwardsAccount $account,
        Transit $transit
    ): AwardedMiles {
        return $account->addExpiringMiles(
            $miles,
            $when->modify(sprintf('+%d days', $expirationDays)),
            $transit,
            $when,
        );
    }

    private function grantedNonExpiringMiles(
        int $miles,
        \DateTimeImmutable $when,
        AwardsAccount $account
    ): AwardedMiles {
        return $account->addNonExpiringMiles($miles, $when);
    }

    private function anActiveAwardsAccount(string $type, \DateTimeImmutable $when): AwardsAccount
    {
        $client = new Client();
        $client->setType($type);

        return new AwardsAccount($client, true, $when);
    }

    private function dayBeforeYesterday(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('1989-12-12 12:12');
    }

    private function yesterday(): \DateTimeImmutable
    {
        return $this->dayBeforeYesterday()->modify('+1 day');
    }

    private function today(): \DateTimeImmutable
    {
        return $this->yesterday()->modify('+1 day');
    }

    public function aTransit(Client $client, \DateTimeImmutable $when): Transit
    {
        $address = new Address('Poland', 'Gdansk', 'Nowe ogrody', 1);
        $transit = new Transit(
            $address,
            $address,
            $client,
            CarType::CAR_CLASS_VAN,
            $when,
            Distance::zero()
        );

        return $transit;
    }
}
