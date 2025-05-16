<?php

namespace App\Tests\Entity;

use App\Entity\Setting;
use PHPUnit\Framework\TestCase;

class SettingTest extends TestCase
{
    public function testCreateSetting(): void
    {
        $setting = new Setting();
        $this->assertInstanceOf(Setting::class, $setting);
    }

    public function testProperties(): void
    {
        $setting = new Setting();
        
        // Test name
        $name = 'test.setting';
        $setting->setName($name);
        $this->assertEquals($name, $setting->getName());

        // Test value
        $value = 'test value';
        $setting->setValue($value);
        $this->assertEquals($value, $setting->getValue());
    }

    public function testApiGroups(): void
    {
        $setting = new Setting();
        $reflection = new \ReflectionClass($setting);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $attributes = $property->getAttributes(Groups::class);
            if (!empty($attributes)) {
                $groups = $attributes[0]->newInstance()->groups;
                $this->assertContains('setting:read', $groups);
            }
        }
        $this->assertGreaterThan(0, count($properties));
    }

    public function testValue(): void
    {
        $setting = new Setting();
        $name = 'test.setting';
        $value = 'test value';
        
        $setting->setName($name);
        $setting->setValue($value);
        
        $this->assertEquals($name, $setting->getName());
        $this->assertEquals($value, $setting->getValue());
    }
}
