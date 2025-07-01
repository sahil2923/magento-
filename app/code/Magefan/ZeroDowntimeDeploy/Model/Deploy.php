<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Model;

use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Setup\Exception;
use Magento\Framework\App\DeploymentConfig\Reader as ConfigReader;
use Magento\Framework\Config\File\ConfigFilePool;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Setup\Console\Command\InstallCommand;
use Magento\Framework\View\Design\Theme\ListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magefan\ZeroDowntimeDeploy\Model\Config;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\User\Model\ResourceModel\User\Collection;
use Magento\Framework\Shell;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Symfony\Component\Console\Question\Question;

/**
 * Class Zero Deploy
 */
class Deploy
{
    const INSTANCE = 'var/mfzerodwt/instance';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var DirectoryList
     */
    protected $directory;

    /**
     * @var ConfigReader
     */
    protected $configReader;

    /**
     * @var ListInterface
     */
    protected $listInterface;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string[]
     */
    protected $copyToInstance = ['app', 'bin', 'dev', 'lib', 'setup', 'vendor', 'phpserver', 'update', '.git', 'composer.json', 'composer.lock', 'auth.json', '.gitignore', 'patches', 'magepack.config.js', 'var/assets', 'var/mfzdd-running.flag'];

    /**
     * @var string[]
     */
    protected $createToInstance = ['generated', 'pub', 'pub/static', 'var', 'var/cache', 'var/log'];

    /**
     * @var
     */
    protected $cliOutput;

    /**
     * @var
     */
    protected $cliInput;

    /**
     * @var
     */
    protected $magentoRootDirectory;

    /**
     * @var
     */
    protected $instanceDirectory;

    /**
     * @var
     */
    protected $currentDirectory;

    /**
     * @var Collection
     */
    protected $userCollection;

    /**
     * @var Shell|null
     */
    protected $shell;

    /**
     * @var File
     */
    protected $fileDriver;

    /**
     * @var FileIo
     */
    protected $fileIo;

    /**
     * @var int
     */
    protected $errorCounter = 0;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var string
     */
    protected $binMagentoCommand;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var bool
     */
    protected $setupAll;

    /**
     * @var bool
     */
    protected $setupDi;

    /**
     * @var bool
     */
    protected $setupStaticContent;

    /**
     * @var mixed
     */
    protected $themeResolver;

    /**
     * @var mixed
     */
    protected $themeProvider;

    /**
     * @var mixed
     */
    protected $questionHelper;

    /**
     * @var bool
     */
    protected $manualChanges;

    /**
     * @var bool 
     */
    protected $needToRunComposerCommandAfterMoveFilesToLiveInstance = true;

    /**
     * Deploy constructor.
     * @param \Magefan\ZeroDowntimeDeploy\Model\Config $config
     * @param DirectoryList $directory
     * @param ConfigReader $configReader
     * @param ListInterface $listInterface
     * @param StoreManagerInterface $storeManager
     * @param Collection $userCollection
     * @param Shell $shell
     * @param File $fileDriver
     * @param FileIo $fileIo
     * @param RequestInterface $request
     * @param EventManager|null $eventManager
     * @param StoreUserAgentThemeResolver|null $themeResolver
     * @param ThemeProviderInterface|null $themeProvider
     */
    public function __construct(
        Config $config,
        DirectoryList $directory,
        ConfigReader $configReader,
        ListInterface $listInterface,
        StoreManagerInterface $storeManager,
        Collection $userCollection,
        Shell $shell,
        File $fileDriver,
        FileIo $fileIo,
        RequestInterface $request,
        EventManager $eventManager = null,
        $themeResolver = null,
        $themeProvider = null
    ) {
        $this->config = $config;
        $this->directory = $directory;
        $this->configReader = $configReader;
        $this->listInterface = $listInterface;
        $this->storeManager = $storeManager;
        $this->userCollection = $userCollection;
        $this->shell = $shell;
        $this->fileDriver = $fileDriver;
        $this->fileIo = $fileIo;
        $this->request = $request;
        $this->eventManager = $eventManager ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Event\ManagerInterface::class);

