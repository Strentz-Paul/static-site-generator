<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;
use App\Contract\Service\MenuServiceInterface;

class MenuService implements MenuServiceInterface
{
    public function __construct(
        private readonly string $menuFile
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getMenuItems(): array
    {
        $menuData = Yaml::parseFile($this->menuFile);

        return $this->processMenu($menuData);
    }

    private function processMenu(array $menuData): array
    {
        $menu = [];
        foreach ($menuData as $key => $value) {
            if ($key === 'menu') {
                return $this->processMenu($value);
            }
            if (isset($value['submenu'])) {
                $subMenu = $this->processSubMenu($value['submenu']);
                $menu[$key] = ['url' => $value['url'], 'sub_menu' => $subMenu];
            } else {
                $menu[$key] = ['url' => $value['url']];
            }
        }

        return $menu;
    }

    private function processSubMenu(array $subMenuData): array
    {
        $subMenu = [];

        foreach ($subMenuData as $subKey => $subValue) {
            $subMenu[$subKey]['url'] = $subValue['url'];
            if (isset($subValue['external'])) {
                $subMenu[$subKey]['external'] = $subValue['external'];
            }
        }

        return $subMenu;
    }
}