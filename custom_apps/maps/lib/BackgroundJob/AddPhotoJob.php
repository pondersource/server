<?php

/**
 * Nextcloud - maps
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2019
 */

namespace OCA\Maps\BackgroundJob;

use \OCP\BackgroundJob\QueuedJob;
use \OCP\BackgroundJob\IJobList;
use \OCP\AppFramework\Utility\ITimeFactory;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUserManager;
use OCP\IConfig;
use OCP\Files\IRootFolder;

use OCA\Maps\Service\PhotofilesService;


class AddPhotoJob extends QueuedJob {

	/** @var PhotofilesService */
	private PhotofilesService $photofilesService;

	/** @var IRootFolder */
	private IRootFolder $root;

	/** @var ICacheFactory */
	private ICacheFactory $cacheFactory;

	/** @var ICache */
	private ICache $backgroundJobCache;

    /**
     * UserInstallScanJob constructor.
     *
     * A QueuedJob to scan user storage for photos and tracks
     *
	 * @param ITimeFactory $timeFactory
	 * @param PhotofilesService $photofilesService
     */
    public function __construct(ITimeFactory $timeFactory,
                                IRootFolder $root,
                                PhotofilesService $photofilesService,
								ICacheFactory $cacheFactory) {
        parent::__construct($timeFactory);
        $this->photofilesService = $photofilesService;
        $this->root = $root;
		$this->cacheFactory = $cacheFactory;
		$this->backgroundJobCache = $this->cacheFactory->createDistributed('maps:background-jobs');
    }

    public function run($arguments) {
        $userFolder = $this->root->getUserFolder($arguments['userId']);
        $files = $userFolder->getById($arguments['photoId']);
        if (empty($files)) {
        	return;
        }
        $file = array_shift($files);
        $this->photofilesService->addPhotoNow($file, $arguments['userId']);

		$counter = $this->backgroundJobCache->get('recentlyAdded:'.$arguments['userId']) ?? 0;
		$this->backgroundJobCache->set('recentlyAdded:'.$arguments['userId'], (int) $counter + 1, 60 * 60 * 3);
    }
}
