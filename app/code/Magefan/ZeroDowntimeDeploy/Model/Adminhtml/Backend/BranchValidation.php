<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Model\Adminhtml\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use Magefan\ZeroDowntimeDeploy\Model\Config;

class BranchValidation extends Value
{
    /**
     * @return BranchValidation
     * @throws LocalizedException
     */
    public function beforeSave(): BranchValidation
    {
        $value = $this->getValue();
        if (preg_match(Config::GIT_BRANCH_REGEX, $value)) {
            throw new LocalizedException(__('Invalid branch name.'));
        }

        return parent::beforeSave();
    }
}
