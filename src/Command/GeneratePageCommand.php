<?php

namespace App\Command;

use App\Contract\Service\MenuServiceInterface;
use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'generate:page',
    description: 'Generate a page',
)]
class GeneratePageCommand extends Command
{
    public const ARG_PAGE_NAME = 'page_name';

    public function __construct(
        private readonly string $pageFolder, 
        private readonly string $pageTemplate,
        private readonly MenuServiceInterface $menuService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::ARG_PAGE_NAME, InputArgument::OPTIONAL, 'The name of the page')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pageName = $input->getArgument(self::ARG_PAGE_NAME);
        if ($pageName === null) {
            $pageName = $io->ask('Choose a Page name (e.g. <comment>Why am I using StaticGen</comment>) ');
        }
        $slugger = new AsciiSlugger();
        $pageNameSlugged = strtolower($slugger->slug($pageName));
        $directories = $this->menuService->getDirectoriesForSlug();
        $fileBasePathUserSelection = $io->choice('Where do you want to store the page', $directories);
        $fileBasePath = $this->pageFolder . '/' . $fileBasePathUserSelection;
        $filePath = $fileBasePath . '/' . $pageNameSlugged . '.html.twig';
        if (file_exists($filePath)) {
            $io->error(sprintf('A page named %s already exist at this path %s', $pageNameSlugged, $filePath));
            return Command::FAILURE;
        }
        $fs = new Filesystem();
        $fs->copy($this->pageTemplate, $filePath);
        $content = file_get_contents($filePath);
        $date = (new DateTime())->format('d-m-Y');
        $informations = "{#\n---\nTitle : $pageName\nDate : $date\nPublished : false\n---\n#}\n";
        $content = $informations . $content;
        $fs->dumpFile($filePath, $content);
        return Command::SUCCESS;
    }
}
