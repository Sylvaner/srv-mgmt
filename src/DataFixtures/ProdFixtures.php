<?php

namespace App\DataFixtures;

use App\Entity\App;
use App\Entity\AppUpdateType;
use App\Entity\Setting;
use App\Entity\Log;
use App\Entity\Server;
use App\Entity\ServerType;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class ProdFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $githubRelease = new AppUpdateType();
        $githubRelease->setName('GitHub Release');
        $manager->persist($githubRelease);
        $githubTag = new AppUpdateType();
        $githubTag->setName('GitHub Tag');
        $manager->persist($githubTag);
        $debian = new AppUpdateType();
        $debian->setName('Debian');
        $manager->persist($debian);
        $docker = new AppUpdateType();
        $docker->setName('Docker');
        $manager->persist($docker);
        $crawler = new AppUpdateType();
        $crawler->setName('Crawler');
        $manager->persist($crawler);
        $debian12 = new ServerType();
        $debian12->setLabel('Debian 12');
        $manager->persist($debian12);
        $windowsServer = new ServerType();
        $windowsServer->setLabel('Windows Server');
        $manager->persist($windowsServer);
        $configWarningLimit = new Setting();
        $configWarningLimit->setName('warning_threshold');
        $configWarningLimit->setValue('10');
        $manager->persist($configWarningLimit);
        $configErrorLimit = new Setting();
        $configErrorLimit->setName('alert_threshold');
        $configErrorLimit->setValue('30');
        $manager->persist($configErrorLimit);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['prod'];
    }
}
