<?php

namespace App\Tests\Entity;

use App\Entity\Server;
use App\Entity\ServerType;
use App\Entity\Log;
use App\Entity\App;
use DateTime;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testCreateServer(): void
    {
        $server = new Server();
        $this->assertInstanceOf(Server::class, $server);
        $this->assertInstanceOf(\Doctrine\Common\Collections\ArrayCollection::class, $server->getLogs());
        $this->assertInstanceOf(\Doctrine\Common\Collections\ArrayCollection::class, $server->getApps());
    }

    public function testProperties(): void
    {
        $server = new Server();
        
        // Test name
        $name = 'Test Server';
        $server->setName($name);
        $this->assertEquals($name, $server->getName());

        // Test IP
        $ip = '192.168.1.1';
        $server->setIp($ip);
        $this->assertEquals($ip, $server->getIp());

        // Test dates
        $date = new DateTime();
        $server->setLastUpdate($date);
        $this->assertEquals($date, $server->getLastUpdate());

        $server->setLastCheck($date);
        $this->assertEquals($date, $server->getLastCheck());

        // Test documentation
        $doc = 'Test documentation';
        $server->setDocumentation($doc);
        $this->assertEquals($doc, $server->getDocumentation());
        
        // Test disabled state
        $this->assertFalse($server->isDisabled(), 'Le serveur devrait être actif par défaut');
        $server->setDisabled(true);
        $this->assertTrue($server->isDisabled(), 'Le serveur devrait être désactivé');
        $server->setDisabled(false);
        $this->assertFalse($server->isDisabled(), 'Le serveur devrait être réactivé');
    }

    public function testRelations(): void
    {
        $server = new Server();
        $type = new ServerType();
        
        // Test ServerType relation
        $type->setLabel('Test Type');
        $server->setType($type);
        $this->assertEquals($type, $server->getType());

        // Test Logs relation
        $log = new Log();
        $server->addLog($log);
        $this->assertContains($log, $server->getLogs());

        // Test Apps relation
        $app = new App();
        $server->addApp($app);
        $this->assertContains($app, $server->getApps());
    }

    public function testRemoveRelations(): void
    {
        $server = new Server();
        
        // Add log
        $log = new Log();
        $server->addLog($log);
        
        // Remove log
        $server->removeLog($log);
        $this->assertNotContains($log, $server->getLogs());

        // Add app
        $app = new App();
        $server->addApp($app);
        
        // Remove app
        $server->removeApp($app);
        $this->assertNotContains($app, $server->getApps());
    }

    public function testApiGroups(): void
    {
        $server = new Server();
        $reflection = new \ReflectionClass($server);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(Groups::class);
            if (!empty($attributes)) {
                $groups = $attributes[0]->newInstance()->groups;
                $this->assertContains('server:read', $groups);
                if ($property->getName() !== 'id') {
                    $this->assertContains('logs', $groups);
                }
            }
        }
        $this->assertGreaterThan(0, count($properties));
    }

    public function testToString(): void
    {
        $server = new Server();
        $server->setName('Test Server');
        $this->assertEquals('Test Server', $server->getName());
    }
}
