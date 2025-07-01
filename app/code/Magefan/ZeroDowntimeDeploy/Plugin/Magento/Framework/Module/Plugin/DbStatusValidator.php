<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Plugin\Magento\Framework\Module\Plugin;

use Magefan\ZeroDowntimeDeploy\Model\Config;
use Magento\Framework\App\FrontController;
use Magento\Framework\App\RequestInterface;

class DbStatusValidator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * DbStatusValidator constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param \Magento\Framework\Module\Plugin\DbStatusValidator $object
     * @param \Closure $proceed
     * @param $subject
     * @param $request
     * @return mixed|void
     */
    public function aroundBeforeDispatch(
        \Magento\Framework\Module\Plugin\DbStatusValidator $object,
        \Closure $proceed,
        FrontController $subject,
        RequestInterface $request
    ) {
        if ($this->config->isEnabled()) {
            return;
        }

        return $proceed($subject, $request);
    }
}
