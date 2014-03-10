<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.142
 */

namespace Bepado\SDK;

/**
 * Base class for product hasher implementations
 *
 * @version 1.1.142
 */
abstract class ProductHasher
{
    /**
     * Get hash for product
     *
     * @return string
     */
    abstract public function hash(Struct\Product $product);
}
