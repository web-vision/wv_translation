<?php
namespace WebVision\WvTranslation\Controller;

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

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Controller to provide translation information for pages.
 *
 * @author Daniel Siepmann <d.siepmann@web-vision.de>
 */
class PagesController extends ActionController
{
    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @inject
     * @var WebVision\WvTranslation\Domain\Repository\LocalizationRepository
     */
    protected $localizationRepository;

    /**
     * Initialize all actions.
     *
     * Fetch current page uid.
     */
    protected function initializeAction()
    {
        $this->settings['currentPageUid'] = 0;
        $currentPageUid = GeneralUtility::_GET('id');
        if ($currentPageUid !== null) {
            $this->settings['currentPageUid'] = (int) $currentPageUid;
        }
    }

    /**
     * Deliver an index of pages, with there current localization stage.
     *
     * The list can is recursive and parent can be set with get var "id". 0 is
     * default.
     *
     * @return void
     */
    public function indexAction()
    {
        $currentPage = $this->localizationRepository->findPageByUid($this->settings['currentPageUid']);
        $this->view->assignMultiple([
            'currentPage' => $currentPage,
            // TODO: Check why subpages of 23 aren't shown, unless you select page 19 or a subpage.
            'pages' => $this->localizationRepository->findPagesByParentPage($currentPage),
            'languages' => $this->localizationRepository->findAllSystemLanguages(),
        ]);
    }

    /**
     * Translate the given pages.
     *
     * @param array $pagesToTranslate
     *
     * @return void
     */
    public function translatePagesAction(array $pagesToTranslate)
    {
        $dataToProcess = [
            'pages' => [],
        ];
        $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');

        // TODO: Don't localize to an existing language, perhaps provide uids
        //       via form submission, and add information as hidden fields?
        // TODO: Localize content. Make it optional?

        // Hide localized versions, as the title is auto generated and they
        // need some more work.
        $GLOBALS['TCA']['pages_language_overlay']['columns']['hidden']['config']['default'] = 1;
        foreach($this->localizationRepository->findAllSystemLanguages() as $language) {
            foreach ($pagesToTranslate as $pageUid) {
                $dataToProcess['pages'][$pageUid] = [
                    'localize' => $language['uid'],
                ];
            }
            $tce->start([], $dataToProcess);
            $tce->process_cmdmap();
        }

        $this->redirect('index');
    }
}
