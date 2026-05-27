<?php
/**
 * Processing Request
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage TinCan Module
 * @author     Uncanny Owl
 * @since      1.0.0
 */

namespace UCTINCAN;

use TinCan;
/**
 *
 */
class ExtendedLanguageMap extends TinCan\LanguageMap
{
    public static function get_target($definition) {
        
        if (!empty($definition->getName())) {
            $nameMap = $definition->getName();
            if (!empty($nameMap->asVersion())) {
                $nameVersion = $nameMap->asVersion();
                return urldecode(array_pop($nameVersion));
            } 
        }
        
        if (empty($target_name) && !empty($definition->getDescription())) {
            $descriptionMap = $definition->getDescription();
            if (!empty($descriptionMap->asVersion())) {
                $descVersion = $descriptionMap->asVersion();
                return urldecode(array_pop($descVersion));
            }
        }

        return '';
    }
}
