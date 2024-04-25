<?php

namespace App\Twig;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use App\Contract\Service\MenuServiceInterface;

class TwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly MenuServiceInterface $menuService
    ) {
    }

    public function getFunctions(): array
    {
        return array(
            new TwigFunction('get_menu_items', array($this, 'getMenuItems')),
        );
    }

    public function getMenuItems(): iterable
    {
        return $this->menuService->getMenuItems();
    }
}