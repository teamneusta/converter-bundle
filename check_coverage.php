<?php

$xmlContent = file_get_contents('.coverage/cobertura.xml');
$matches = [];
preg_match("#coverage line-rate=\"(.*)\"#U", $xmlContent, $matches);

$coverage = (int) ((float) $matches[1] * 100);

echo "Coverage: " . $coverage . "%\n";

if ($coverage < 80) {
    echo "Test coverage is below 80%\n";
    exit(1); // Gibt einen Fehlercode zurück, der den Build fehlschlagen lässt
}

echo "Test coverage is sufficient.\n";
exit(0);
