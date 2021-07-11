<?php

namespace ANOITCOM\EAVSemanticsBundle\DependencyInjection\CompilerPass;

use ANOITCOM\EAVBundle\DependencyInjection\CompilerPass\MigrationsDirResolver;
use ANOITCOM\EAVSemanticsBundle\Install\InstallCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EAVSemanticsMigrationsDirPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $resolver = new MigrationsDirResolver($container);

        $container
            ->getDefinition(InstallCommand::class)
            ->setArgument('$migrationsDir', $resolver->resolve());
    }
}