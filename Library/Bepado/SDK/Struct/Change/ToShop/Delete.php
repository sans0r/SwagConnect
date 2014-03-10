<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK\Struct\Change\ToShop;

use Bepado\SDK\Struct\Change;

/**
 * Delete change struct
 *
 * @version 1.1.142
 * @api
 */
class Delete extends Change
{
    /**
     * Shop id
     *
     * @var string
     */
    public $shopId;
}
