<?php
namespace WebVision\WvTranslation\ViewHelpers\Format;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Will make the given string fully uppercase.
 *
 * Will work on wrapped content / inline. So no further arguments are
 * available.
 *
 * @author Daniel Siepmann <d.siepmann@web-vision.de>
 */
class UppercaseViewHelper extends AbstractViewHelper
{
    /**
     * Render method to process
     *
     * @return string
     */
    public function render()
    {
        return GeneralUtility::strtoupper(
            $this->renderChildren()
        );
    }
}
