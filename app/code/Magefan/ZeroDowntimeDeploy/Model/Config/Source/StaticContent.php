<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ZeroDowntimeDeploy\Model\Config\Source;

/**
 * Class Static Content
 */
class StaticContent
{
    /**]
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        $options = [];

        $options = array_merge_recursive($options, [
            ['label' => __('Deploy Only Enabled Themes'), 'value' => 0],
            ['label' => __('Deploy All Themes'), 'value' => 1],
        ]);
        return $options;
    }
}
