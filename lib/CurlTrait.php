<?php declare(strict_types=1);

/**
 *       _                 ___ ___ ___  ___
 *  __ _| |__ _  _ ___ ___|_ _| _ \   \| _ )
 * / _` | '_ \ || (_-</ -_)| ||  _/ |) | _ \
 * \__,_|_.__/\_,_/__/\___|___|_| |___/|___/
 * 
 * This file is part of Kristuff\AbuseIPDB.
 *
 * (c) Kristuff <kristuff@kristuff.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @version    0.9.15
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