        if (class_exists(\Magento\Theme\Model\Theme\StoreUserAgentThemeResolver::class)) {
            $this->themeResolver = $themeResolver ?: \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Theme\Model\Theme\StoreUserAgentThemeResolver::class);
        }
        $this->themeProvider = $themeProvider ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\View\Design\Theme\ThemeProviderInterface::class);
    }

    /**
     * @param $input
     */
    public function setCliInput($input)
    {
        $this->cliInput = $input;
        return $this;
    }

    /**
     * @param $output
     */
    public function setCliOutput($output)
    {
        $this->cliOutput = $output;
        return $this;
    }

    public function setQuestionHelper($questionHelper)
    {
        $this->questionHelper = $questionHelper;
        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit", '-1');

        if ($this->config->isEnabled()) {
            if ($this->fileIo->fileExists(Config::RUNNING_FLAG_FILE)) {
                $output->writeln('<comment>'
                    . __('Deploy is executed by another process. Please wait. To unlock deploying remove "var/mfzdd-running.flag" file.')
                    .'</comment>');
                return;
            }

            $this->fileIo->write(Config::RUNNING_FLAG_FILE, '');

            $this->cliInput = $input;
            $this->cliOutput = $output;

            $this->dispatchEvent('mf_zdd_before_zdd_start');

            $setupStaticContent = $this->cliInput->getOption('static');
            $setupDi = $this->cliInput->getOption('di_compile');

            $setupAll = !$setupStaticContent && !$setupDi;

            $this->initDirectories();

            $this->removeOldFilesInMagentoRoot(true, true, true);
            $this->createInstanceFolder();

            $this->chdir($this->instanceDirectory);

            if (!$this->cliInput->getOption('manual_changes')) {
                $this->executeGitCommands();
            }

            $this->executeComposerCommands();

            if ($this->questionHelper && $this->cliInput->getOption('manual_changes')) {
                $this->manualChanges = true;
                $question = new Question('<question>Please make changes in ' . self::INSTANCE. '/[app, lib, vendor, composer.json, composer.lock, auth.json] folders or files and press enter to continue.</question> ');
                $this->questionHelper->ask($input, $output, $question);
            }

            $enableNewModules = $this->enableNewModules();
            if ($enableNewModules) {
                $setupAll = true;
            }

            $this->setupAll = $setupAll;
            $this->setupDi = $setupDi;
            $this->setupStaticContent = $setupStaticContent;

            $this->executeSetupCommands($setupAll, $setupDi, $setupStaticContent);

            $this->chdir($this->magentoRootDirectory);

            $this->copyNewFilesToMagentoRoot($setupAll, $setupDi, $setupStaticContent);

            if (!$this->cliInput->getOption('manual_changes')) {
                $this->executeGitCommands();
            }

            if ($this->needToRunComposerCommandAfterMoveFilesToLiveInstance || $this->cliInput->getOption('manual_changes')) {
                $this->executeComposerCommands();
            } else {
                $this->cliOutput->writeln(PHP_EOL . '<info>' . __('Nothing to install, update or remove via the composer.') . '</info>');
            }

            $this->replaceFilesInMagentoRoot($setupAll, $setupDi, $setupStaticContent);

            /* Speed 2 seconds before working with caches */
            sleep(2);

            $this->chdir($this->magentoRootDirectory);
            if ($this->config->isEnableAllCaches()) {
                $this->execBinMagento('cache:enable');
            }

            $this->execteFinalCacheClean();

            /**
             * should be located here to prevent the 404 error, when old static cache was removed
             * pub/static/_cache/merged/cb08edd81351cfcba3eae4ee5caa03db.css
             * pub/static/_cache/merged/cd3157bcf1c49540f4d56c8087c2fe6f.js
             *
             * but old Page Cache is not cleaned yet, so that we get 404 error
             */
            $this->remove($this->magentoRootDirectory . '/pub/static/_cache');

            /*
            if ($setupAll) {
                //$this->cliOutput->writeln(__('Disable maintanance mode'));
                $this->execBinMagento('maintenance:disable', true, false);
            }
            */

            $this->cliOutput->writeln(PHP_EOL . __('Deleting old files...'));

            $this->removeOldFilesInMagentoRoot($setupAll, $setupDi, $setupStaticContent);
            $this->deleteInstanceFolder();
            $this->cliOutput->writeln('<info>'.__('Update done!').'</info>');

            $this->dispatchEvent('mf_zdd_after_zdd_done');

            $this->fileIo->rm(Config::RUNNING_FLAG_FILE);
        }
    }

    /**
     *  Final cache flush
     */
    protected function execteFinalCacheClean()
    {
        $this->cliOutput->writeln(PHP_EOL . __('Clear cache...'));
        $this->dispatchEvent('mf_zdd_before_final_cache_flush');
        $this->execBinMagento('cache:flush');
        $this->dispatchEvent('mf_zdd_after_final_cache_flush');
    }

    /**
     *  Run di:compile
     */
    protected function execteDiCompile()
    {
        $this->dispatchEvent('mf_zdd_before_di_compile');
        $this->execBinMagento('setup:di:compile');
        $this->dispatchEvent('mf_zdd_after_di_compile');
    }

    /**
     *  Run setup:upgrade
     */
    protected function executeDbUpgrade()
    {
        $this->dispatchEvent('mf_zdd_before_db_upgrade');
        $this->execBinMagento('setup:upgrade --keep-generated ' .
            ' --magento-init-params=MAGE_DIRS[base][path]=' . $this->instanceDirectory);

        $this->dispatchEvent('mf_zdd_after_db_upgrade');
    }

    /**
     *  Deploy theme files
     */
    protected function executeStaticContentDeploy()
    {

        $jobs = $this->config->getCountOfJobsForParallelProcessing() > 1 ? ' --jobs ' . $this->config->getCountOfJobsForParallelProcessing() . ' ' : '';

        $this->cliOutput->writeln(PHP_EOL . __('Run Static Content Deploy...'));

        $this->dispatchEvent('mf_zdd_before_static_content_deploy');

        $runOnlyEnabledThemes = !$this->config->getThemeDeployMode();

        $done = [];

        $this->execBinMagento('magefan:zero-downtime:amasty-jet-theme-generate-theme-asset', true, false);

        foreach ($this->getStoreData() as $storeData) {
            if ($runOnlyEnabledThemes) {
                if ($storeData['isActive']) {

                    $themeData = $this->config->getThemeDataByStoreId($storeData['id']);
                    if (empty($themeData['code'])) {
                        $key = $storeData['locale'];
                        if (!isset($done[$key])) {
                            $done[$key] = true;
                            $this->execBinMagento('setup:static-content:deploy ' . $storeData['locale'] . ' -s standard -f' . $jobs);
                        }
                        continue;
                    }

                    $key = $storeData['locale'] . '_' . $themeData['code'];
                    if (!isset($done[$key])) {
                        $done[$key] = true;
                        $this->execBinMagento('setup:static-content:deploy '
                            . $storeData['locale'] . ' --theme="' . $themeData['code'] . '" -s standard -f' . $jobs);
                    }

                    if ($this->themeResolver) {
                        $userAgentsThemeIds = $this->themeResolver->getThemes($this->storeManager->getStore($storeData['id']));
                        if ($userAgentsThemeIds) {
                            foreach ($userAgentsThemeIds as $themeId) {
                                $theme = $this->themeProvider->getThemeById($themeId);
                                $themeCode = trim((string)$theme->getCode());
                                if ($themeCode) {
                                    $key = $storeData['locale'] . '_' . $themeCode;
                                    if (!isset($done[$key])) {
                                        $done[$key] = true;
                                        $this->execBinMagento('setup:static-content:deploy '
                                            . $storeData['locale'] . ' --theme="' . $themeCode . '" -s standard -f' . $jobs);
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $key = $storeData['locale'];
                if (!isset($done[$key])) {
                    $done[$key] = true;
                    $this->execBinMagento('setup:static-content:deploy ' . $storeData['locale'] . ' -s standard -f' . $jobs);
                }
            }
        }

        if (true || $runOnlyEnabledThemes) {
            $adminUsers = $this->userCollection
                ->addFieldToSelect(['interface_locale', 'is_active'])
                ->addFieldToFilter('is_active', ['eq' => 1]);

            $adminLocales = [];
            foreach ($adminUsers as $user) {
                $adminLocales[$user->getData('interface_locale')] = $user->getData('interface_locale');
            }

            foreach ($this->listInterface as $theme) {
                if ($theme->getArea() == 'adminhtml') {
                    foreach ($adminLocales as $adminLocale) {
                        $this->execBinMagento('setup:static-content:deploy '
                            . $adminLocale . ' --theme="' . $theme->getCode() . '" -s standard -f' . $jobs);
                    }
                }
            }
        }

        if ($this->config->isMagepackEnabled()) {
            $this->exec('magepack bundle', false, false);
        }

        $this->dispatchEvent('mf_zdd_after_static_content_deploy');
    }

    /**
     *  Delete instance of current project
     */
    public function deleteInstanceFolder()
    {
        $this->initDirectories();
        $this->dispatchEvent('mf_zdd_before_delete_instance_folder');
        $this->remove($this->instanceDirectory);
        $this->dispatchEvent('mf_zdd_after_delete_instance_folder');
    }

    /**
     *  Create an instance of current project
     */
    public function createInstanceFolder()
    {
        $this->initDirectories();

        $this->dispatchEvent('mf_zdd_before_create_instance_folder');

        $this->cliOutput->writeln('<info>' . __('Creating temporary instance folder (%1).' . '</info>', $this->instanceDirectory));

        if ($this->fileDriver->isDirectory($this->instanceDirectory)) {
            $this->deleteInstanceFolder();
        }

        $this->mkdir(dirname($this->instanceDirectory));
        $this->mkdir($this->instanceDirectory);

        foreach ($this->createToInstance as $item) {
            $this->mkdir($this->instanceDirectory . '/' . $item);
        }

        foreach ($this->getCopyToInstance() as $item) {
            $from = $this->magentoRootDirectory . '/' . $item;
            $to = $this->instanceDirectory . '/' . $item;

            $this->copy($from, $to);
        }


        $this->chdir($this->instanceDirectory);
        $this->exec('chmod +x bin/magento', false, false);

        $instanceEnvPhp = $this->instanceDirectory . '/app/etc/env.php';

        if (is_link($instanceEnvPhp)) {
            $this->exec('mv -f ' . $instanceEnvPhp . ' ' . $instanceEnvPhp . '_tmp ');
            $pathToEnvPhpFromSymlink = $this->exec('readlink -f ' . $this->magentoRootDirectory . '/app/etc/env.php');

            //create env.php file in zdd dir for magento
            $this->copy($pathToEnvPhpFromSymlink, $instanceEnvPhp);
            $instanceEnvPhp = $pathToEnvPhpFromSymlink;
        }

        /* Disable redis, varnish on temporary instance */
        $envContent = require $instanceEnvPhp;
        $changed = false;
        if (isset($envContent['http_cache_hosts'])) {
            unset($envContent['http_cache_hosts']);
            $changed = true;
        }
        if (isset($envContent['cache'])
            && isset($envContent['cache']['frontend']['default']['backend'])
            && stripos($envContent['cache']['frontend']['default']['backend'], 'redis') !== false
        ) {
            unset($envContent['cache']);
            $changed = true;
        }
        if (isset($envContent['session']) && isset($envContent['session']['save']) && $envContent['session']['save'] != 'files') {
            $envContent['session']['save'] = 'files';
            $changed = true;
        }

        if ($changed) {
            $this->fileDriver->filePutContents($instanceEnvPhp, '<?php return ' . PHP_EOL . var_export($envContent, true) . ';');
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $this->dispatchEvent('mf_zdd_after_create_instance_folder');
    }

    /**
     * @param null $storeId
     * @return false|string[]
     */
    public function getGitCommand()
    {
        try {
            $branchName = $this->cliInput->getOption('git_branch');
        } catch (\Exception $e) {
            $branchName = '';
        }

        if (!$branchName) {
            $branchName = $this->config->getGitBranch();
        }

        if ($branchName) {
            $branchName = preg_replace(Config::GIT_BRANCH_REGEX, '', $branchName);
        }

        return 'git pull origin "' . $branchName . '" &&  git checkout "' . $branchName . '"';
    }

    /**
     *  Execute GIT commands
     */
    protected function executeGitCommands()
    {
        try {
            $pullFromGit = $this->cliInput->getOption('pull_from_git');
        } catch (\Exception $e) {
            $pullFromGit = null;
        }

        if (null === $pullFromGit) {
            $pullFromGit = $this->config->isPullFromGit();
        } else {
            $pullFromGit = (int)$pullFromGit;
        }

        if ($pullFromGit) {
            $this->cliOutput->writeln(PHP_EOL . __('Executing git pull origin master.'));

            $this->dispatchEvent('mf_zdd_before_execute_git_commands');

            if ($command = $this->getGitCommand()) {
                try {
                    $this->exec($command);
                    $this->errorCounter = 0;
                } catch (LocalizedException $e) {

                    $this->cliOutput->writeln(__('<error>'
                        . substr((string)$e, 0, strpos((string)$e, 'Stack trace:')) .'<error>'));

                    if ($this->errorCounter < 2) {
                        $this->errorCounter++;
                        $this->executeGitCommands();
                    } else {
                        $this->cliOutput->writeln(__('<info> Exit... Please try again latter! <info>'));
                    }
                }
            }

            $this->dispatchEvent('mf_zdd_after_execute_git_commands');
        }
    }

    /**
     * Execute Composer commands
     * @param $composerOptions
     */
    protected function executeComposerCommands($composerOptions = '')
    {
        try {
            $pullFromComposer = $this->cliInput->getOption('pull_from_composer');
        } catch (\Exception $e) {
            $pullFromComposer = null;
        }

        if (null === $pullFromComposer) {
            $pullFromComposer = $this->config->isPullFromComposer();
        } else {
            $pullFromComposer = (int)$pullFromComposer;
        }

        if ($pullFromComposer) {

            $this->dispatchEvent('mf_zdd_before_execute_composer_commands');

            /*$this->cliOutput->writeln(PHP_EOL . __('Executing composer install.'));*/
            if ($command = $this->config->getComposerCommand('install')) {
                $command = str_replace('{{magento-folder}}', $this->currentDirectory, $command);

                $resultMessage = (string)$this->exec($command . ' ' . $composerOptions);

                if (strpos($resultMessage, 'Nothing to install, update or remove') !== false) {
                    $this->needToRunComposerCommandAfterMoveFilesToLiveInstance = false;
                }
            }

            $this->dispatchEvent('mf_zdd_after_execute_composer_commands');
        }
    }

    /**
     * @throws \Exception
     */
    public function enableNewModules()
    {
        $this->initDirectories();

        $currentConfig = $this->configReader->load(ConfigFilePool::APP_CONFIG);

        if (!array_key_exists(ConfigOptionsListConstants::KEY_MODULES, $currentConfig)) {
            $this->cliOutput->writeln('<error>'.'Can\'t find the modules configuration in the \'app/etc/config.php\' file.'.'</error>');
            throw new \Exception('Can\'t find the modules configuration in the \'app/etc/config.php\' file.');
        }

        $currentModuleConfig = $currentConfig[ConfigOptionsListConstants::KEY_MODULES];
        //$correctModuleConfig = $this->createModulesConfig([], true);

        $disabledModules = [];
//        $this->chdir($this->instanceDirectory);
        $instanceModules = $this->execBinMagento('module:status', false, false);
        $instanceModules = explode('List of disabled modules:', $instanceModules);
        if (count($instanceModules) > 1) {
            $instanceModules = trim($instanceModules[1]);
            $disabledModules = explode(PHP_EOL, $instanceModules);
        }

        $modulesToEnabele = [];
        foreach ($disabledModules as $disabledModule) {
            /* Need to check because $disabledModule may contain binary data like this 1b5b33396d */
            if (!isset($currentModuleConfig[$disabledModule]) && $disabledModule && false !== strpos($disabledModule, '_')) {
                $modulesToEnabele[] = $disabledModule;
            }
        }

        if (count($modulesToEnabele) == 1 && $modulesToEnabele[0] == 'None') {
            unset($modulesToEnabele[0]);
        }

        if (count($modulesToEnabele)) {
            $this->execBinMagento('module:enable --clear-static-content ' . implode(' ', $modulesToEnabele));
            return true;
        }

        return false;
    }

    /**
     * @param string $command
     * @param bool $showOutput
     */
    protected function execBinMagento(string $command, bool $showOutput = true, bool $showCommand = true)
    {
        if (null === $this->binMagentoCommand) {

            if ($this->config->getMagentoCommand()) {
                $this->binMagentoCommand = $this->config->getMagentoCommand();
                if (false === strpos($this->binMagentoCommand, '{{command}}')) {
                    $this->binMagentoCommand .= ' {{command}}';
                }
            } else {
                $argv = $this->request->getServer('argv');
                $bin = (string)$this->request->getServer('_');
                if (false === strpos($bin, 'php')) {
                    $bin = '';
                } else {
                    $bin = $bin . ' ';
                }

                $this->binMagentoCommand = $bin . $argv[0];
                $this->binMagentoCommand = str_replace('bin/magento bin/magento', 'bin/magento', $this->binMagentoCommand);

                $this->binMagentoCommand .= ' {{command}}';
            }
        }

        $command = str_replace('{{command}}', $command, $this->binMagentoCommand);
        $command = str_replace('{{magento-folder}}', $this->currentDirectory, $command);

        return $this->exec($command, $showOutput, $showCommand);
    }


    /**
     * @param string $command
     * @param bool $showOutput
     */
    protected function exec(string $command, bool $showOutput = true, bool $showCommand = true)
    {
        if ($showCommand) {
            $this->cliOutput->writeln(PHP_EOL . '<info>' . $command . '</info>');
        }

        $rez = $this->shell->execute($command);

        if ($showOutput) {
            $this->cliOutput->writeln('<comment>' . $rez . '</comment>');
        }

        return $rez;
    }

    /**
     * @param $all
     * @param $request
     * @param $key
     * @return array|false|string[]
     */
    protected function readListOfModules($all, $request, $key)
    {
        $result = [];
        if (!empty($request[$key])) {
            if ($request[$key] == 'all') {
                $result = $all;
            } else {
                $result = explode(',', $request[$key]);
                foreach ($result as $module) {
                    if (!in_array($module, $all)) {
                        throw new \LogicException("Unknown module in the requested list: '{$module}'");
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param $setupAll
     * @param $setupDi
     * @param $setupStaticContent
     */
    protected function executeSetupCommands($setupAll, $setupDi, $setupStaticContent)
    {
        $this->chdir($this->instanceDirectory);

        $this->dispatchEvent('mf_zdd_before_magento_setup_commands');

        if ($setupAll || $setupDi) {
            $this->execteDiCompile();
        }

        if ($setupAll || $setupStaticContent) {
            $this->executeStaticContentDeploy();
        }

        if ($setupAll) {
            $this->chdir($this->instanceDirectory);
            $this->executeDbUpgrade();
        }

        $this->dispatchEvent('mf_zdd_after_magento_after_commands');
    }

    /**
     * @param $setupAll
     * @param $setupDi
     * @param $setupStaticContent
     * @return array|string[]
     */
    protected function getCopyFromInstanceList($setupAll, $setupDi, $setupStaticContent)
    {
        $copyFromInstance = [];

        if ($this->manualChanges) {
            $copyFromInstance = array_merge($copyFromInstance, ['app', 'lib', 'vendor', 'composer.json', 'composer.lock', 'auth.json']);
        } else {
            $copyFromInstance = array_merge($copyFromInstance, ['app/etc/config.php']);
        }

        if ($setupAll) {
            $copyFromInstance = array_merge($copyFromInstance, ['generated', 'pub/static/adminhtml', 'pub/static/frontend', 'pub/static/deployed_version.txt', 'var/view_preprocessed', 'var/assets']);
        } else {
            if ($setupDi) {
                $copyFromInstance = array_merge($copyFromInstance, ['generated']);
            }

            if ($setupStaticContent) {
                $copyFromInstance = array_merge($copyFromInstance, ['pub/static/adminhtml', 'pub/static/frontend', 'pub/static/deployed_version.txt', 'var/view_preprocessed', 'var/assets']);
            }
        }

        return array_unique(array_merge($copyFromInstance, $this->config->getCopyFromTemporaryInstance()));
    }

    /**
     *  Copy files from instance to project root directory
     */
    public function copyNewFilesToMagentoRoot($setupAll, $setupDi, $setupStaticContent)
    {
        $this->initDirectories();

        $this->cliOutput->writeln(PHP_EOL . '<info>'.__('Move processed files to Magento root directory...'). '</info>');

        $this->dispatchEvent('mf_zdd_before_copy_new_files_to_magento_root');

        $copyFromInstance = $this->getCopyFromInstanceList($setupAll, $setupDi, $setupStaticContent);

        foreach ($copyFromInstance as $item) {
            $this->move($this->instanceDirectory . '/' . $item, $this->magentoRootDirectory . '/' . $item . '-deploy');
            if ($this->manualChanges && $item == 'app') {
                $this->move($this->magentoRootDirectory . '/' . $item . '/etc/env.php', $this->magentoRootDirectory . '/' . $item . '-deploy/etc/env.php');
            }
        }

        $this->dispatchEvent('mf_zdd_after_copy_new_files_to_magento_root');
    }


    /**
     *  Copy files from instance to project root directory
     */
    public function replaceFilesInMagentoRoot($setupAll, $setupDi, $setupStaticContent)
    {
        $this->initDirectories();

        $this->dispatchEvent('mf_zdd_before_replace_files_in_magento_root');

        $copyFromInstance = $this->getCopyFromInstanceList($setupAll, $setupDi, $setupStaticContent);

        foreach ($copyFromInstance as $item) {
            $path = $this->magentoRootDirectory . '/' . $item;

            $this->move($path, $path . '-old-deploy');
            $this->move($path . '-deploy', $path);
        }

        $this->remove($this->magentoRootDirectory . \Magento\Framework\Code\GeneratedFiles::REGENERATE_FLAG);

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        $this->dispatchEvent('mf_zdd_after_replace_files_in_magento_root');
    }

    /**
     *  Copy files from instance to project root directory
     */
    public function removeOldFilesInMagentoRoot($setupAll, $setupDi, $setupStaticContent)
    {
        $this->initDirectories();

        $this->dispatchEvent('mf_zdd_before_remove_old_files_in_magento_root');

        $copyFromInstance = $this->getCopyFromInstanceList($setupAll, $setupDi, $setupStaticContent);
        foreach ($copyFromInstance as $item) {
            $path = $this->magentoRootDirectory . '/' . $item;

            if ($this->fileDriver->isDirectory($path . '-old-deploy') || $this->fileDriver->isFile($path . '-old-deploy')) {
                $this->remove($path . '-old-deploy');
            }

            if ($this->fileDriver->isDirectory($path . '-deploy') || $this->fileDriver->isFile($path . '-deploy')) {
                $this->remove($path . '-deploy');
            }
        }

        $this->dispatchEvent('mf_zdd_after_remove_old_files_in_magento_root');
    }

    /**
     * Execute cp command
     * @param $from
     * @param $to
     */
    protected function copy($from, $to)
    {
        if ($this->fileDriver->isDirectory($from) || $this->fileDriver->isFile($from)) {
            $this->exec('cp -rf ' . $from . ' ' . $to . '', false, false);
        }
    }

    /**
     * Execute mv command
     * @param $from
     * @param $to
     */
    protected function move($from, $to)
    {
        if ($this->fileDriver->isDirectory($from) || $this->fileDriver->isFile($from)) {
            $this->exec('mv -f ' . $from . ' ' . $to . '', false, false);
        }
    }

    /**
     * Execute rm command
     * @param $item
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function remove($item)
    {
        if ($this->fileDriver->isDirectory($item) || $this->fileDriver->isFile($item)) {
            $this->exec('rm -rf  ' . $item . '', false, false);
        }
    }

    /**
     * Execute mkdir command
     * @param $item
     */
    protected function mkdir($item)
    {
        if (!$this->fileDriver->isDirectory($item)) {
            $this->exec('mkdir  ' . $item . '', false, false);
        }
    }

    /**
     * @return array:
     */
    protected function getStoreData() : array
    {
        $result = [];
        foreach ($this->storeManager->getStores() as $key => $value) {
            $result[] = [
                'code' => $value['code'],
                'id' => $value->getId(),
                'isActive' => $value['is_active'],
                'locale' => $this->config->getStoreLocaleByStoreId($value->getId())
            ];
        }
        return $result;
    }

    /**
     * Dispatch event
     * @return null:
     */
    protected function dispatchEvent($name, $params = [])
    {
        $params = array_merge([
            'input' => $this->cliInput,
            'output' => $this->cliOutput,
            'setupAll' => $this->setupAll,
            'setupDi' => $this->setupDi,
            'setupStaticContent' => $this->setupStaticContent,
        ], $params);

        $this->eventManager->dispatch($name, $params);
    }

    /**
     * Init directories variables
     */
    protected function initDirectories()
    {
        if (null === $this->magentoRootDirectory) {
            $this->magentoRootDirectory = $this->directory->getRoot();
            $this->instanceDirectory = $this->magentoRootDirectory . '/' . self::INSTANCE;
            $this->currentDirectory = $this->magentoRootDirectory;
        }
    }

    /**
     * Change current directory
     */
    protected function chdir($dir)
    {
        chdir($dir);
        $this->currentDirectory = $dir;
    }

    /**
     * @return array|string[]
     */
    protected function getCopyToInstance()
    {
        return array_unique(array_merge($this->copyToInstance, $this->config->getCopyToTemporaryInstance()));
    }
}
