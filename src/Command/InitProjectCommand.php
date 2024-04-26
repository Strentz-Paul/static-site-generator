<?php

namespace App\Command;

use Exception;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Filesystem;
use App\Contract\Service\MenuServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsCommand(
    name: 'init:project',
    description: 'Initialization from the menu.yaml file',
)]
class InitProjectCommand extends Command
{
    public function __construct(
        private readonly string $pageFolder,
        private readonly string $pageTemplate,
        private readonly string $pageTemplateWithSlug,
        private readonly MenuServiceInterface $menuService,
        private readonly Generator $generator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $menu = $this->menuService->getMenuItems();
        $filesToCreate = [];
        $filesWithSlug = [];
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
            $hasSlug = false;
            if (isset($item['has_slug'])) {
                $hasSlug = true;
                $filesWithSlug[] = $fullFilePath;
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
                    if (isset($item['has_slug'])) {
                        $filesWithSlug[] = $fullFilePath;
                    }
                    $filesToCreate[] = $fullFilePath;
                }
            }
        }
        if (count($filesWithSlug) > 1) {
            $io->error('There is more than one menu item for slug part. Please check the documentation');
            return Command::FAILURE;
        }

        foreach ($filesToCreate as $file) {
            if (file_exists($file)) {
                continue;
            }
            $hasSlug = false;
            $pageTemplate = $this->pageTemplate;
            if (in_array($file, $filesWithSlug)) {
                $hasSlug = true;
                $pageTemplate = $this->pageTemplateWithSlug;
            }
            $fs = new Filesystem();
            $fs->copy($pageTemplate, $file);
            $explodeFilePath = str_replace('/app/templates/Pages/', '', $file);
            $hasMoreEntropy = count(explode('/', $explodeFilePath)) > 2;
            $isHomeContext = count(explode('/', $explodeFilePath)) === 1;
            
            $controllerNameArray = explode('/', $file);
            $arrayLenght = count($controllerNameArray);
            $controllerName = $controllerNameArray[$arrayLenght - 2];
            $controllerClassNameDetails = $this->generator->createClassNameDetails(
                $controllerName,
                'Controller\\',
                'Controller'
            );
            $customRoutePath = Str::asRoutePath($controllerClassNameDetails->getRelativeNameWithoutSuffix());
            $routeName = Str::asRouteName($controllerClassNameDetails->getRelativeNameWithoutSuffix());
            $templateName = Str::asFilePath($controllerClassNameDetails->getRelativeNameWithoutSuffix());
            $customControllerFullName = $controllerClassNameDetails->getFullName();
            if ($hasMoreEntropy) {
                $customRoutePath = '/' . strtolower(str_replace('/index.html.twig', '', $explodeFilePath));
                $routeName = 'app_' . strtolower(str_replace(['/index.html.twig', '/'], ['', '_'], $explodeFilePath));
                $templateName = str_replace('/index.html.twig', '', $explodeFilePath);
                $customControllerFullName = $controllerClassNameDetails->getRelativeNameWithoutSuffix();
                $fullNamespace = $controllerClassNameDetails->getFullName();
                $className = $controllerClassNameDetails->getShortName();
                $customControllerFullName = str_replace($className, '', $fullNamespace);
                $indexPages = array_search('Pages', $controllerNameArray);
                $indexControllerName = array_search($controllerClassNameDetails->getRelativeNameWithoutSuffix(), $controllerNameArray);
                for ($i = $indexPages + 1; $i <= $indexControllerName; $i++) {
                    $customControllerFullName .= $controllerNameArray[$i] . '\\';
                }
                $customControllerFullName .= $className;
            }
            if ($isHomeContext) {
                $customRoutePath = '/';
                $routeName = 'app_home';
                $templateName = '';
                $customControllerFullName = 'App\Controller\HomeController';
            }
    
            $useStatements = new UseStatementGenerator([
                AbstractController::class,
                Response::class,
                Route::class,
            ]);
            $defaultTemplateName = $isHomeContext ? 'index.html.twig' : '/index.html.twig';
            $templateNameIndex = $templateName . $defaultTemplateName;
            if ($hasSlug) {
                if ($isHomeContext) {
                    throw new Exception('Home page cannot be with slug');
                }
                $customRoutePathSlug = $customRoutePath . '/{slug}';
                $routeNameSlug = $routeName . '_slug';
                $templateNameSlug = $templateName . "/\$slug.html.twig";
                $this->generator->generateController(
                    $customControllerFullName,
                    __DIR__ . '/../Templates/ControllerWithSlug.tpl.php',
                    [
                        'use_statements' => $useStatements,
                        'route_path' => $customRoutePath,
                        'route_name' => $routeName,
                        'method_name' => 'index',
                        'with_template' => true,
                        'template_name' => $templateNameIndex,
                        'route_path_slug' => $customRoutePathSlug,
                        'route_name_slug' => $routeNameSlug,
                        'method_name_slug' => 'pageWithSlug',
                        'template_name_slug' => $templateNameSlug,
                        'page_title' => $templateName
                    ]
                );
            } else {
                $this->generator->generateController(
                    $customControllerFullName,
                    __DIR__ . '/../Templates/Controller.tpl.php',
                    [
                        'use_statements' => $useStatements,
                        'route_path' => $customRoutePath,
                        'route_name' => $routeName,
                        'method_name' => 'index',
                        'with_template' => true,
                        'template_name' => $templateNameIndex,
                    ]
                );
            }
            $this->generator->writeChanges();
        }
        $io->success('Init done, you can now update all the files in templates/Pages folder');
        return Command::SUCCESS;
    }
}
