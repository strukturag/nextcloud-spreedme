<?php
/**
 * Nextcloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright struktur AG 2016
 */

namespace OCA\SpreedME\Helper;

class FileCounter {

	private $separator = '.';
	private $counter = 1;
	private $parts;

	public function __construct($filename) {
		$this->parts = explode($this->separator, $filename);
	}

	public function next() {
		$parts = $this->parts;
		if ($this->counter === 1) {
			$this->counter++;
			return implode($this->separator, $parts);
		}
		$extension = array_pop($parts);
		$name = array_pop($parts);
		$parts[] = $name . ' ' . $this->counter;
		$parts[] = $extension;
		$this->counter++;
		return implode($this->separator, $parts);
	}

}
