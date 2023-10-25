<?php
namespace OCA\LoginPageButton\AppInfo;

use OCP\AppFramework\App;
use OCP\IConfig;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\LoginPageButton\LoginPageOption;

class Application extends App implements IBootstrap {
    private $appName = 'login_page_button';
    public function __construct()
    {
        parent::__construct($this->appName);
    }
    public function register(IRegistrationContext $context): void
    {
        $context->registerAlternativeLogin(LoginPageOption::class);
    }
    public function boot(IBootContext $context): void {
    }
}
