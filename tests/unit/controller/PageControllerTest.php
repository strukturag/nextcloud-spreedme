<?php
/**
 * ownCloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright struktur AG 2016
 */

namespace OCA\SpreedME\Controller;

use PHPUnit_Framework_TestCase;

class PageControllerTest extends PHPUnit_Framework_TestCase {

	private $controller;

	public function setUp() {
		$request = $this->getMockBuilder('OCP\IRequest')->getMock();

		$this->controller = new PageController(
			'spreedme', $request, $this->userId
		);
	}

	public function testTrue() {
		$this->assertTrue(true);
	}

}