<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Command;

use Neusta\ConverterBundle\Debug\Builder\ChartInfoBuilder;
use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Neusta\ConverterBundle\Debug\Model\ServiceArgumentInfo;
use Neusta\ConverterBundle\Debug\Model\ServiceInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

#[AsCommand(name: 'neusta:converter:debug', description: 'Displays debug information for converters, populators and factories')]
final class DebugCommand extends Command
{
    public function __construct(
        private readonly DebugInfo $debugInfo,
        private readonly ChartInfoBuilder $chartInfoBuilder,
        private readonly TranslatorInterface $translator,
        private readonly ?TwigEnvironment $twig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('out', null, InputOption::VALUE_REQUIRED, 'Path to the HTML file for static output')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Language to use', 'en')
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command displays a structured list of all tagged services that act as converters, factories or populators – including their constructor arguments.

                  <info>%command.full_name%</info>

                Optionally, you can generate static HTML documentation using the <info>--out</info> option:

                  <info>%command.full_name% --out=var/converter.html</info>

                The generated HTML file contains a linkable overview and can be used, for example, as part of developer documentation.
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->translator->setLocale($input->getOption('locale'));
        if ($out = $input->getOption('out')) {
            if (null === $this->twig) {
                throw new \LogicException(\sprintf(
                    'You cannot use the "%s" command if the Twig Bundle is not available. ' .
                    'Try running "composer require symfony/twig-bundle".',
                    $this->getName(),
                ));
            }

            $html = $this->twig->render('@NeustaConverter/debug/service_inspector.html.twig', [
                'services' => $this->debugInfo->services(),
                'chartInfo' => $this->chartInfoBuilder->buildFromDebugInfo($this->debugInfo),
            ]);

            file_put_contents($out, $html);
            $output->writeln("✅ HTML-Datei gespeichert: {$out}");

            return Command::SUCCESS;
        }

        if ($converters = $this->debugInfo->services('converter')) {
            $output->writeln('<info>🎯 Converter:</info>');
            $this->describeServices($converters, $output);
        }

        if ($populators = $this->debugInfo->services('populator')) {
            $output->writeln(['', '<info>🎯 Populatoren:</info>']);
            $this->describeServices($populators, $output);
        }

        if ($factories = $this->debugInfo->services('factory')) {
            $output->writeln(['', '<info>🎯 Factories:</info>']);
            $this->describeServices($factories, $output);
        }

        return Command::SUCCESS;
    }

    /**
     * @param iterable<string, ServiceInfo> $services
     */
    private function describeServices(iterable $services, OutputInterface $output): void
    {
        foreach ($services as $id => $service) {
            $output->writeln("🔧 <info>{$id}</info>: <comment>{$service->class}</comment>");
            $this->describeArguments($service->arguments, $output, 1);
        }
    }

    /**
     * @param array<ServiceArgumentInfo> $arguments
     */
    private function describeArguments(array $arguments, OutputInterface $output, int $level): void
    {
        foreach ($arguments as $name => $argument) {
            $output->write(str_repeat('  ', $level) . "- <info>{$name}</info>:");

            if (\is_array($argument->value)) {
                $output->writeln('');
                $this->describeArguments($argument->value, $output, $level + 1);
            } else {
                $output->writeln(" <comment>{$argument->value}</comment>");
            }
        }
    }
}
