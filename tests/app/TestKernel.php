<?php

declare(strict_types=1);

use Neusta\ConverterBundle\NeustaConverterBundle;

final class TestKernel extends \Nyholm\BundleTest\TestKernel
{
    private ?string $testProjectDir = null;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        $this->addTestBundle(NeustaConverterBundle::class);
    }

    public function getProjectDir(): string
    {
        return $this->testProjectDir ?? __DIR__;
    }

    public function setTestProjectDir($projectDir): void
    {
        $this->testProjectDir = $projectDir;
    }
}
