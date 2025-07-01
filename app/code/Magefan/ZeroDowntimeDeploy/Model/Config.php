<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;

class Config
{
    /**
     * Extension enabled config path
     */
    const XML_PATH_EXTENSION_ENABLED           = 'mfzerodwt/general/enabled';
    const XML_PATH_THEME_DEPLOY_MODE           = 'mfzerodwt/general/static_content_deploy';
    const XML_PATH_ENABLE_CACHES               = 'mfzerodwt/general/enable_caches';
    const XML_PATH_STORE_LOCALE                = 'general/locale/code';
    const XML_PATH_MAGENTO_COMMAND             = 'mfzerodwt/general/magento_cli_command';
    const XML_PATH_DISABLE_MAGENTO_COMMAND     = 'mfzerodwt/general/disable_default_commands';
    const XML_PATH_COUNT_OF_JOBS               = 'mfzerodwt/general/jobs_count_for_parallel_processing';

    /**
     * Composer config section
     */
    const XML_PATH_COMPOSER_PULL_ENABLED       = 'mfzerodwt/composer/pull_from_composer';
    const XML_PATH_COMPOSER_COMMANDS           = 'mfzerodwt/composer/composer_pull_command';

    /**
     * Git config section
     */
    const XML_PATH_GIT_PULL_ENABLED            = 'mfzerodwt/git/pull_from_git';
    const XML_PATH_GIT_BRANCH                  = 'mfzerodwt/git/pull_from_git_branch';
    const XML_PATH_WEBHOOKS_ENABLED            = 'mfzerodwt/git/webhooks_enabled';
    const XML_PATH_WEBHOOKS_SECRET             = 'mfzerodwt/git/secret';

    /**
     * Path to scheduled flag file
     */
    const SCHEDULED_FLAG_FILE                  = 'var/mfzdd-scheduled.flag';

    /**
     * Path to running flag file
     */
    const RUNNING_FLAG_FILE                    = 'var/mfzdd-running.flag';

    /**
     *  Path from/to temporary additional files
     */
    const GET_COPY_TO_TEMPORARY_INSTANCE = 'mfzerodwt/copy_files/copy_to_temp';
    const GET_COPY_FROM_TEMPORARY_INSTANCE = 'mfzerodwt/copy_files/copy_from_temp';

    const GIT_BRANCH_REGEX = '/[^A-Za-z0-9\.\-\_\/]+/';

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ThemeProviderInterface
     */
    protected $themeProvider;


    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ThemeProviderInterface $themeProvider
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->themeProvider = $themeProvider;
    }

    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isMagepackEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig('dev/js/enable_magepack_js_bundling', $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_EXTENSION_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isPullFromGit($storeId = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_GIT_PULL_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     * @return false|string[]
     */
    public function getGitBranch($storeId = null)
    {
        return $this->getConfig(self::XML_PATH_GIT_BRANCH, $storeId);
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getMagentoCommand($storeId = null)
    {
        return (string)$this->getConfig(self::XML_PATH_MAGENTO_COMMAND, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isPullFromComposer($storeId = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_COMPOSER_PULL_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isEnableAllCaches($storeId = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_ENABLE_CACHES, $storeId);
    }

    /**
     * @param string $commandType
     * @return string
     */
    public function getComposerCommand(string $commandType = 'install'): string
    {
        if (!$commandType) {
            $commandType = 'install';
        }

        $command = $this->getConfig(self::XML_PATH_COMPOSER_COMMANDS) ?: '';
        if (false !== strpos($command, '&')
            || false !== strpos($command, ';')
            || false === strpos($command, 'composer')
        ) {
            $command = 'composer';
        }

        if (false === strpos($command, '--no-interaction')) {
            $command .= ' --no-interaction ';
        }

        return $command  . ' ' . $commandType;
    }

    /**
     * @param null $storeId
     * @return int
     */
    public function getThemeDeployMode($storeId = null)
    {
        return (int)$this->getConfig(self::XML_PATH_THEME_DEPLOY_MODE, $storeId);
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getStoreLocaleByStoreId($storeId)
    {
        $locale =  $this->getConfig(self::XML_PATH_STORE_LOCALE, $storeId);
        return ($locale && is_string($locale) && strlen($locale) > 2) ? $locale : 'en_US';
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getThemeDataByStoreId($storeId)
    {

        $themeId = $this->scopeConfig->getValue(
            \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $theme = $this->themeProvider->getThemeById($themeId);

        return $theme->getData();
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isWebhooksEnabled($storeId = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_WEBHOOKS_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getWebhooksSecret($storeId = null)
    {
        $value = $this->getConfig(self::XML_PATH_WEBHOOKS_SECRET, $storeId);
        return $value ? $this->getEncryptor()->decrypt($value) : $value;
    }

    /**
     * @return \Magento\Framework\Encryption\EncryptorInterface|mixed
     */
    private function getEncryptor()
    {
        if (null === $this->encryptor) {
            $this->encryptor = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Encryption\EncryptorInterface::class);
        }
        return $this->encryptor;
    }

    /**
     * @param null $storeId
     * @return int
     */
    public function getCountOfJobsForParallelProcessing($storeId = null)
    {
        return (int)$this->getConfig(self::XML_PATH_COUNT_OF_JOBS, $storeId);
    }

    /**
     * @param null $storeId
     * @return array
     */
    public function getCopyToTemporaryInstance($storeId = null): array
    {
        return $this->stringLinesToArray(
            $this->getConfig(self::GET_COPY_TO_TEMPORARY_INSTANCE, $storeId) ?: ''
        );
    }

    /**
     * @param null $storeId
     * @return string
     */
    public function getCopyFromTemporaryInstance($storeId = null)
    {
        return $this->stringLinesToArray(
            $this->getConfig(self::GET_COPY_FROM_TEMPORARY_INSTANCE, $storeId) ?: ''
        );
    }

    /**
     * @param string $value
     * @return array
     */
    private function stringLinesToArray(string $value): array
    {
        $value = str_replace(["\r", "\r"], [PHP_EOL, PHP_EOL], $value);

        $items =  explode(PHP_EOL, $value);
        $result = [];
        foreach ($items as $item) {
            if ($item = trim($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isDisableMagentoCommand($storeId = null)
    {
        return (bool)$this->getConfig(self::XML_PATH_DISABLE_MAGENTO_COMMAND, $storeId);
    }
}
