<?php

namespace App\Tests\Entity;

use App\Entity\Log;
use App\Entity\Server;
use DateTime;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    public function testCreateLog(): void
    {
        $log = new Log();
        $this->assertInstanceOf(Log::class, $log);
    }

    public function testProperties(): void
    {
        $log = new Log();
        
        // Test date
        $date = new DateTime();
        $log->setDate($date);
        $this->assertEquals($date, $log->getDate());

        // Test message
        $message = 'Test log message';
        $log->setMessage($message);
        $this->assertEquals($message, $log->getMessage());
    }

    public function testServerRelation(): void
    {
        $log = new Log();
        $server = new Server();
        $server->setName('Test Server');
        
        $log->setServer($server);
        $this->assertEquals($server, $log->getServer());
    }

    public function testApiGroups(): void
    {
        $log = new Log();
        $reflection = new \ReflectionClass($log);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(Groups::class);
            if (!empty($attributes)) {
                $groups = $attributes[0]->newInstance()->groups;
                $this->assertContains('logs', $groups);
                $this->assertContainsOnly(['logs'], $groups);
            }
        }
        $this->assertGreaterThan(0, count($properties));
    }

    public function testMessage(): void
    {
        $log = new Log();
        $message = 'Test Log message';
        $log->setMessage($message);
        $this->assertEquals($message, $log->getMessage());
    }
}
