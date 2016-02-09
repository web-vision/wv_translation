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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * TODO: Refactor, fix query's to respect enable fields like deleted.
 * Move most code to separate classes.
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

    protected function initializeAction()
    {
        $this->initializeCurrentPage();
    }

    public function indexAction()
    {
        $this->view->assignMultiple([
            'pages' => $this->getPages($this->settings['currentPage']),
            'languages' => $this->getLanguages(),
        ]);
    }

    /**
     * Initialize the current page, from page tree, and persist to settings.
     *
     * @return void
     */
    protected function initializeCurrentPage()
    {
        $currentPage = GeneralUtility::_GET('id');
        $pageRecord = [
            'uid' => 0,
            'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
        ];
        if ($currentPage === null) {
            $currentPage = 0;
        } else {
            $pageRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                '*',
                'pages',
                'uid = ' . (int) $currentPage
            );
        }

        $page = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class);
        $page->setId($currentPage);
        $page->setRecord($pageRecord);
        $this->addLanguageOverlay($page);
        $this->settings['currentPage'] = $page;
    }

    protected function getPages($parentPage)
    {
        $tree = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\DataProvider::class);
        $pages = $tree->getNodes($parentPage);
        foreach ($pages as $page) {
            $this->initializePageAndSubpages($page);
        }

        return $pages;
    }

    /**
     * TODO: Refactor to first gather all page ids, then fetch all information, and add all information?
     * We have to choose: One query, or one iteration?
     */
    protected function initializePageAndSubpages($page)
    {
        // Fetch additional information
        $this->addLanguageOverlay($page);
        if ($page->getChildNodes()) {
            foreach ($page->getChildNodes() as $subPage) {
                $this->initializePageAndSubpages($subPage);
            }
        }
    }

    protected function addLanguageOverlay($page)
    {
        $additionalInformation = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*',
            'pages_language_overlay',
            'pid = ' . (int) $page->getId()
        );
        $record = $page->getRecord();
        $record['languageOverlay'] = $additionalInformation;
        $page->setRecord($record);
    }

    protected function getLanguages()
    {
        return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*',
            'sys_language',
            '1=1'
        );
    }
}
