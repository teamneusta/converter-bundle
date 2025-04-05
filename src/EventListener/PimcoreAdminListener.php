<?php declare(strict_types=1);

namespace Neusta\ConverterBundle\EventListener;

use Pimcore\Event\BundleManager\PathsEvent;

final class PimcoreAdminListener
{
    public function addJSFiles(PathsEvent $event): void
    {
        $event->addPaths([
            '/bundles/neustaconverter/js/service-inspector.js.js',
        ]);
    }
}
