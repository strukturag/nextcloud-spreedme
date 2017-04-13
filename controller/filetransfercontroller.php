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

namespace OCA\SpreedME\Controller;

use OCA\SpreedME\Errors\ErrorCodes;
use OCA\SpreedME\Helper\Helper;
use OCA\SpreedME\Settings\Settings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\ILogger;
use OCP\IRequest;

class FileTransferController extends Controller {

	private $logger;
	private $rootFolder;

	public function __construct($appName, IRequest $request, $userId, ILogger $logger, IRootFolder $rootFolder) {
		parent::__construct($appName, $request);

		$this->logger = $logger;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function uploadAndShare($target) {
		$_response = array('success' => false);
		$target = stripslashes($target); // TODO(leon): Is this really required? Found it somewhere

		// TODO(leon): Validate token!

		if (!Helper::areFileTransferUploadsAllowed() || !Helper::doesServiceUserExist()) {
			$_response['error'] = ErrorCodes::FILETRANSFER_DISABLED;
		} else {
			try {
				$file = $this->request->getUploadedFile('file');
				if (empty($file)) {
					throw new \Exception('No file uploaded');
				}
				$fileName = $file['name'];
				if (is_array($fileName)) {
					// TODO(leon): We should support multiple file_s_
					throw new \Exception('Only a single file may be uploaded');
				}
				if ($file['error'] !== UPLOAD_ERR_OK) {
					throw new \Exception('Upload error: ' . $file['error']);
				}
				// TODO(leon): We don't need this check?
				if (!is_uploaded_file($file['tmp_name'])) {
					throw new \Exception('Uploaded file is not an uploaded file?');
				}
				if ($file['size'] > Helper::getServiceUserMaxUploadSize()) {
					throw new \Exception('Uploaded file is too big');
				}
				// Validate file extension
				// Keep in sync with SUPPORTED_DOCUMENT_TYPES
				$allowedFileExtensions = array(
					'.pdf',
					'.odf',
				);
				$isAllowedFileExtension = false;
				foreach ($allowedFileExtensions as $extension) {
					if (stripos(strrev($fileName), strrev($extension)) === 0) {
						// Found allowed extension
						$isAllowedFileExtension = true;
						break;
					}
				}
				if (!$isAllowedFileExtension) {
					throw new \Exception('Unsupported file extension');
				}

				$serviceUserFolder = $this->rootFolder->getUserFolder(Settings::SPREEDME_SERVICEUSER_USERNAME);
				$uploadFolder = $serviceUserFolder
					->newFolder(Settings::SPREEDME_SERVICEUSER_UPLOADFOLDER)
					->newFolder($target);
				$newFile = $uploadFolder->newFile($fileName);
				$newFile->putContent(file_get_contents($file['tmp_name']));

				$shareToken = Helper::runAsServiceUser(function () use ($newFile) {
					return \OCP\Share::shareItem(
						'file',
						$newFile->getId(),
						\OCP\Share::SHARE_TYPE_LINK,
						null, /* shareWith */
						\OCP\Constants::PERMISSION_READ,
						null, /* itemSourceName */
						null/* expirationDate, TODO(leon): Add support for this */
					);
				});

				if (!is_string($shareToken)) {
					throw new \Exception('Failed to share uploaded file');
				}

				$_response['token'] = $shareToken;
				$_response['success'] = true;
			} catch (\Exception $e) {
				$this->logger->logException($e, ['app' => Settings::APP_ID]);
				$_response['error'] = ErrorCodes::FILETRANSFER_FAILED;
			}
		}

		return new DataResponse($_response);
	}

}
