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

namespace OCA\SpreedME\Security;

use OCA\SpreedME\Errors\ErrorCodes;
use OCA\SpreedME\Helper\Helper;
use OCA\SpreedME\Security\Security;
use OCP\IDBConnection;

class TemporaryPasswordManager {

	private $db;
	private $tableName = 'spreedme_tps';
	private $hashFuncName = 'sha256';
	private $maxUserLength = 64; // Keep in sync with database.xml
	private $disallowedUserChars = array(':', '/');
	private $temporaryPasswordLength = 10; // ld(55^10) ≈ 58 bit of entropy
	private $temporaryPasswordAllowedChars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789';

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	// So we don't leak anything due to timing side channels
	private function hashTemporaryPassword($tp) {
		return hash($this->hashFuncName, $tp);
	}

	private function getNewTemporaryPassword() {
		return Security::getRandomString($this->temporaryPasswordLength, $this->temporaryPasswordAllowedChars);
	}

	private function requireEnabledTemporaryPassword() {
		if (Helper::getConfigValue('OWNCLOUD_TEMPORARY_PASSWORD_LOGIN_ENABLED') !== true) {
			throw new \Exception('Temporary Passwords not enabled in config/config.php', ErrorCodes::TEMPORARY_PASSWORD_NOT_ENABLED);
		}
	}

	private function decorateUserId($userid, $prefix) {
		// Prefix userid with ext/ or int/ and append /uniqueid
		$uniqueid = uniqid('', true);
		return sprintf('%s/%s/%s', $prefix, $userid, $uniqueid);
	}

	private function getFormattedDate($date) {
		return $date->format('Y-m-d H:i:s');
	}

	public function generateTemporaryPassword($userid, $expirationUnix) {
		// Validate userid length
		if (strlen($userid) > $this->maxUserLength) {
			throw new \Exception('userid too long', ErrorCodes::TEMPORARY_PASSWORD_USERID_TOO_LONG);
		}

		// Prevent certain characters in userid
		foreach ($this->disallowedUserChars as $char) {
			if (strpos($userid, $char) !== false) {
				throw new \Exception('userid may not contain one of these symbols: ' . join(' or ', $this->disallowedUserChars), ErrorCodes::TEMPORARY_PASSWORD_INVALID_USERID);
			}
		}

		$expiration = new \DateTime();
		$expiration->setTimestamp($expirationUnix);

		// Generate TP and insert it to the DB in hashed form
		// Repeat until we inserted an unique one
		while (true) {
			$tp = $this->getNewTemporaryPassword();
			$query = $this->db->getQueryBuilder();
			try {
				$query->insert($this->tableName)->values(array(
					'tp' => $query->createNamedParameter($this->hashTemporaryPassword($tp)),
					'userid' => $query->createNamedParameter($userid),
					'expiration' => $query->createNamedParameter($this->getFormattedDate($expiration)),
				))->execute();
				return $tp;
			} catch (UniqueConstraintViolationException $e) {
				// Whoops, hash collision
				// You are likely one of the first people to find a $this->hashFuncName collision
				// Unfortunately I don't tell you about it ;) Fame = vanished
			}
		}
	}

	// Returns false or the TP's decorated userid
	public function validateTemporaryPassword($tp) {
		$now = new \DateTime();
		$query = $this->db->getQueryBuilder();
		$useridField = 'userid';
		$query
			->select($useridField)
			->from($this->tableName)
			->where($query->expr()->eq('tp', $query->createNamedParameter($this->hashTemporaryPassword($tp))))
			->andWhere($query->expr()->gte('expiration', $query->createNamedParameter($this->getFormattedDate($now))))
			->execute();

		$result = $query->execute();
		$user = false;
		// TODO(leon): Why do we loop?
		while ($row = $result->fetch()) {
			$user = $this->decorateUserId($row[$useridField], 'ext');
		}
		$result->closeCursor();
		return $user;
	}

	public function getSignedComboFromTemporaryPassword($tp) {
		$this->requireEnabledTemporaryPassword();

		$userid = $this->validateTemporaryPassword($tp);
		if ($this->validateTemporaryPassword($tp) === false) {
			throw new \Exception('Invalid Temporary Password', ErrorCodes::TEMPORARY_PASSWORD_INVALID);
		}

		return Security::getSignedCombo($userid);
	}

}
