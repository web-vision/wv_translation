<?php

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

call_user_func(
    function ($extKey) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'WebVision.WvTranslation',
            'web',
            'translation',
            '',
            array(
                'Pages' => 'index, translatePages',
            ),
            array(
                'access' => 'user, group',
                'workspaces' => 'online, custom',
                'icon' => 'EXT:' . $extKey . '/Resources/Public/Images/Icons/Module.svg',
                'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf'
            )
        );
    },
    'wv_translation'
);
