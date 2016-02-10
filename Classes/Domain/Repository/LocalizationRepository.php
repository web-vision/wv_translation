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
    /**
     * @var PageRepository
     */
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
     * Returns page by here uid.
     *
     * Also will handle page 0.
     *
     * @param int $pageUid
     *
     * @return array
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

        if ($pageUid > 0) {
            $tree = GeneralUtility::makeInstance(PageTreeView::class);
            $page = [
                'row' => $tree->getRecord($pageUid),
                'depthData' => '',
                'HTML' => Pagetree\Commands::getNode($pageUid)->getSpriteIconCode(),
            ];
        }

        $this->addLanguageOverlayToPage($page);

        return $page;
    }

    /**
     * Will find all pages, that are sub pages of the provided page.
     *
     * @param int $parentPageUid Uid of parent page to fetch childs from.
     * @param int $depth Number of levels to go down.
     *
     * @return array
     */
    public function findPagesByParentPage($parentPageUid, $depth = 5)
    {
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->getTree($parentPageUid, $depth);
        $pages = $tree->tree;

        // Use reference, as we will update the existing pages and can save
        // memory.
        foreach ($pages as &$page) {
            $this->addLanguageOverlayToPage($page);
        }

        return $pages;
    }

    /**
     * Will find all active system languages.
     *
     * Respects current be user.
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
        return array_filter($languages, function (array $language) {
            return $GLOBALS['BE_USER']->checkLanguageAccess($language['uid']);
        });
    }

    /**
     * Will add the language overlay information to the given page.
     *
     * The information will be available under 'row.languageOverlay'.
     * Respects current be user.
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
