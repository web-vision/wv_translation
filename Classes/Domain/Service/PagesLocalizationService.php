<?php
namespace WebVision\WvTranslation\Domain\Service;

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

/**
 * Provide features for localization, related to pages.
 *
 * E.g. allow localization of pages.
 *
 * @author Daniel Siepmann <d.siepmann@web-vision.de>
 */
class PagesLocalizationService
{
    /**
     * Localize the given pages to the given languages.
     *
     * @param array $pageUids UIDs of pages to localize.
     * @param array $languageUids Languages to which the pages should be localized.
     *
     * @return void
     */
    public function localizePages(array $pageUids, array $languageUids)
    {
        $dataToProcess = $this->buildLocalizationDataArray($pageUids, $languageUids);
        $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');

        // Hide localized versions, as the title is auto generated and they
        // need some more work.
        $GLOBALS['TCA']['pages_language_overlay']['columns']['hidden']['config']['default'] = 1;

        // Process each language, as TCE doesn't allow multiple at once.
        foreach ($dataToProcess['languages'] as $dataForLanguage) {
            $tce->start([], $dataForLanguage);
            $tce->process_cmdmap();
        }
    }

    /**
     * Build an array, containing sub array, ready to use for tce as cmd map.
     *
     * @param array $pageUids UIDs of pages to localize.
     * @param array $languageUids Languages to which the pages should be localized.
     *
     * @return array
     */
    protected function buildLocalizationDataArray(array $pageUids, array $languageUids)
    {
        // As TCE doesn't allow localization to multiple languages at once, we
        // have to build an array for each language.
        $dataToProcess = [
            'languages' => [],
        ];

        foreach($pageUids as $pageUid) {
            foreach ($languageUids as $languageUid) {
                if (!is_array($dataToProcess['languages'][$languageUid])) {
                    $dataToProcess['languages'][$languageUid] = [
                        'pages' => [],
                    ];
                }

                $dataToProcess['languages'][$languageUid]['pages'][$pageUid] = [
                    'localize' => $languageUid,
                ];
            }
        }

        return $dataToProcess;
    }
}
