<?php

namespace OCA\ScienceMesh\Controller;

use OCP\IRequest;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\ISession;
use OCP\IConfig;

use OCP\IL10N;
use OCP\App\IAppManager;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

use OC\Files\View;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use \OCP\Files\NotFoundException;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\OCS\OCSNotFoundException;

use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;

use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Share\Exceptions\ShareNotFound;

use OCA\CloudFederationAPI\Config;
use OCA\Files_Trashbin\Trash\ITrashManager;

use OCA\ScienceMesh\AppInfo\ScienceMeshApp;
use OCA\ScienceMesh\ShareProvider\ScienceMeshShareProvider;

use Psr\Log\LoggerInterface;

define('RESTRICT_TO_SCIENCEMESH_FOLDER', false);
define('EFSS_PREFIX', (RESTRICT_TO_SCIENCEMESH_FOLDER ? 'sciencemesh/' : ''));
define('REVA_PREFIX', '/home/'); // See https://github.com/pondersource/sciencemesh-php/issues/96#issuecomment-1298656896

class RevaController extends Controller
{

	/* @var ISession */
	private $session;

	/** @var LoggerInterface */
	private $logger;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ICloudFederationProviderManager */
	private $cloudFederationProviderManager;

	/** @var Config */
	private $config;

	/** @var ICloudFederationFactory */
	private $factory;

	/** @var ICloudIdManager */
	private $cloudIdManager;

	/** @var \OCP\Files\Node */
	private $lockedNode;

	/** @var IAppManager */
	private $appManager;

	/** @var IL10N */
	private $l;

	public function __construct(
		$AppName,
		IRootFolder $rootFolder,
		IRequest $request,
		ISession $session,
		IUserManager $userManager,
		IURLGenerator $urlGenerator,
		$userId,
		IConfig $config,
		\OCA\ScienceMesh\Service\UserService $UserService,
		ITrashManager $trashManager,
		IManager $shareManager,
		IGroupManager $groupManager,
		ICloudFederationProviderManager $cloudFederationProviderManager,
		ICloudFederationFactory $factory,
		ICloudIdManager $cloudIdManager,
		LoggerInterface $logger,
		IAppManager $appManager,
		IL10N $l10n,
		ScienceMeshShareProvider $shareProvider
	) {
		parent::__construct($AppName, $request);
		require_once(__DIR__ . '/../../vendor/autoload.php');

		$this->rootFolder = $rootFolder;
		$this->request = $request;
		$this->session = $session;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;

		$this->config = new \OCA\ScienceMesh\ServerConfig($config);

		$this->trashManager = $trashManager;
		$this->shareManager = $shareManager;
		$this->groupManager = $groupManager;
		$this->cloudFederationProviderManager = $cloudFederationProviderManager;
		$this->factory = $factory;
		$this->cloudIdManager = $cloudIdManager;
		$this->logger = $logger;
		$this->appManager = $appManager;
		$this->l = $l10n;
		$this->shareProvider = $shareProvider;
	}

	private function init($userId)
	{
		error_log("RevaController init for user '$userId'");
		$this->userId = $userId;
		$this->checkRevadAuth();
		if ($userId) {
			error_log("root folder absolute path '" . $this->rootFolder->getPath() . "'");
			$this->userFolder = $this->rootFolder->getUserFolder($userId);
		}
	}

	private function getDomainFromURL($url)
	{
		// converts https://revaowncloud1.docker/ to revaowncloud1.docker
		// Note: DO not use it on anything whithout http(s) in the start, it would return null.
		return str_ireplace("www.", "", parse_url($url, PHP_URL_HOST));
	}

	private function removePrefix($string, $prefix)
	{
		// first check if string is actually prefixed or not.
		$len = strlen($prefix);
		if (substr($string, 0, $len) === $prefix) {
			$ret = substr($string, $len);
		} else {
			$ret = $string;
		}

		return $ret;
	}

	private function revaPathFromOpaqueId($opaqueId)
	{
		return $this->removePrefix($opaqueId, "fileid-");
	}

	private function revaPathToEfssPath($revaPath)
	{
		if ("$revaPath/" == REVA_PREFIX) {
			error_log("revaPathToEfssPath: Interpreting special case $revaPath as ''");
			return '';
		}
		$ret = EFSS_PREFIX . $this->removePrefix($revaPath, REVA_PREFIX);
		error_log("revaPathToEfssPath: Interpreting $revaPath as $ret");
		return $ret;
	}

	private function efssPathToRevaPath($efssPath)
	{
		$ret = REVA_PREFIX . $this->removePrefix($efssPath, EFSS_PREFIX);
		error_log("efssPathToRevaPath: Interpreting $efssPath as $ret");
		return $ret;
	}

	private function efssFullPathToRelativePath($efssFullPath)
	{

		$ret = $this->removePrefix($efssFullPath, $this->userFolder->getPath());
		error_log("efssFullPathToRelativePath: Interpreting $efssFullPath as $ret");
		return $ret;
	}

