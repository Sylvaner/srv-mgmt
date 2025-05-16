<?php

namespace App\Tests\Entity;

use App\Entity\ServerType;
use App\Entity\Server;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ServerTypeTest extends TestCase
{
    public function testCreateServerType(): void
    {
        $type = new ServerType();
        $this->assertInstanceOf(ServerType::class, $type);
        $this->assertInstanceOf(ArrayCollection::class, $type->getServers());
    }

    public function testLabel(): void
    {
        $type = new ServerType();
        
        // Test label
        $label = 'Test Type';
        $type->setLabel($label);
        $this->assertEquals($label, $type->getLabel());
    }

    public function testServersRelation(): void
    {
        $type = new ServerType();
        $server = new Server();
        
        // Add server
        $type->addServer($server);
        $this->assertContains($server, $type->getServers());
        
        // Remove server
        $type->removeServer($server);
        $this->assertNotContains($server, $type->getServers());
    }

    public function testApiGroups(): void
    {
        $type = new ServerType();
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


}
