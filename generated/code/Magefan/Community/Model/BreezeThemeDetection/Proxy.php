<?php
namespace Magefan\Community\Model\BreezeThemeDetection;

/**
 * Proxy class for @see \Magefan\Community\Model\BreezeThemeDetection
 */
class Proxy extends \Magefan\Community\Model\BreezeThemeDetection implements \Magento\Framework\ObjectManager\NoninterceptableInterface
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Proxied instance name
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Proxied instance
     *
     * @var \Magefan\Community\Model\BreezeThemeDetection
     */
    protected $_subject = null;

    /**
     * Instance shareability flag
     *
     * @var bool
     */
    protected $_isShared = null;

    /**
     * Proxy constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     * @param bool $shared
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magefan\\Community\\Model\\BreezeThemeDetection', $shared = true)
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
        $this->_isShared = $shared;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['_subject', '_isShared', '_instanceName'];
    }

    /**
     * Retrieve ObjectManager from global scope
     */
    public function __wakeup()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Clone proxied instance
     */
    public function __clone()
    {
        $this->_subject = clone $this->_getSubject();
    }

    /**
     * Get proxied instance
     *
     * @return \Magefan\Community\Model\BreezeThemeDetection
     */
    protected function _getSubject()
    {
        if (!$this->_subject) {
            $this->_subject = true === $this->_isShared
                ? $this->_objectManager->get($this->_instanceName)
                : $this->_objectManager->create($this->_instanceName);
        }
        return $this->_subject;
    }

    /**
     * {@inheritdoc}
     */
    public function getThemeModuleName() : string
    {
        return $this->_getSubject()->getThemeModuleName();
    }

    /**
     * {@inheritdoc}
     */
    public function getThemeName() : string
    {
        return $this->_getSubject()->getThemeName();
    }

    /**
     * {@inheritdoc}
     */
    public function execute($storeId = null) : bool
    {
        return $this->_getSubject()->execute($storeId);
    }
}
