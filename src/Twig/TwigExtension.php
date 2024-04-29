<?php

namespace App\Twig;

use App\Contract\Service\ArticleServiceInterface;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use App\Contract\Service\MenuServiceInterface;
use Exception;

class TwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly MenuServiceInterface $menuService,
        private readonly ArticleServiceInterface $articleService
    ) {
    }

    public function getFunctions(): array
    {
        return array(
            new TwigFunction('get_menu_items', array($this, 'getMenuItems')),
            new TwigFunction('get_title_by_path', array($this, 'getTitleByPath')),
            new TwigFunction('get_articles', array($this, 'getArticles')),
        );
    }

    public function getMenuItems(): iterable
    {
        return $this->menuService->getMenuItems();
    }

    public function getTitleByPath(string $templateName): string
    {
        $templatePathArray = explode('/', $templateName);
        $stringTitle = '';
        foreach ($templatePathArray as $item) {
            if ($item === 'Pages') {
                continue;
            }
            if (str_contains($item, '.html.twig')) {
                if (strtolower($item) ==='index.html.twig') {
                    break;
                }
                $stringTitle = $this->articleService->getTitleData($templateName);
                break;
            }
            $stringTitle = ucfirst($item);
        }
        if (trim($stringTitle) === '') {
            $stringTitle = 'Home';
        }
        return $stringTitle;
    }

    public function getArticles(): iterable
    {
        return $this->articleService->getAllFormatedArticles();
    }
}