<?php

namespace App\Tests\Entity;

use App\Entity\RefreshToken;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;
use PHPUnit\Framework\TestCase;

class RefreshTokenTest extends TestCase
{
    public function testCreateRefreshToken(): void
    {
        $token = new RefreshToken();
        $this->assertInstanceOf(RefreshToken::class, $token);
        $this->assertInstanceOf(BaseRefreshToken::class, $token);
    }

    public function testTableName(): void
    {
        $reflection = new \ReflectionClass(RefreshToken::class);
        $table = $reflection->getAttributes(\Doctrine\ORM\Mapping\Table::class)[0]->newInstance();
        $this->assertEquals('refresh_tokens', $table->name);
    }
}
