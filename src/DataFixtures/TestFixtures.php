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

class TestFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $testUser = new User();
        $testUser->setLogin('Test user');
        $testUser->setLastLogin(new DateTime());
        $testUser->setActive(true);
        $manager->persist($testUser);
        $lastWeek = new DateTime();
        $lastWeek->modify('-1 week');
        $threeMonth = new DateTime();
        $threeMonth->modify('-3 months');
        $debianPackage = new AppUpdateType();
        $debianPackage->setName('Debian package');
        $github = new AppUpdateType();
        $github->setName('GitHub');
        $debian = new ServerType();
        $debian->setLabel('Debian 12');
        $windowsServer = new ServerType();
        $windowsServer->setLabel('Windows Server');
        $server0 = new Server();
        $server0->setName('GLPI');
        $server0->setIp('192.168.1.1');
        $server0->setLastUpdate(new DateTime());
        $server0->setType($debian);
        $server1 = new Server();
        $server1->setName('Mailing');
        $server1->setIp('192.168.2.1');
        $server1->setLastUpdate(new DateTime());
        $server1->setType($debian);
        $server1->setDocumentation('https://my-server-documentation.fr');
        $server2 = new Server();
        $server2->setName('DNS');
        $server2->setIp('192.168.1.200');
        $server2->setLastUpdate($lastWeek);
        $server2->setType($windowsServer);
        $appGlpi = new App();
        $appGlpi->setName('GLPI');
        $appGlpi->setCurrentVersion('9.0');
        $appGlpi->setLastUpdate($lastWeek);
        $appGlpi->setUpdateType($github);
        $appGlpi->setUpdateResource('glpi-project/glpi');
        $appGlpi->setDocumentation('https://my-app-documentation.fr');
        $appGlpi->setServer($server0);
        $appSympa = new App();
        $appSympa->setName('Sympa');
        $appSympa->setCurrentVersion('2.0');
        $appSympa->setLastUpdate($threeMonth);
        $appSympa->setUpdateType($debianPackage);
        $appSympa->setUpdateResource('sympa');
        $appSympa->setServer($server1);
        $manager->persist($windowsServer);
        $manager->persist($debian);
        $manager->persist($debianPackage);
        $manager->persist($github);
        $manager->persist($server0);
        $manager->persist($server1);
        $manager->persist($server2);
        $manager->persist($appSympa);
        $manager->persist($appGlpi);
        $log1 = new Log();
        $log1->setDate(new DateTime());
        $log1->setMessage('Mise à jour du serveur');
        $log1->setServer($server0);
        $log1->setUsername($testUser->getLogin());
        $log2 = new Log();
        $log2->setDate($lastWeek);
        $log2->setMessage('Mise à jour de GLPI - 9.0');
        $log2->setServer($server0);
        $log2->setUsername($testUser->getLogin());
        $manager->persist($log1);
        $manager->persist($log2);
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
        return ['tests'];
    }
}
