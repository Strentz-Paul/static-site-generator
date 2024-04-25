<?php

namespace App\Tests;

use App\Contract\Service\MenuServiceInterface;
use App\Service\MenuService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MenuItemTest extends KernelTestCase
{
    private MenuServiceInterface $service;

    public function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->service = self::getContainer()->get(MenuService::class);
    }

    public function testGetItemsMenu(): void
    {
        $result = $this->service->getMenuItems();
        self::assertIsArray($result);
        self::assertCount(4, $result);
        foreach ($result as $key) {
            self::assertIsArray($key);
        }
        /** @var array $result */
        $keys = array_keys($result);
        self::assertSame('Home', $keys[0]);
        self::assertSame('Articles', $keys[1]);
        self::assertSame('About', $keys[2]);
        self::assertSame('Socials', $keys[3]);
        $values = array_values($result);
        self::assertSame(['url' => '/'], $values[0]);
        self::assertSame(['url' => '/articles'], $values[1]);
        self::assertSame(['url' => '/about'], $values[2]);
        self::assertSame(['url' => null, 'sub_menu' => ['X' => ['url' => 'https://x.com', 'external' => true], 'Github'  => ['url' => 'https://github.com', 'external' => true], 'Me'  => ['url' => '/socials/me']]], $values[3]);
    }
}
