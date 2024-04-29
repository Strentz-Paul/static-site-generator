<?php

namespace App\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use App\Contract\Service\ExportServiceInterface;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'project:compile',
    description: 'Compile and export project',
)]
class CompileCommand extends Command
{
    public function __construct(
        private readonly ExportServiceInterface $service,
        private readonly string $pageFolder
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pagesToExport = $this->service->extractAllPageToExport();
        $countOfFileCompiled = 0;
        $error = [];
        foreach($pagesToExport as $page) {
            try {
                $page = str_replace('app/templates/', '', $page);
                $exportFilePath = $this->service->compile($page);
                $countOfFileCompiled = $exportFilePath ? $countOfFileCompiled++ : $countOfFileCompiled;
            } catch (Exception $e) {
                $error[] = $e->getMessage();
            }
        }
        if (count($error) > 0) {
            $io->error(sprintf("There is some errors : %s", implode('\n    - ', $error)));
            return Command::FAILURE;
        }
        $successSentence = $countOfFileCompiled > 1 ? '%s files compiled. You can now use the export folder (%s) to publish your static site' : '%s file compiled. You can now use the export folder (%s) to publish your static site';
        $io->success(sprintf("$successSentence", $countOfFileCompiled, $this->pageFolder));
        return Command::SUCCESS;
    }
}
