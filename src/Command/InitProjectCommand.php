<?php

namespace App\Command;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use App\Contract\Service\MenuServiceInterface;
use function PHPUnit\Framework\throwException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'init:project',
    description: 'Initialization from the menu.yaml file',
)]
class InitProjectCommand extends Command
{
    public function __construct(
        private readonly string $pageFolder,
        private readonly string $pageTemplate,
        private readonly MenuServiceInterface $menuService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $menu = $this->menuService->getMenuItems();
        $filesToCreate = [];
        foreach ($menu as $key => $item) {
            $pageNameToCreate = $item['url'];
            if ($pageNameToCreate === null) {
                if (!isset($item['sub_menu'])) {
                    throw new Exception(sprintf('Url must be set in the %s menu configuration (config/menu.yaml file). Please check the documentation', $key));
                }
                $baseSubPath = $this->pageFolder . '/' . $key;
                foreach ($item['sub_menu'] as $subKey => $subItem) {
                    $pageNameToCreate = $subItem['url'];
                    if (isset($subItem['external']) && $subItem['external'] === true) {
                        continue;
                    }
                    $pageNameToCreate = $pageNameToCreate[0] === '/' ? substr($pageNameToCreate, 1) : $pageNameToCreate;
                    if (str_contains($pageNameToCreate, strtolower($key) . '/')) {
                        $pageNameToCreate = str_replace(strtolower($key) . '/', '', $pageNameToCreate);
                    }
                    if ($pageNameToCreate === strtolower($subKey)) {
                        $pageNameToCreate = 'index.html.twig';
                    } else {
                        $pageNameToCreate .= '.html.twig';
                    }
                    $fullFilePath = $baseSubPath . '/' . $subKey . '/' . $pageNameToCreate;
                    $filesToCreate[] = $fullFilePath;
                }
                continue;
            }
            if (isset($item['external']) && $item['external'] === true) {
                continue;
            }
            if ($pageNameToCreate === null) {
                $pageNameToCreate = 'index.html.twig';
            }
            $pageNameToCreate = $pageNameToCreate[0] === '/' ? substr($pageNameToCreate, 1) : $pageNameToCreate;
            $isHomePage = false;
            if (trim($pageNameToCreate) === '') {
                $pageNameToCreate = 'index.html.twig';
                $isHomePage = true;
            }
            $pageNameToCreate = strtolower($pageNameToCreate);
            if ($pageNameToCreate === 'index.html.twig' || $pageNameToCreate === strtolower($key)) {
                $pageNameToCreate = 'index.html.twig';
            } else {
                $pageNameToCreate .= '.html.twig';
            }
            $fullFilePath = $this->pageFolder . '/' . $key . '/' . $pageNameToCreate;
            if ($isHomePage) {
                $fullFilePath = $this->pageFolder . '/' . $pageNameToCreate;
            }
            $filesToCreate[] = $fullFilePath;
            if (isset($item['sub_menu'])) {
                $baseSubPath = $this->pageFolder . '/' . $key;
                foreach ($item['sub_menu'] as $subKey => $subItem) {
                    $pageNameToCreate = $subItem['url'];
                    if (isset($subItem['external']) && $subItem['external'] === true) {
                        continue;
                    }
                    $pageNameToCreate = $pageNameToCreate[0] === '/' ? substr($pageNameToCreate, 1) : $pageNameToCreate;
                    if (str_contains($pageNameToCreate, strtolower($key) . '/')) {
                        $pageNameToCreate = str_replace(strtolower($key) . '/', '', $pageNameToCreate);
                    }
                    if ($pageNameToCreate === strtolower($subKey)) {
                        $pageNameToCreate = 'index.html.twig';
                    } else {
                        $pageNameToCreate .= '.html.twig';
                    }
                    $fullFilePath = $baseSubPath . '/' . $subKey . '/' . $pageNameToCreate;
                    $filesToCreate[] = $fullFilePath;
                }
            }
        }
        foreach ($filesToCreate as $file) {
            if (file_exists($file)) {
                continue;
            }
            $fs = new Filesystem();
            $fs->copy($this->pageTemplate, $file);
        }
        $io->success('Init done, you can now update all the files in templates/Pages folder');
        return Command::SUCCESS;
    }
}
