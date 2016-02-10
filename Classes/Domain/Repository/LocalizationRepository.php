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

use TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode;
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
        $pageRecord = [
            'uid' => 0,
            'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
        ];
        if ($pageUid != 0) {
            $pageRecord = $this->pageRepository->getPage((int) $pageUid);
        }

        $page = GeneralUtility::makeInstance(PagetreeNode::class);
        $page->setId($pageUid);
        $page->setRecord($pageRecord);

        $this->addLanguageOverlayToPage($page);

        return $page;
    }

    /**
     * Will find all pages, that are sub pages of the provided page.
     *
     * @param PagetreeNode $parentPage
     *
     * @return TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection
     */
    public function findPagesByParentPage(PagetreeNode $parentPage)
    {
        $tree = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\DataProvider::class);
        $pages = $tree->getNodes($parentPage);
        foreach ($pages as $page) {
            $this->addLanguageOverlayToPageAndSubpages($page);
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

        return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*',
            $table,
            '1=1 ' . $this->getAdditionalWhereClause($table)
        );
    }

    /**
     * Will add the language overlay information to the given page and there
     * subpages.
     *
     * @param PagetreeNode $page
     *
     * @return void
     */
    protected function addLanguageOverlayToPageAndSubpages(PagetreeNode $page)
    {
        // TODO: Refactor to first gather all page ids, then fetch all
        // information, and add all information?  We have to choose: One query,
        // or one iteration?
        $this->addLanguageOverlayToPage($page);
        if ($page->getChildNodes()) {
            foreach ($page->getChildNodes() as $subPage) {
                $this->addLanguageOverlayToPageAndSubpages($subPage);
            }
        }
    }

    /**
     * Will add the language overlay information to the given page.
     *
     * The information will be available under record property and 'languageOverlay'.
     *
     * @param PagetreeNode $page
     *
     * @return void
     */
    protected function addLanguageOverlayToPage(PagetreeNode $page)
    {
        $table = 'pages_language_overlay';
        $overlay = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*',
            $table,
            'pid = ' . (int) $page->getId() . ' ' . $this->getAdditionalWhereClause($table)
        );

        $record = $page->getRecord();
        $record['languageOverlay'] = $overlay;
        $page->setRecord($record);
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
