<?php

use \OCA\SpreedWebRTC\Changelog\Changelog;
use \OCA\SpreedWebRTC\Config\Config;

$changes_by_version = Changelog::getChangesSinceVersion(0);
$response = '';

foreach($changes_by_version as $version => $changes) {
	$response .= sprintf(
		'<h2>Version %s:</h2>',
		$version
	);
	foreach($changes as $change) {
		$response .= sprintf(
			'* %s<br />',
			$change
		);
	}
	$response .= '<br />' . PHP_EOL;
}

echo $response;
