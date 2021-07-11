<?php

namespace ANOITCOM\EAVSemanticsBundle\Install;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class InstallCommand extends Command
{

    protected static $defaultName = 'eav-semantics:install';

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var KernelInterface
     */
    private $kernel;

    private string $migrationsDir;


    public function __construct(
        KernelInterface $kernel,
        Filesystem $fs,
        string $migrationsDir
    ) {
        parent::__construct(self::$defaultName);
        $this->kernel        = $kernel;
        $this->fs            = $fs;
        $this->migrationsDir = $migrationsDir;
    }


    protected function configure()
    {
        $this->addArgument('actions', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Available options: migration');
    }


    public function run(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $actions = $this->getActionsToDo($input);

        $io->writeln('Installing EAV Bundle');

        if (in_array('migration', $actions, true)) {
            $io->writeln('Installing migrations...');
            if ($this->isMigrationAlreadyInstalled()) {
                $io->warning('Migrations are already installed to ' . $this->migrationsDir);
            } else {
                $this->installMigrations();
                $io->success('Migrations installed to ' . $this->migrationsDir);
            }
        }

        $io->success('Installation complete successful');

        return 0;

    }


    private function isMigrationAlreadyInstalled(): bool
    {
        $finder = new Finder();
        $finder->files()->in($this->migrationsDir);

        foreach ($finder as $file) {
            $migrationsContent = $file->getContents();
            if (stripos($migrationsContent, 'EAV SEMANTICS MIGRATION MARK - DO NOT DELETE') !== false) {
                return true;
            }
        }

        return false;
    }


    private function installMigrations(): void
    {
        [ $migrationContent, $className ] = $this->compileMigration();

        $this->fs->dumpFile($this->migrationsDir . '/' . $className . '.php', $migrationContent);
    }


    private function compileMigration(): array
    {
        $templatePath = __DIR__ . '/Migrations/Migration.tpl.php';
        $className    = 'Version' . (new \DateTime('now', new \DateTimeZone('UTC')))->format('YmdHis');

        ob_start();

        include $templatePath;

        $content = ob_get_clean();

        return [ $content, $className ];

    }


    /**
     * @param InputInterface $input
     *
     * @return string[]
     */
    private function getActionsToDo(InputInterface $input): array
    {
        $allActions = [
            'migration',
        ];

        $actions = $input->getArgument('actions');

        if ( ! count($actions)) {
            return $allActions;
        }

        $actionsToDo = [];

        foreach ($actions as $action) {
            if ( ! in_array($action, $allActions, true)) {
                throw new \InvalidArgumentException('Action \'' . $action . '\' not supported!');
            }

            $actionsToDo[] = $action;
        }

        return $actionsToDo;
    }
}