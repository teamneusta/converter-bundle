<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Command;

use Neusta\ConverterBundle\Debug\InspectedServicesRegistry;
use Neusta\ConverterBundle\Debug\ServiceArgumentInfo;
use Neusta\ConverterBundle\Debug\ServiceInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment as TwigEnvironment;

#[AsCommand(name: 'neusta_converter:show', description: 'show converters, populators and factories')]
final class ShowConvertersPopulatorsCommand extends Command
{
    public function __construct(
        private readonly InspectedServicesRegistry $registry,
        private readonly ?TwigEnvironment $twig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('html', null, InputOption::VALUE_REQUIRED, 'Path to the HTML file for static output')
            ->setHelp(<<<'HELP'
                This command displays a structured list of all tagged services
                that act as converters, factories or populators – including their constructor arguments.

                Optionally, you can generate static HTML documentation using the --html option:

                bin/console neusta_converter:show --html=var/converter.html

                The HTML file contains a linkable overview and can be used, for example,
                as part of developer documentation.
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $htmlPath = $input->getOption('html');

        if ($htmlPath) {
            if (null === $this->twig) {
                throw new \LogicException(sprintf(
                    'You cannot use the "%s" command if the Twig Bundle is not available. ' .
                    'Try running "composer require symfony/twig-bundle".',
                    $this->getName(),
                ));
            }

            $html = $this->twig->render('@NeustaConverter/debug/service_inspector.html.twig', [
                'services' => array_merge($this->registry->converters(), $this->registry->populators(), $this->registry->factories()),
            ]);

            file_put_contents($htmlPath, $html);
            $output->writeln("✅ HTML-Datei gespeichert: {$htmlPath}");

            return Command::SUCCESS;
        }

        $output->writeln('<info>🎯 Converter:</info>');
        $this->describeServices($this->registry->converters(), $output);

        $output->writeln('');
        $output->writeln('<info>🎯 Factories:</info>');
        $this->describeServices($this->registry->factories(), $output);

        $output->writeln('');
        $output->writeln('<info>🎯 Populatoren:</info>');
        $this->describeServices($this->registry->populators(), $output);

        return Command::SUCCESS;
    }

    /**
     * @param iterable<string, ServiceInfo> $services
     */
    private function describeServices(iterable $services, OutputInterface $output): void
    {
        foreach ($services as $id => $service) {
            $output->writeln("🔧 <info>{$id}</info>: <comment>{$service->class}</comment>");

            $this->writeArgumentsArray($service->arguments, $output, 1);
            $output->writeln('');
        }
    }

    /**
     * @param array<ServiceArgumentInfo> $arguments
     */
    private function writeArgumentsArray(array $arguments, OutputInterface $output, int $level): void
    {
        foreach ($arguments as $name => $argument) {
            if (\is_array($argument->value)) {
                $output->writeln(str_repeat('  ', $level) . "- <info>{$name}</info>:");
                $this->writeArgumentsArray($argument->value, $output, $level + 1);
            } else {
                $output->writeln(str_repeat('  ', $level) . "- <info>{$name}</info>: <comment>{$argument->value}</comment>");
            }
        }
    }
}
