<?php
namespace WebVision\WvTranslation\Domain\Repository;

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

use TYPO3\CMS\Backend\Tree\Pagetree;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Repository to handle all localization related persistence.
 *
 * @author Daniel Siepmann <d.siepmann@web-vision.de>
 */
class LocalizationRepository
{
    protected $pageRepository;

    /**
     * @param PageRepository $pageRepository
     *
     * @return void
     */
    public function injectPageRepository(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
        $this->pageRepository->init(true);
    }

    /**
     * Initialize the current page, from page tree, and persist to settings.
     *
     * @param int $pageUid
     *
     * @return PagetreeNode
     */
    public function findPageByUid($pageUid)
    {
        $pageNode = GeneralUtility::makeInstance(Pagetree\PagetreeNode::class);
        $page = [
            'row' => [
                'uid' => 0,
                'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            ],
            'depthData' => '',
            'HTML' => $pageNode->getSpriteIconCode(),
        ];

        if ($pageUid !== 0) {
            $pageNode = Pagetree\Commands::getNode($pageUid);
            $tree = GeneralUtility::makeInstance(PageTreeView::class);
            $page = [
                'row' => $tree->getRecord($pageUid),
                'depthData' => '',
                'HTML' => $pageNode->getSpriteIconCode(),
            ];
        }

        $this->addLanguageOverlayToPage($page);

        return $page;
    }

    /**
     * Will find all pages, that are sub pages of the provided page.
     *
     * @param array $parentPage Parent node containing childs.
     * @param int $depth Number of levels to go down.
     *
     * @return array
     */
    public function findPagesByParentPage(array $parentPage, $depth = 5)
    {
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->getTree($parentPage['row']['uid'], $depth);
        $pages = $tree->tree;

        foreach ($pages as &$page) {
            $this->addLanguageOverlayToPage($page);
        }

        return $pages;
    }

    /**
     * Will find all active system languages.
     *
     * @return array
     */
    public function findAllSystemLanguages()
    {
        $table = 'sys_language';
        $languages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*',
            $table,
            '1=1 ' . $this->getAdditionalWhereClause($table)
        );

        // Only use languages, where current user has access to.
        $languages = array_filter($languages, function (array $language) {
            return $GLOBALS['BE_USER']->checkLanguageAccess($language['uid']);
        });

        return $languages;
    }

    /**
     * Will add the language overlay information to the given page.
     *
     * The information will be available under record property and 'languageOverlay'.
     *
     * @param array $page
     *
     * @return void
     */
    protected function addLanguageOverlayToPage(array &$page)
    {
        $table = 'pages_language_overlay';
        $overlay = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*',
            $table,
            'pid = ' . (int) $page['row']['uid'] . ' ' . $this->getAdditionalWhereClause($table)
        );

        $overlay = array_filter($overlay, function (array $overlay) {
            return $GLOBALS['BE_USER']->checkLanguageAccess($overlay['sys_language_uid']);
        });

        $page['row']['languageOverlay'] = $overlay;
    }

    /**
     * Wrapper for system call.
     *
     * Single place to define which enable fields should be ignored.
     *
     * @param string $table Used to respect TCA.
     *
     * @return string
     */
    protected function getAdditionalWhereClause($table)
    {
        return $this->pageRepository->enableFields(
            $table,
            true,
            ['starttime', 'endtime']
        );
    }
}
