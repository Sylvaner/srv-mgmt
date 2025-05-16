<?php

namespace App\Tests\Api;

use App\Entity\Server;

class ApiServerTest extends BaseApiTestCase
{
    public function testGetAll()
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/servers');
        $this->assertResponseIsSuccessful();
        $result = $response->toArray();
        $this->assertCount(3, $result);
    }

    public function testGetOne()
    {
        $firstServerId = $this->entityManager->getRepository(Server::class)->findAll()[0]->getId();
        $response = $this->createClientWithCredentials()->request('GET', '/api/servers/' . $firstServerId);
        $this->assertResponseIsSuccessful();
        $server = $response->toArray();
        $this->assertEquals($firstServerId, $server['id']);
        $this->assertEquals('Debian 12', $server['type']['label']);
        $this->assertEquals('GLPI', $server['apps'][0]['name']);
        $this->assertEquals('192.168.1.1', $server['ip']);
    }
}
