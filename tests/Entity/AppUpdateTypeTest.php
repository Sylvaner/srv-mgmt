<?php

namespace App\Tests\Entity;

use App\Entity\AppUpdateType;
use App\Entity\App;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class AppUpdateTypeTest extends TestCase
{
    public function testCreateAppUpdateType(): void
    {
        $type = new AppUpdateType();
        $this->assertInstanceOf(AppUpdateType::class, $type);
        $this->assertInstanceOf(ArrayCollection::class, $type->getApps());
    }

    public function testProperties(): void
    {
        $type = new AppUpdateType();
        
        // Test name
        $name = 'Test Update Type';
        $type->setName($name);
        $this->assertEquals($name, $type->getName());
    }

    public function testAppsRelation(): void
    {
        $type = new AppUpdateType();
        $app = new App();
        
        // Add app
        $type->addApp($app);
        $this->assertContains($app, $type->getApps());

        // Remove app
        $type->removeApp($app);
        $this->assertNotContains($app, $type->getApps());
    }

    public function testApiGroups(): void
    {
        $type = new AppUpdateType();
        $reflection = new \ReflectionClass($type);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(Groups::class);
            if (!empty($attributes)) {
                $groups = $attributes[0]->newInstance()->groups;
                $this->assertContains('server:read', $groups);
            }
        }
        $this->assertGreaterThan(0, count($properties));
    }

    public function testLabel(): void
    {
        $type = new AppUpdateType();
        $name = 'Test Update Type';
        $type->setName($name);
        $this->assertEquals($name, $type->getName());
    }
}
