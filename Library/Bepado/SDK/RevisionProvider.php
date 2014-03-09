<?php
/**
 * This file is part of the Bepado SDK Component.
 *
 * @version 1.1.141
 */

namespace Bepado\SDK;

/**
 * Abstract base class for revision providers
 *
 * @version 1.1.141
 */
abstract class RevisionProvider
{
    /**
     * Get next revision
     *
     * @return string
     */
    abstract public function next();
}
