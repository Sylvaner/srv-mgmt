<?php

namespace App\Tests\Api;

use App\Entity\ServerType;

class ApiServerTypeTest extends BaseApiTestCase
{
    public function testGetAll()
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/server_types');
        $this->assertResponseIsSuccessful();
        $result = $response->toArray();
        $this->assertCount(2, $result);
    }

    public function testGetOne()
    {
        $firstServerTypeId = $this->entityManager->getRepository(ServerType::class)->findAll()[0]->getId();
        $response = $this->createClientWithCredentials()->request('GET', '/api/server_types/' . $firstServerTypeId);
        $this->assertResponseIsSuccessful();
        $serverType = $response->toArray();
        $this->assertEquals($firstServerTypeId, $serverType['id']);
        $this->assertEquals('Windows Server', $serverType['label']);
    }
}
