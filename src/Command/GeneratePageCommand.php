<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'generate:page',
    description: 'Generate a page',
)]
class GeneratePageCommand extends Command
{
    public const ARG_PAGE_NAME = 'page_name';

    public function __construct(
        private readonly string $pageFolder, 
        private readonly string $pageTemplate
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
        $pageName = strtolower(str_replace(' ', '_', $pageName));
        $finder = new Finder();
        foreach ($finder->directories()->in($this->pageFolder) as $dir) {
            $directories[] = $dir->getBasename();
        }
        $fileBasePathUserSelection = $io->choice('Where do you want to store the page', $directories);
        $fileBasePath = $this->pageFolder . '/' . $fileBasePathUserSelection;
        $filePath = $fileBasePath . '/' . $pageName . '.html.twig';
        if (file_exists($filePath)) {
            $io->error(sprintf('A page named %s already exist at this path %s', $pageName, $filePath));
            return Command::FAILURE;
        }
        $fs = new Filesystem();
        $fs->copy($this->pageTemplate, $filePath);
        return Command::SUCCESS;
    }
}
