<?php

declare(strict_types=1);

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../vendor/autoload.php';
$root = dirname(__DIR__);

// bin/bench-baseline symlinks vendor/ into a worktree of an older commit, but the autoloader's
// generated PSR-4 map still carries this checkout's absolute paths - remap explicitly so a
// baseline run measures its own src/, not this checkout's.
$loader->setPsr4('Neusta\\ConverterBundle\\', [$root . '/src']);
$loader->setPsr4('Neusta\\ConverterBundle\\Benchmarks\\', [$root . '/benchmarks']);
