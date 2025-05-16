<?php

namespace App\Tests\Api;

use App\Entity\Config;

class ApiSettingTest extends BaseApiTestCase
{
    public function testGetConfigAllItems()
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/settings');
        $this->assertResponseIsSuccessful();
        $result = $response->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals('alert_threshold', $result[0]['name']);
        $this->assertEquals('30', $result[0]['value']);
    }

    public function testGetConfigItem()
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/settings/warning_threshold');
        $this->assertResponseIsSuccessful();
        $result = $response->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals('warning_threshold', $result['name']);
        $this->assertEquals('10', $result['value']);
    }
}