	/**
	 * @param array $nodeInfo
	 *
	 * Returns the data of a CS3 provider.ResourceInfo object https://github.com/cs3org/cs3apis/blob/a86e5cb/cs3/storage/provider/v1beta1/resources.proto#L35-L93
	 * @return array
	 *
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	private function lock(\OCP\Files\Node $node)
	{
		$node->lock(ILockingProvider::LOCK_SHARED);
		$this->lockedNode = $node;
	}

	/**
	 * Make sure that the passed date is valid ISO 8601
	 * So YYYY-MM-DD
	 * If not throw an exception
	 *
	 * @param string $expireDate
	 *
	 * @throws \Exception
	 * @return \DateTime
	 */
	private function parseDate(string $expireDate): \DateTime
	{
		try {
			$date = new \DateTime($expireDate);
		} catch (\Exception $e) {
			throw new \Exception('Invalid date. Format must be YYYY-MM-DD');
		}

		$date->setTime(0, 0, 0);

		return $date;
	}

	private function checkRevadAuth()
	{
		error_log("checkRevadAuth");
		$authHeader = $this->request->getHeader('X-Reva-Secret');

		if ($authHeader != $this->config->getRevaSharedSecret()) {
			throw new \OCP\Files\NotPermittedException('Please set an http request header "X-Reva-Secret: <your_shared_secret>"!');
		}
	}

	private function getChecksum(\OCP\Files\Node $node, $checksumType = 4): string
	{
		$checksumTypes = array(
			1 => "UNSET:",
			2 => "ADLER32:",
			3 => "MD5:",
			4 => "SHA1:",
		);

		// checksum is in db table oc_filecache.
		// folders do not have checksum
		$checksums = explode(' ', $node->getFileInfo()->getChecksum());

		foreach ($checksums as $checksum) {

			// Note that the use of !== false is deliberate (neither != false nor === true will return the desired result); 
			// strpos() returns either the offset at which the needle string begins in the haystack string, or the boolean 
			// false if the needle isn't found. Since 0 is a valid offset and 0 is "falsey", we can't use simpler constructs
			//  like !strpos($a, 'are').
			if (strpos($checksum, $checksumTypes[$checksumType]) !== false) {
				return substr($checksum, strlen($checksumTypes[$checksumType]));
			}
		}

		return '';
	}

	private function nodeToCS3ResourceInfo(\OCP\Files\Node $node, $token = ''): array
	{
		$isDirectory = ($node->getType() === \OCP\Files\FileInfo::TYPE_FOLDER);
		$efssPath = substr($node->getPath(), strlen($this->userFolder->getPath()) + 1);
		$revaPath = $this->efssPathToRevaPath($efssPath);

		$payload = [
			"type" => ($isDirectory ? 2 : 1),
			"id" => [
				"opaque_id" => "fileid-" . $revaPath,
			],
			"checksum" => [
				// checksum algorithms:
				// 1 UNSET
				// 2 ADLER32
				// 3 MD5
				// 4 SHA1

				// note: folders do not have checksum, their type should be unset.
				"type" => $isDirectory ? 1 : 4,
				"sum" => $this->getChecksum($node, $isDirectory ? 1 : 4),
			],
			"etag" => $node->getEtag(),
			"mime_type" => ($isDirectory ? "folder" : $node->getMimetype()),
			"mtime" => [
				"seconds" => $node->getMTime(),
			],
			"path" => $revaPath,
			"permissions" => $node->getPermissions(),
			"size" => $node->getSize(),
			"owner" => [
				"opaque_id" => $this->userId,
				"idp" => $this->getDomainFromURL($this->config->getIopUrl()),
			]
		];

		error_log("nodeToCS3ResourceInfo " . var_export($payload, true));

		return $payload;
	}

