<?php
namespace OCA\LoginPageButton;

use \OCP\Authentication\IAlternativeLogin;
use \OCP\IConfig;

class LoginPageOption implements IAlternativeLogin {

    private $config;
    private $appName;

    public function __construct(IConfig $config, $appName){
        $this->config = $config;
        $this->appName = $appName;
    }

    public function getLabel(): string
    {
    	return $this->config->getSystemValue('login_page_button_text', 'SUNET');
    }
    
    public function getLink(): string
    {
    	return $this->config->getSystemValue('login_page_button_link', 'https://sunet.se');
    }
    
    public function getClass(): string
    {
    	return 'login-page-button';
    }
    
    public function load(): void
    {
    }
}
