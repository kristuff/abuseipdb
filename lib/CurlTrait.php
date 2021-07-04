<?php declare(strict_types=1);

/**
 *     _    _                    ___ ____  ____  ____
 *    / \  | |__  _   _ ___  ___|_ _|  _ \|  _ \| __ )
 *   / _ \ | '_ \| | | / __|/ _ \| || |_) | | | |  _ \
 *  / ___ \| |_) | |_| \__ \  __/| ||  __/| |_| | |_) |
 * /_/   \_\_.__/ \__,_|___/\___|___|_|   |____/|____/
 *
 * This file is part of Kristuff\AbuseIPDB.
 *
 * (c) Kristuff <kristuff@kristuff.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @version    0.9.12
 * @copyright  2020-2021 Kristuff
 */

namespace Kristuff\AbuseIPDB;

/**
 * cURL helper functions
 */
trait CurlTrait
{
    /**
     * helper to configure cURL option
     *  
     * @access protected
     * @param resource  $ch
     * @param int       $option
     * @param mixed     $value
     *   
     * @return void
     * @throws \RuntimeException
     */
    protected function setCurlOption($ch, int $option, $value): void
    {
        if(!curl_setopt($ch,$option,$value)){
            throw new \RuntimeException('curl_setopt failed! '.curl_error($ch));
        }
    }
}
