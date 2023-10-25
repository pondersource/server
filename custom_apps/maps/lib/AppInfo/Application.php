<?php
/**
 * ownCloud - maps
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Sander Brand <brantje@gmail.com>, Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @copyright Sander Brand 2014, Vinzenz Rosenkranz 2016, 2017
 */

namespace OCA\Maps\AppInfo;


use OCA\Files\Event\LoadSidebar;
use OCA\Maps\Listener\LoadSidebarListener;
use \OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Http\EmptyFeaturePolicy;
use \OCP\IServerContainer;
use OCA\Maps\Hooks\FileHooks;
use OCA\Maps\Service\PhotofilesService;
use OCA\Maps\Service\TracksService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\Maps\Listener\LoadAdditionalScriptsListener;
use OCA\Maps\Listener\CardCreatedListener;
use OCA\Maps\Listener\CardUpdatedListener;
use OCA\Maps\Listener\CardDeletedListener;
use OCP\Security\FeaturePolicy\AddFeaturePolicyEvent;


class Application extends App implements IBootstrap {
	public const APP_ID = 'maps';

    public function __construct (array $urlParams=array()) {
        parent::__construct('maps', $urlParams);
    }

	public function register(IRegistrationContext $context): void {
		// ... registration logic goes here ...

		// Register the composer autoloader for packages shipped by this app, if applicable
		include_once __DIR__ . '/../../vendor/autoload.php';
		$context->registerEventListener(
			LoadAdditionalScriptsEvent::class,
			LoadAdditionalScriptsListener::class
		);

		$context->registerEventListener(
			LoadSidebar::class,
			LoadSidebarListener::class
		);

		$context->registerEventListener(
			CardCreatedEvent::class,
			CardCreatedListener::class
		);
		$context->registerEventListener(
			CardUpdatedEvent::class,
			CardUpdatedListener::class
		);
		$context->registerEventListener(
			CardDeletedEvent::class,
			CardDeletedListener::class
		);
	}

	public function boot(IBootContext $context): void {
		// ... boot logic goes here ...
		$context->getAppContainer()->registerService('FileHooks', function($c) {
			return new FileHooks(
				$c->query(IServerContainer::class)->getRootFolder(),
				\OC::$server->query(PhotofilesService::class),
				\OC::$server->query(TracksService::class),
				$c->query(IServerContainer::class)->getLogger(),
				$c->query('AppName'),
				$c->query(IServerContainer::class)->getLockingProvider()
			);
		});

		$context->getAppContainer()->query('FileHooks')->register();

		$this->registerFeaturePolicy();
	}

	private function registerFeaturePolicy() {
		$dispatcher = $this->getContainer()->getServer()->getEventDispatcher();

		$dispatcher->addListener(AddFeaturePolicyEvent::class, function (AddFeaturePolicyEvent $e) {
			$fp = new EmptyFeaturePolicy();
			$fp->addAllowedGeoLocationDomain('\'self\'');
			$e->addPolicy($fp);
		});
	}

}