	# For ListReceivedShares, GetReceivedShare and UpdateReceivedShare we need to include "state:2"
	private function shareInfoToCs3Share(IShare $share, $token = ''): array
	{
		$shareeParts = explode("@", $share->getSharedWith());
		if (count($shareeParts) == 1) {
			error_log("warning, could not find sharee user@host from '" . $share->getSharedWith() . "'");
			$shareeParts = ["unknown", "unknown"];
		}

		$ownerParts = [$share->getShareOwner(), $this->getDomainFromURL($this->config->getIopUrl())];

		$stime = 0; // $share->getShareTime()->getTimeStamp();

		try {
			$filePath = $share->getNode()->getPath();
			$opaqueId = "fileid-" . $filePath;
		} catch (\OCP\Files\NotFoundException $e) {
			$opaqueId = "unknown";
		}

		// produces JSON that maps to
		// https://github.com/cs3org/reva/blob/v1.18.0/pkg/ocm/share/manager/nextcloud/nextcloud.go#L77
		// and
		// https://github.com/cs3org/go-cs3apis/blob/d297419/cs3/sharing/ocm/v1beta1/resources.pb.go#L100
		$payload = [
			"id" => [
				// https://github.com/cs3org/go-cs3apis/blob/d297419/cs3/sharing/ocm/v1beta1/resources.pb.go#L423
				"opaque_id" => $share->getId()
			],
			"resource_id" => [

				"opaque_id"  => $opaqueId,
			],
			"permissions" => $share->getNode()->getPermissions(),
			// https://github.com/cs3org/go-cs3apis/blob/d29741980082ecd0f70fe10bd2e98cf75764e858/cs3/storage/provider/v1beta1/resources.pb.go#L897
			"grantee" => [
				"type" => 1, // https://github.com/cs3org/go-cs3apis/blob/d29741980082ecd0f70fe10bd2e98cf75764e858/cs3/storage/provider/v1beta1/resources.pb.go#L135
				"id" => [
					"opaque_id" => $shareeParts[0],
					"idp" => $shareeParts[1]
				],
			],
			"owner" => [
				"id" => [
					"opaque_id" => $ownerParts[0],
					"idp" => $ownerParts[1]
				],
			],
			"creator" => [
				"id" => [
					"opaque_id" => $ownerParts[0],
					"idp" => $ownerParts[1]
				],
			],
			"ctime" => [
				"seconds" => $stime
			],
			"mtime" => [
				"seconds" => $stime
			],
			"token" => $token
		];

		error_log("shareInfoToCs3Share " . var_export($payload, true));

		return $payload;
	}

	# correspondes the permissions we got from Reva to Nextcloud
	private function getPermissionsCode(array $permissions): int
	{
		$permissionsCode = 0;
		if (!empty($permissions["get_path"]) || !empty($permissions["get_quota"]) || !empty($permissions["initiate_file_download"]) || !empty($permissions["initiate_file_upload"]) || !empty($permissions["stat"])) {
			$permissionsCode += \OCP\Constants::PERMISSION_READ;
		}
		if (!empty($permissions["create_container"]) || !empty($permissions["move"]) || !empty($permissions["add_grant"]) || !empty($permissions["restore_file_version"]) || !empty($permissions["restore_recycle_item"])) {
			$permissionsCode += \OCP\Constants::PERMISSION_CREATE;
		}
		if (!empty($permissions["move"]) || !empty($permissions["delete"]) || !empty($permissions["remove_grant"])) {
			$permissionsCode += \OCP\Constants::PERMISSION_DELETE;
		}
		if (!empty($permissions["list_grants"]) || !empty($permissions["list_file_versions"]) || !empty($permissions["list_recycle"])) {
			$permissionsCode += \OCP\Constants::PERMISSION_SHARE;
		}
		if (!empty($permissions["update_grant"])) {
			$permissionsCode += \OCP\Constants::PERMISSION_UPDATE;
		}
		return $permissionsCode;
	}

