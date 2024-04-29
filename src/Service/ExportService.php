<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;
use App\Contract\Service\ExportServiceInterface;
use App\Contract\Service\MenuServiceInterface;
use Exception;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

readonly class ExportService implements ExportServiceInterface
{
    public function __construct(
        private string $pageFolder,
        private string $exportFolder,
        private MenuServiceInterface $menuService,
        private Environment $twig
    ) {  
    }

    public function extractAllPageToExport(): array
    {
        $finder = new Finder();
        $filePaths = [];
        foreach ($finder->files()->in($this->pageFolder) as $file) {
            $filePath = $file->getRealPath();
            if (!$this->isPartOfBlog($filePath)) {
                $filePaths[] = $filePath;
            }
            if ($this->isPublishedPage($filePath)) {
                $filePaths[] = $filePath;
            };
        }
        return $filePaths;
    }

    public function defineExportFilePath(string $twigFilePath): string
    {
        $filePathArray = explode('Pages', $twigFilePath);
        $exportFilePath = str_replace('.twig', '', $filePathArray[1]);
        return $this->exportFolder . $exportFilePath;
    }

    public function convertTwigToHtml(string $twigFilePath, ?string $specificTemplateFolder = null): string
    {
        if ($specificTemplateFolder !== null) {
            $twigFilePath = $specificTemplateFolder . '/' . $twigFilePath;
        }
        return $this->twig->render($twigFilePath);
    }

    public function createSubFolderIfNeeded(string $exportFilePath): void
    {
        $fs = new Filesystem();
        $dirPath = dirname($exportFilePath);
        if (!$fs->exists($dirPath)) {
            mkdir($dirPath);
        }
    }

    public function isPublishedPage(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            throw new FileNotFoundException();
        }
        return $this->extractDataPublished($filePath) === 'true';
    }

    public function extractDataPublished(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        $pattern = '/Published\s*:\s*(.*?)\s*\n/';
        preg_match($pattern, $content, $matches);
        array_shift($matches);
        if (!isset($matches[0]) || (trim($matches[0]) !== 'true' && trim($matches[0] !== 'false'))) {
            return null;
        }
        return $matches[0];
    }

    public function isPartOfBlog(string $filePath): bool
    {
        $basename = basename($filePath);
        if ($basename === 'index.html.twig') {
            return false;
        }
        $blogFolder = $this->menuService->getDirectoriesForSlug();
        $isPartOfBlog = false;
        foreach ($blogFolder as $folderName) {
            $needle = $folderName . '/' . $basename;
            if (str_contains($filePath, $needle)) {
                $isPartOfBlog = true;
                break;
            }
        }
        return $isPartOfBlog;
    }

    public function compile(string $twigFilePath, ?string $specificTemplateFolder = null): bool
    {
        $fs = new Filesystem();
        try {
            $filePath = $this->defineExportFilePath($twigFilePath);
            $this->createSubFolderIfNeeded($filePath);
            $content = $this->convertTwigToHtml($twigFilePath, $specificTemplateFolder);
            $fs->dumpFile($filePath, $content);
        } catch(Exception $e) {
            return false;
        }

        return true; 
    }
}