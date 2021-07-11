<?php

namespace ANOITCOM\EAVSemanticsBundle;

use ANOITCOM\EAVSemanticsBundle\DependencyInjection\CompilerPass\EAVSemanticsMigrationsDirPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EAVSemanticsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EAVSemanticsMigrationsDirPass());
    }
}