<?php

namespace App\Tests\Entity;

use App\Entity\App;
use App\Entity\AppUpdateType;
use App\Entity\Server;
use DateTime;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function testCreateApp(): void
    {
        $app = new App();
        $this->assertInstanceOf(App::class, $app);
    }

    public function testProperties(): void
    {
        $app = new App();
        
        // Test name
        $name = 'Test App';
        $app->setName($name);
        $this->assertEquals($name, $app->getName());

        // Test version
        $version = '1.0.0';
        $app->setCurrentVersion($version);
        $this->assertEquals($version, $app->getCurrentVersion());

        // Test last update
        $date = new DateTime();
        $app->setLastUpdate($date);
        $this->assertEquals($date, $app->getLastUpdate());

        // Test update resources
        $resource = 'https://example.com/update';
        $app->setUpdateResource($resource);
        $this->assertEquals($resource, $app->getUpdateResource());

        $extraResource = 'https://example.com/extra';
        $app->setExtraUpdateResource($extraResource);
        $this->assertEquals($extraResource, $app->getExtraUpdateResource());
    }

    public function testRelations(): void
    {
        $app = new App();
        
        // Test Server relation
        $server = new Server();
        $server->setName('Test Server');
        $app->setServer($server);
        $this->assertEquals($server, $app->getServer());

        // Test UpdateType relation
        $updateType = new AppUpdateType();
        $updateType->setName('Test Type');
        $app->setUpdateType($updateType);
        $this->assertEquals($updateType, $app->getUpdateType());
    }

    public function testApiGroups(): void
    {
        $app = new App();
        $reflection = new \ReflectionClass($app);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(Groups::class);
            if (!empty($attributes)) {
                $groups = $attributes[0]->newInstance()->groups;
                $this->assertContains('server:read', $groups);
                $this->assertContainsOnly(['server:read'], $groups);
            }
        }
        $this->assertGreaterThan(0, count($properties));
    }

    public function testToString(): void
    {
        $app = new App();
        $app->setName('Test App');
        $this->assertEquals('Test App', $app->getName());
    }
}