	/**
	 * @param int
	 *
	 * @return int
	 * @throws NotFoundException
	 */
	private function getStorageUrl($userId)
	{
		$storageUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute("sciencemesh.storage.handleHead", ["userId" => $userId, "path" => "foo"]));
		$storageUrl = preg_replace('/foo$/', '', $storageUrl);
		return $storageUrl;
	}

	/* Reva handlers */

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function AddGrant($userId)
	{
		error_log("AddGrant");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));
		// FIXME: Expected a param with a grant to add here;

		return new JSONResponse("Not implemented", Http::STATUS_NOT_IMPLEMENTED);
	}

	private function formatUser($user)
	{
		return [
			"id" => [
				"idp" => $this->getDomainFromURL($this->config->getIopUrl()),
				"opaque_id" => $user->getUID(),
			],
			"display_name" => $user->getDisplayName(),
			"username" => $user->getUID(),
			"email" => $user->getEmailAddress(),
			"type" => 1,
		];
	}

	private function formatFederatedUser($username, $remote)
	{
		return [
			"id" => [
				"idp" => $remote,
				"opaque_id" => $username,
			],
			"display_name" => $username,   // FIXME: this comes in the OCM share payload
			"username" => $username,
			"email" => "unknown@unknown",  // FIXME: this comes in the OCM share payload
			"type" => 6,
		];
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function Authenticate($userId)
	{
		error_log("Authenticate");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			$share = $this->shareProvider->getSentShareByToken($userId);
			if ($share) {
				$sharedWith = explode("@", $share->getSharedWith());
				$result = [
					"user" => $this->formatFederatedUser($sharedWith[0], $sharedWith[1]),
					"scopes" => [],
				];
				return new JSONResponse($result, Http::STATUS_OK);
			} else {
				return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
			}
		}

		$userId = $this->request->getParam("clientID");
		$password = $this->request->getParam("clientSecret");

		// Try e.g.:
		// curl -v -H 'Content-Type:application/json' -d'{"clientID":"einstein",clientSecret":"relativity"}' http://einstein:relativity@localhost/index.php/apps/sciencemesh/~einstein/api/auth/Authenticate

		// Ref https://github.com/cs3org/reva/issues/2356
		if ($password == $this->config->getRevaLoopbackSecret()) {
			$user = $this->userManager->get($userId);
		} else {
			$user = $this->userManager->checkPassword($userId, $password);
		}
		if ($user) {
			$result = [
				"user" => $this->formatUser($user),
				"scopes" => [
					"user" => [
						"resource" => [
							"decoder" => "json",
							"value" => "eyJyZXNvdXJjZV9pZCI6eyJzdG9yYWdlX2lkIjoic3RvcmFnZS1pZCIsIm9wYXF1ZV9pZCI6Im9wYXF1ZS1pZCJ9LCJwYXRoIjoic29tZS9maWxlL3BhdGgudHh0In0=",
						],
						"role" => 1,
					],
				],
			];
			return new JSONResponse($result, Http::STATUS_OK);
		}

		return new JSONResponse("Username / password not recognized", 401);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function CreateDir($userId)
	{
		error_log("CreateDir");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$urlDecodedPath = urldecode($this->request->getParam("path"));
		$path = $this->revaPathToEfssPath($urlDecodedPath);

		try {
			$this->userFolder->newFolder($path);
		} catch (NotPermittedException $e) {
			return new JSONResponse(["error" => "Could not create directory."], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		return new JSONResponse("OK", Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function CreateHome($userId)
	{
		error_log("CreateHome");
		if (RESTRICT_TO_SCIENCEMESH_FOLDER) {
			if ($this->userManager->userExists($userId)) {
				$this->init($userId);
			} else {
				return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
			}
			$homeExists = $this->userFolder->nodeExists("sciencemesh");
			if (!$homeExists) {
				try {
					$this->userFolder->newFolder("sciencemesh"); // Create the Sciencemesh directory for storage if it doesn't exist.
				} catch (NotPermittedException $e) {
					return new JSONResponse(["error" => "Create home failed. Resource Path not foun"], Http::STATUS_INTERNAL_SERVER_ERROR);
				}
				return new JSONResponse("CREATED", Http::STATUS_CREATED);
			}
		}
		return new JSONResponse("OK", Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function CreateReference($userId)
	{
		error_log("CreateReference");

		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}
		$path = $this->revaPathToEfssPath($this->request->getParam("path"));
		return new JSONResponse("Not implemented", Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function CreateStorageSpace($userId)
	{
		error_log("CreateStorageSpace");
		return new JSONResponse([
			"status" => [
				"code" => 1,
				"trace" => "00000000000000000000000000000000"
			],
			"storage_space" => [
				"opaque" => [
					"map" => [
						"bar" => [
							"value" => "c2FtYQ=="
						],
						"foo" => [
							"value" => "c2FtYQ=="
						]
					]
				],
				"id" => [
					"opaque_id" => "some-opaque-storage-space-id"
				],
				"owner" => [
					"id" => [
						"idp" => "some-idp",
						"opaque_id" => "some-opaque-user-id",
						"type" => 1
					]
				],
				"root" => [
					"storage_id" => "some-storage-id",
					"opaque_id" => "some-opaque-root-id"
				],
				"name" => "My Storage Space",
				"quota" => [
					"quota_max_bytes" => 456,
					"quota_max_files" => 123
				],
				"space_type" => "home",
				"mtime" => [
					"seconds" => 1234567890
				]
			]
		], Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 * @throws FileNotFoundException
	 */
	public function Delete($userId)
	{
		error_log("Delete");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));

		try {
			$node = $this->userFolder->get($path);
			$node->delete($path);
			return new JSONResponse("OK", Http::STATUS_OK);
		} catch (FileNotFoundException $e) {
			return new JSONResponse(["error" => "Failed to delete."], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function EmptyRecycle($userId)
	{
		// DIFFERENT FUNCTION IN NC/OC
		error_log("EmptyRecycle");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$user = $this->userManager->get($userId);
		$trashItems = $this->trashManager->listTrashRoot($user);

		$result = []; // Where is this used?
		foreach ($trashItems as $node) {
			#getOriginalLocation : returns string
			if (preg_match("/^sciencemesh/", $node->getOriginalLocation())) {
				$this->trashManager->removeItem($node);
			}
		}
		return new JSONResponse("OK", Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function GetMD($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$ref = $this->request->getParam("ref");
		error_log("GetMD " . var_export($ref, true));

		if (isset($ref["path"])) {
			// e.g. GetMD {"ref": {"path": "/home/asdf"}, "mdKeys": null}
			$revaPath = $ref["path"];
		} else if (isset($ref["resource_id"]) && isset($ref["resource_id"]["opaque_id"]) && str_starts_with($ref["resource_id"]["opaque_id"], "fileid-")) {
			// e.g. GetMD {"ref": {"resource_id": {"storage_id": "00000000-0000-0000-0000-000000000000", "opaque_id": "fileid-/asdf"}}, "mdKeys":null}
			$revaPath = $this->revaPathFromOpaqueId($ref["resource_id"]["opaque_id"]);
		} else {
			throw new \Exception("ref not understood!");
		}

		// this path is url coded, we need to decode it
		// for example this converts "we%20have%20space" to "we have space"
		$revaPathDecoded = urldecode($revaPath);

		$path = $this->revaPathToEfssPath($revaPathDecoded);
		error_log("Looking for EFSS path '$path' in user folder; reva path '$revaPathDecoded' ");

		// apparently nodeExists requires relative path to the user folder:
		// see https://github.com/owncloud/core/blob/b7bcbdd9edabf7d639b4bb42c4fb87862ddf4a80/lib/private/Files/Node/Folder.php#L45-L55;
		// another string manipulation is necessary to extract relative path from full path.
		$relativePath = $this->efssFullPathToRelativePath($path);

		$success = $this->userFolder->nodeExists($relativePath);
		if ($success) {
			error_log("File found");
			$node = $this->userFolder->get($relativePath);
			$resourceInfo = $this->nodeToCS3ResourceInfo($node);
			return new JSONResponse($resourceInfo, Http::STATUS_OK);
		}

		error_log("File not found");
		return new JSONResponse(["error" => "File not found"], 404);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function GetPathByID($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}
		// in progress
		$path = "subdir/";
		$storageId = $this->request->getParam("storage_id");
		$opaqueId = $this->request->getParam("opaque_id");

		return new DataResponse($path, Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function InitiateUpload($userId)
	{
		$ref = $this->request->getParam("ref");
		$path = $this->revaPathToEfssPath((isset($ref["path"]) ? $ref["path"] : ""));

		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}
		$response = [
			"simple" => $path
		];

		return new JSONResponse($response, Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function ListFolder($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$ref = $this->request->getParam("ref");

		// this path is url coded, we need to decode it
		// for example this converts "we%20have%20space" to "we have space"
		$pathDecoded = urldecode((isset($ref["path"]) ? $ref["path"] : ""));
		$path = $this->revaPathToEfssPath($pathDecoded);
		$success = $this->userFolder->nodeExists($path);
		error_log("ListFolder: $path");

		if (!$success) {
			error_log("ListFolder: path not found");
			return new JSONResponse(["error" => "Folder not found"], 404);
		}
		error_log("ListFolder: path found");

		$node = $this->userFolder->get($path);
		$nodes = $node->getDirectoryListing();
		$resourceInfos = array_map(function (\OCP\Files\Node $node) {
			return $this->nodeToCS3ResourceInfo($node);
		}, $nodes);
		return new JSONResponse($resourceInfos, Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function ListGrants($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));

		return new JSONResponse("Not implemented", Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function ListRecycle($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$user = $this->userManager->get($userId);
		$trashItems = $this->trashManager->listTrashRoot($user);
		$result = [];

		foreach ($trashItems as $node) {
			if (preg_match("/^sciencemesh/", $node->getOriginalLocation())) {
				$path = $this->efssPathToRevaPath($node->getOriginalLocation());
				$result = [
					[
						"opaque" => [
							"map" => null,
						],
						"key" => $path,
						"ref" => [
							"resource_id" => [
								"map" => null,
							],
							"path" => $path,
						],
						"size" => 12345,
						"deletion_time" => [
							"seconds" => 1234567890
						]
					]
				];
			}
		}

		return new JSONResponse($result, Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function ListRevisions($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));

		return new JSONResponse("Not implemented", Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function RemoveGrant($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));
		// FIXME: Expected a grant to remove here;

		return new JSONResponse("Not implemented", Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function RestoreRecycleItem($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$key = $this->request->getParam("key");
		$user = $this->userManager->get($userId);
		$trashItems = $this->trashManager->listTrashRoot($user);

		foreach ($trashItems as $node) {
			if (preg_match("/^sciencemesh/", $node->getOriginalLocation())) {
				// we are using original location as the RecycleItem's
				// unique key string, see:
				// https://github.com/cs3org/cs3apis/blob/6eab4643f5113a54f4ce4cd8cb462685d0cdd2ef/cs3/storage/provider/v1beta1/resources.proto#L318

				if ($this->revaPathToEfssPath($key) == $node->getOriginalLocation()) {
					$this->trashManager->restoreItem($node);
					return new JSONResponse("OK", Http::STATUS_OK);
				}
			}
		}

		return new JSONResponse('["error" => "Not found."]', 404);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function RestoreRevision($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));
		// FIXME: Expected a revision param here;

		return new JSONResponse("Not implemented", Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function SetArbitraryMetadata($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));
		$metadata = $this->request->getParam("metadata");

		// FIXME: this needs to be implemented for real, merging the incoming metadata with the existing ones.
		// For now we return OK to let the uploads go through, see https://github.com/sciencemesh/nc-sciencemesh/issues/43
		return new JSONResponse("I'm cheating", Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function UnsetArbitraryMetadata($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));

		// FIXME: this needs to be implemented for real
		return new JSONResponse("Not implemented", Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 */
	public function UpdateGrant($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$path = $this->revaPathToEfssPath($this->request->getParam("path"));

		// FIXME: Expected a paramater with the grant(s)
		return new JSONResponse("Not implemented", Http::STATUS_NOT_IMPLEMENTED);
	}

	/**
	 * Write a new file.
	 *
	 * @param string $path
	 * @param string $contents
	 * @param Config $config Config object
	 *
	 * @return bool false on failure, true on success
	 *
	 * @throws \OCP\Files\InvalidPathException
	 */
	private function write($path, $contents, Config $config)
	{
		try {
			if ($this->userFolder->nodeExists($path)) {
				$node = $this->userFolder->get($path);
				$node->putContent($contents);
			} else {
			}
		} catch (\Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return JSONResponse|StreamResponse
	 * @throws NotFoundException
	 */
	public function Download($userId, $path)
	{
		error_log("Download");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		error_log("Download path: $path");

		$efssPath = $this->removePrefix($path, "home/");
		error_log("Download efss path: $efssPath");

		if ($this->userFolder->nodeExists($efssPath)) {
			error_log("Download: file found");
			$node = $this->userFolder->get($efssPath);
			$view = new View();
			$nodeLocalFilePath = $view->getLocalFile($node->getPath());
			error_log("Download local file path: $nodeLocalFilePath");
			return new StreamResponse($nodeLocalFilePath);
		}

		error_log("Download: file not found");
		return new JSONResponse(["error" => "File not found"], 404);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $userId
	 * @param $path
	 * @return JSONResponse
	 */
	public function Upload($userId, $path): JSONResponse
	{
		$revaPath = "/$path";
		error_log("RevaController Upload! user: $userId , reva path: $revaPath");

		try {
			if ($this->userManager->userExists($userId)) {
				$this->init($userId);
			} else {
				return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
			}

			$contents = file_get_contents('php://input');
			$efssPath = $this->revaPathToEfssPath($revaPath);

			error_log("Uploading! reva path: $revaPath");
			error_log("Uploading! efss path $efssPath");

			if ($this->userFolder->nodeExists($efssPath)) {
				$node = $this->userFolder->get($efssPath);
				$view = new View();
				$view->file_put_contents($node->getPath(), $contents);
				return new JSONResponse("OK", Http::STATUS_OK);
			} else {
				$dirname = dirname($efssPath);
				$filename = basename($efssPath);

				if (!$this->userFolder->nodeExists($dirname)) {
					$this->userFolder->newFolder($dirname);
				}

				$node = $this->userFolder->get($dirname);
				$node->newFile($filename);

				$node = $this->userFolder->get($efssPath);
				$view = new View();
				$view->file_put_contents($node->getPath(), $contents);

				return new JSONResponse("CREATED", Http::STATUS_CREATED);
			}
		} catch (\Exception $e) {
			return new JSONResponse(["error" => "Upload failed"], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @NoSameSiteCookieRequired
	 *
	 * Get user list.
	 */
	public function GetUser($dummy)
	{
		$this->init(false);

		$userToCheck = $this->request->getParam('opaque_id');

		if ($this->userManager->userExists($userToCheck)) {
			$user = $this->userManager->get($userToCheck);
			$response = $this->formatUser($user);
			return new JSONResponse($response, Http::STATUS_OK);
		}

		return new JSONResponse(
			['message' => 'User does not exist'],
			Http::STATUS_NOT_FOUND
		);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @NoSameSiteCookieRequired
	 *
	 * Get user by claim.
	 */
	public function GetUserByClaim($dummy)
	{
		$this->init(false);

		$userToCheck = $this->request->getParam('value');

		if ($this->request->getParam('claim') == 'username') {
			error_log("GetUserByClaim, claim = 'username', value = $userToCheck");
		} else {
			return new JSONResponse('Please set the claim to username', Http::STATUS_BAD_REQUEST);
		}

		if ($this->userManager->userExists($userToCheck)) {
			$user = $this->userManager->get($userToCheck);
			$response = $this->formatUser($user);
			return new JSONResponse($response, Http::STATUS_OK);
		}

		return new JSONResponse(
			['message' => 'User does not exist'],
			Http::STATUS_NOT_FOUND
		);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 *
	 * @throws NotFoundException
	 * @throws OCSNotFoundException
	 * Create a new share in fn with the given access control list.
	 */

	public function addSentShare($userId)
	{
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$params = $this->request->getParams();
		error_log("addSentShare " . var_export($params, true));

		$owner = $params["owner"]["opaqueId"]; // . "@" . $params["owner"]["idp"];
		$name = $params["name"]; // "fileid-/other/q/f gr"
		$resourceOpaqueId = $params["resourceId"]["opaqueId"]; // "fileid-/other/q/f gr"
		$revaPath = $this->revaPathFromOpaqueId($resourceOpaqueId); // "/other/q/f gr"
		$efssPath = $this->revaPathToEfssPath($revaPath);

		$revaPermissions = null;

		foreach ($params['accessMethods'] as $accessMethod) {
			if (isset($accessMethod['webdavOptions'])) {
				$revaPermissions = $accessMethod['webdavOptions']['permissions'];
				break;
			}
		}

		if (!isset($revaPermissions)) {
			throw new \Exception('reva permissions not found');
		}

		$granteeType = $params["grantee"]["type"]; // "GRANTEE_TYPE_USER"
		$granteeHost = $params["grantee"]["userId"]["idp"]; // "revanc2.docker"
		$granteeUser = $params["grantee"]["userId"]["opaqueId"]; // "marie"

		if ($revaPermissions === null) {
			$revaPermissions = [
				"initiate_file_download" => true
			];
		}
		$efssPermissions = $this->getPermissionsCode($revaPermissions);
		$shareWith = $granteeUser . "@" . $granteeHost;
		$sharedSecret = $params["token"];

		try {
			$node = $this->userFolder->get($efssPath);
		} catch (NotFoundException $e) {
			return new JSONResponse(["error" => "Share failed. Resource Path not found"], Http::STATUS_BAD_REQUEST);
		}

		error_log("calling newShare");
		$share = $this->shareManager->newShare();
		$share->setNode($node);

		try {
			$this->lock($share->getNode());
		} catch (LockedException $e) {
			throw new OCSNotFoundException($this->l->t('Could not create share'));
		}

		$share->setShareType(IShare::TYPE_SCIENCEMESH);
		$share->setSharedBy($userId);
		$share->setSharedWith($shareWith);
		$share->setShareOwner($owner);
		$share->setPermissions($efssPermissions);
		$share->setToken($sharedSecret);
		$share = $this->shareProvider->createInternal($share);

		return new DataResponse($share->getId(), Http::STATUS_CREATED);
	}

	/**
	 * add a received share
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 * @return Http\DataResponse|JSONResponse
	 */
	public function addReceivedShare($userId)
	{
		$params = $this->request->getParams();
		error_log("addReceivedShare " . var_export($params, true));
		foreach ($params['protocols'] as $protocol) {
			if (isset($protocol['webdavOptions'])) {
				$sharedSecret = $protocol['webdavOptions']['sharedSecret'];
				// make sure you have webdav_endpoint = "https://nc1.docker/" under 
				// [grpc.services.ocmshareprovider] in the sending Reva's config
				$uri = $protocol['webdavOptions']['uri']; // e.g. https://nc1.docker/remote.php/dav/ocm/vaKE36Wf1lJWCvpDcRQUScraVP5quhzA
				$remote = implode('/', array_slice(explode('/', $uri), 0, 3)); // e.g. https://nc1.docker
				break;
			}
		}
		if (!isset($sharedSecret)) {
			throw new \Exception('sharedSecret not found');
		}

		$shareData = [
			"remote" => $remote, //https://nc1.docker
			"remote_id" =>  $params["remoteShareId"], // the id of the share in the oc_share table of the remote.
			"share_token" => $sharedSecret, // 'tDPRTrLI4hE3C5T'
			"password" => "",
			"name" => rtrim($params["name"], "/"), // '/grfe'
			"owner" => $params["owner"]["opaqueId"], // 'einstein'
			"user" => $userId // 'marie'
		];

		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$scienceMeshData = [
			"is_external" => true,
		];

		$id = $this->shareProvider->addScienceMeshShare($scienceMeshData, $shareData);
		return new JSONResponse($id, 201);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 *
	 *
	 * Remove Share from share table
	 */
	public function Unshare($userId)
	{
		error_log("Unshare");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$opaqueId = $this->request->getParam("Spec")["Id"]["opaque_id"];
		$name = $this->getNameByOpaqueId($opaqueId);

		if ($this->shareProvider->deleteSentShareByName($userId, $name)) {
			return new JSONResponse("Deleted Sent Share", Http::STATUS_OK);
		} else {
			if ($this->shareProvider->deleteReceivedShareByOpaqueId($userId, $opaqueId)) {
				return new JSONResponse("Deleted Received Share", Http::STATUS_OK);
			} else {
				return new JSONResponse("Could not find share", Http::STATUS_BAD_REQUEST);
			}
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 *
	 */
	public function UpdateSentShare($userId)
	{
		error_log("UpdateSentShare");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}
		$opaqueId = $this->request->getParam("ref")["Spec"]["Id"]["opaque_id"];
		$permissions = $this->request->getParam("p")["permissions"];
		$permissionsCode = $this->getPermissionsCode($permissions);
		$name = $this->getNameByOpaqueId($opaqueId);
		if (!($share = $this->shareProvider->getSentShareByName($userId, $name))) {
			return new JSONResponse(["error" => "UpdateSentShare failed"], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
		$share->setPermissions($permissionsCode);
		$shareUpdated = $this->shareProvider->update($share);
		$response = $this->shareInfoToCs3Share($shareUpdated);
		return new JSONResponse($response, Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 *
	 * UpdateReceivedShare updates the received share with share state.
	 */
	public function UpdateReceivedShare($userId)
	{
		error_log("UpdateReceivedShare");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$response = [];
		$resourceId = $this->request->getParam("received_share")["share"]["resource_id"];
		$permissions = $this->request->getParam("received_share")["share"]["permissions"];
		$permissionsCode = $this->getPermissionsCode($permissions);

		try {
			$share = $this->shareProvider->getReceivedShareByToken($resourceId);
			$share->setPermissions($permissionsCode);
			$shareUpdate = $this->shareProvider->UpdateReceivedShare($share);
			$response = $this->shareInfoToCs3Share($shareUpdate, $resourceId);
			$response["state"] = 2;
			return new JSONResponse($response, Http::STATUS_OK);
		} catch (\Exception $e) {
			return new JSONResponse(["error" => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 *
	 * ListSentShares returns the shares created by the user. If md is provided is not nil,
	 * it returns only shares attached to the given resource.
	 */
	public function ListSentShares($userId)
	{
		error_log("ListSentShares");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$responses = [];
		$shares = $this->shareProvider->getSentShares($userId);

		if ($shares) {
			foreach ($shares as $share) {
				array_push($responses, $this->shareInfoToCs3Share($share));
			}
		}
		return new JSONResponse($responses, Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 * ListReceivedShares returns the list of shares the user has access.
	 */
	public function ListReceivedShares($userId)
	{
		error_log("ListReceivedShares");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$responses = [];
		$shares = $this->shareProvider->getReceivedShares($userId);

		if ($shares) {
			foreach ($shares as $share) {
				$response = $this->shareInfoToCs3Share($share);
				array_push($responses, [
					"share" => $response,
					"state" => 2
				]);
			}
		}

		return new JSONResponse($responses, Http::STATUS_OK);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 *
	 * GetReceivedShare returns the information for a received share the user has access.
	 */
	public function GetReceivedShare($userId)
	{
		error_log("GetReceivedShare");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$opaqueId = $this->request->getParam("Spec")["Id"]["opaque_id"];
		$name = $this->getNameByOpaqueId($opaqueId);

		try {
			$share = $this->shareProvider->getReceivedShareByToken($opaqueId);
			$response = $this->shareInfoToCs3Share($share, $opaqueId);
			$response["state"] = 2;
			return new JSONResponse($response, Http::STATUS_OK);
		} catch (\Exception $e) {
			return new JSONResponse(["error" => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 *
	 * GetSentShare gets the information for a share by the given ref.
	 */
	public function GetSentShare($userId)
	{
		error_log("GetSentShare");
		if ($this->userManager->userExists($userId)) {
			$this->init($userId);
		} else {
			return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
		}

		$opaqueId = $this->request->getParam("Spec")["Id"]["opaque_id"];
		$name = $this->getNameByOpaqueId($opaqueId);
		$share = $this->shareProvider->getSentShareByName($userId, $name);

		if ($share) {
			$response = $this->shareInfoToCs3Share($share);
			return new JSONResponse($response, Http::STATUS_OK);
		}

		return new JSONResponse(["error" => "GetSentShare failed"], Http::STATUS_NOT_FOUND);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @return Http\DataResponse|JSONResponse
	 *
	 * GetSentShareByToken gets the information for a share by the given token.
	 */
	public function GetSentShareByToken($userId)
	{
		error_log("GetSentShareByToken: user is -> $userId");

		// See: https://github.com/cs3org/reva/pull/4115#discussion_r1308371946 
		if ($userId !== "nobody") {
			if ($this->userManager->userExists($userId)) {
				$this->init($userId);
			} else {
				return new JSONResponse("User not found", Http::STATUS_FORBIDDEN);
			}
		}

		$token = $this->request->getParam("Spec")["Token"];
		error_log("GetSentShareByToken: " . var_export($this->request->getParam("Spec"), true));

		$share = $this->shareProvider->getSentShareByToken($token);

		if ($share) {
			$response = $this->shareInfoToCs3Share($share, $token);
			return new JSONResponse($response, Http::STATUS_OK);
		}

		return new JSONResponse(["error" => "GetSentShare failed"], Http::STATUS_BAD_REQUEST);
	}
}
