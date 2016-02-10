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

use TYPO3\CMS\Backend\Utility\BackendUtility;
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
     * Array key containing the page uid of the current page.
     *
     * @var string
     */
    const ARRAY_KEY_CURRENT_PAGE = 'currentPageUid';

    /**
     * Use backend template container as view.
     *
     * This way we already have common design.
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @inject
     * @var WebVision\WvTranslation\Domain\Repository\LocalizationRepository
     */
    protected $repository;

    /**
     * @inject
     * @var WebVision\WvTranslation\Domain\Service\PagesLocalizationService
     */
    protected $pageService;

    /**
     * Initialize all actions.
     *
     * Used to fetch current page uid from page tree.
     *
     * @return void
     */
    protected function initializeAction()
    {
        $this->settings[static::ARRAY_KEY_CURRENT_PAGE] = 0;
        $currentPageUid = GeneralUtility::_GET('id');
        if ($currentPageUid !== null) {
            $this->settings[static::ARRAY_KEY_CURRENT_PAGE] = (int) $currentPageUid;
        }
    }

    /**
     * Add further information to view.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view
     *
     * @return void
     */
    protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view)
    {
        // Show path to current page in doc header.
        $pageRecord = BackendUtility::readPageAccess(
            $this->settings[static::ARRAY_KEY_CURRENT_PAGE],
            $GLOBALS['BE_USER']->getPagePermsClause(1)
        );
        if ($view->getModuleTemplate() && $pageRecord !== false) {
            $view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($pageRecord);
        }
    }

    /**
     * Deliver an index of pages, with there current localization stage.
     *
     * The list is recursive and root element can be set with get var "id".
     * Where 0 is default.
     *
     * @return void
     */
    public function indexAction()
    {
        $currentPage = $this->repository->findPageByUid($this->settings['currentPageUid']);
        $this->view->assignMultiple([
            'currentPage' => $currentPage,
            'pages' => $this->repository->findPagesByParentPage($currentPage['row']['uid']),
            'languages' => $this->repository->findAllSystemLanguages(),
        ]);
    }

    /**
     * Translate the given pages.
     *
     * @param array $pagesToTranslate Uids of pages to localize.
     * @param array $languages Uids of languages the pages should be localized to.
     *
     * @return void
     */
    public function translatePagesAction(array $pagesToTranslate, array $languages)
    {
        $this->pageService->localizePages($pagesToTranslate, $languages);

        $this->redirect('index');
    }
}
