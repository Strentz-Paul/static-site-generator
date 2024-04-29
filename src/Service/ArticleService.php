<?php

namespace App\Service;

use App\Contract\Service\ArticleServiceInterface;
use App\Contract\Service\MenuServiceInterface;
use App\ViewModel\ArticleViewModel;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Finder\Finder;

class ArticleService implements ArticleServiceInterface
{

    public function __construct(
        private readonly MenuServiceInterface $menuService,
        private readonly string $pageFolder
    ) {
    }

    public function getAllFormatedArticles(): iterable
    {
        $articles = new ArrayCollection();
        $directoriesToCheck = $this->menuService->getDirectoriesForSlug();
        $rawFiles = [];
        $finder = new Finder();
        foreach ($directoriesToCheck as $articleDir) {
            $folderPath = $this->pageFolder . '/' . $articleDir;
            foreach ($finder->files()->in($folderPath) as $item) {
                if ($item->getFilename() === 'index.html.twig') {
                    continue;
                }
                $rawFiles[] = $item->getPathname();
            }
        }
        foreach ($rawFiles as $file) {
            $articles->add($this->generateVM($file));
        }
        return $articles;
    }

    public function getTitleData(string $templateName): string
    {
        $filePath = $this->pageFolder . '/' . str_replace('Pages', '', $templateName);
        $allDatas = $this->extractData($filePath);
        return ucfirst($allDatas[0]);
    }

    private function generateVM(string $filePath): ArticleViewModel
    {
        [$title, $date, $draft] = $this->extractData($filePath);
        $draft = !($draft === 'true');
        $url = $this->generateUrl($filePath);
        return new ArticleViewModel($url, $title, $date, (bool)$draft);
    }

    private function extractData(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $pattern = '/Title\s*:\s*(.*?)\s*\nDate\s*:\s*(.*?)\s*\nPublished\s*:\s*(.*?)\s*\n/';
        preg_match($pattern, $content, $matches);
        array_shift($matches);
        return $matches;
    }

    private function generateUrl(string $filePath): string
    {
        return strtolower(str_replace(['/app/templates/Pages/', '.html.twig'], '', $filePath));
    }
}