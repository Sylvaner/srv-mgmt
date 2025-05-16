<?php

namespace App\Tests\Api;

use App\Entity\AppUpdateType;

class ApiAppUpdateTypeTest extends BaseApiTestCase
{
    public function testGetAll()
    {
        $response = $this->createClientWithCredentials()->request('GET', '/api/app_update_types');
        $this->assertResponseIsSuccessful();
        $result = $response->toArray();
        $this->assertCount(2, $result);
    }

    public function testGetOne()
    {
        $firstAppUpdateTypeId = $this->entityManager->getRepository(AppUpdateType::class)->findAll()[0]->getId();
        $response = $this->createClientWithCredentials()->request('GET', '/api/app_update_types/' . $firstAppUpdateTypeId);
        $this->assertResponseIsSuccessful();
        $appUpdateType = $response->toArray();
        $this->assertEquals($firstAppUpdateTypeId, $appUpdateType['id']);
        $this->assertEquals('Debian package', $appUpdateType['name']);
    }
}
