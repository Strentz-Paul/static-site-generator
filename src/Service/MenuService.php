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

    /**
     * @inheritdoc
     */
    public function getDirectoriesForSlug(): array
    {
        $menuData = Yaml::parseFile($this->menuFile);
        $menuArray = $this->processMenu($menuData);
        $directories = [];
        foreach ($menuArray as $key => $item) {
            if (!isset($item['has_slug']) && !isset($item['sub_menu'])) {
                continue;
            }
            if (isset($item['has_slug'])) {
                $directories[] = $key;
            }
            if (isset($item['sub_menu'])) {
                foreach ($item['sub_menu'] as $keySub => $itemSub) {
                    if (isset($itemSub['has_slug'])) {
                        $directories[] = $key . '/' . $keySub;
                    }
                }
            }
        }
        return $directories;
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
            if (isset($value['has_slug'])) {
                $menu[$key]['has_slug'] = true;
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