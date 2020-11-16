<?php declare(strict_types=1);
/**
 * Folksonomy
 *
 * Add tags and tagging form to any resource to create uncontrolled vocabularies
 * and tag clouds.
 *
 * @copyright Daniel Berthereau, 2013-2020
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */
namespace Folksonomy;

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Folksonomy\Form\ConfigForm;
use Generic\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

    /**
     * @var array Cache of tags and taggings by resource.
     */
    protected $cache = [
        'tags' => [],
        'taggings' => [],
    ];

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);
        $this->addEntityManagerFilters();
        $this->addAclRules();
    }

    protected function postInstall(): void
    {
        $services = $this->getServiceLocator();
        $t = $services->get('MvcTranslator');

        $settings = $services->get('Omeka\Settings');

        $html = '<p>';
        $html .= sprintf($t->translate("I agree with %sterms of use%s and I accept to free my contribution under the licence %sCC&nbsp;BY-SA%s."), // @translate
            '<a rel="licence" href="#" target="_blank">', '</a>',
            '<a rel="licence" href="https://creativecommons.org/licenses/by-sa/3.0/" target="_blank">', '</a>'
        );
        $html .= '</p>';
        $settings->set('folksonomy_legal_text', $html);
    }

    /**
     * Add tag and tagging visibility filters to the entity manager.
     */
    protected function addEntityManagerFilters(): void
    {
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $services->get('Omeka\EntityManager')->getFilters()
            ->enable('tagging_visibility')
            ->setAcl($acl);
    }

    /**
     * Add ACL rules for this module.
     *
     * @todo Simplify rules (see module Comment).
     */
    protected function addAclRules(): void
    {
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $settings = $services->get('Omeka\Settings');

        $publicAllowTag = $settings->get('folksonomy_public_allow_tag', false);
        $publicEntityRights = ['read'];
        $publicAdapterRights = ['search', 'read'];
        if ($publicAllowTag) {
            $publicEntityRights[] = 'create';
            $publicAdapterRights[] = 'create';
        }

        // Similar than items or item sets from Omeka\Service\AclFactory.
        $acl
            ->allow(
                null,
                [
                    \Folksonomy\Controller\Admin\TaggingController::class,
                    \Folksonomy\Controller\Site\TaggingController::class,
                ]
            )
            ->allow(
                null,
                [\Folksonomy\Api\Adapter\TaggingAdapter::class],
                $publicAdapterRights
            )
            ->allow(
                null,
                Tagging::class,
                $publicEntityRights
            )

            ->allow(
                'researcher',
                [\Folksonomy\Controller\Admin\TaggingController::class],
                ['add', 'index', 'search', 'browse', 'show', 'show-details', 'sidebar-select']
            )
            ->allow(
                'researcher',
                [\Folksonomy\Api\Adapter\TaggingAdapter::class],
                ['create']
            )
            ->allow(
                'researcher',
                [\Folksonomy\Entity\Tagging::class],
                ['create']
            )
            ->allow(
                'researcher',
                [\Folksonomy\Entity\Tagging::class],
                ['read'],
                new OwnsEntityAssertion
            )

            ->allow(
                'author',
                [\Folksonomy\Controller\Admin\TaggingController::class],
                ['add', 'index', 'search', 'browse', 'show', 'show-details', 'sidebar-select']
            )
            ->allow(
                'author',
                [\Folksonomy\Api\Adapter\TaggingAdapter::class],
                ['create']
            )
            ->allow(
                'author',
                [\Folksonomy\Entity\Tagging::class],
                ['create']
            )
            ->allow(
                'author',
                [\Folksonomy\Entity\Tagging::class],
                ['read'],
                new OwnsEntityAssertion
            )

            ->allow(
                'reviewer',
                [\Folksonomy\Controller\Admin\TaggingController::class],
                ['add', 'edit', 'delete', 'index', 'search', 'browse', 'show', 'show-details', 'sidebar-select']
            )
            ->allow(
                'reviewer',
                [\Folksonomy\Api\Adapter\TaggingAdapter::class],
                ['create', 'update', 'delete']
            )
            ->allow(
                'reviewer',
                [\Folksonomy\Entity\Tagging::class],
                ['create', 'update', 'delete', 'view-all']
            )

            ->allow(
                'editor',
                [\Folksonomy\Controller\Admin\TaggingController::class],
                ['add', 'edit', 'delete', 'index', 'search', 'browse', 'show', 'show-details', 'sidebar-select']
            )
            ->allow(
                'editor',
                [\Folksonomy\Api\Adapter\TaggingAdapter::class],
                ['create', 'update', 'delete']
            )
            ->allow(
                'editor',
                [\Folksonomy\Entity\Tagging::class],
                ['create', 'update', 'delete', 'view-all']
            )

            // Similar than items or item sets from Omeka\Service\AclFactory.
            ->allow(
                null,
                [
                    \Folksonomy\Controller\Admin\TagController::class,
                    \Folksonomy\Controller\Site\TagController::class,
                ]
            )
            ->allow(
                null,
                [\Folksonomy\Api\Adapter\TagAdapter::class],
                $publicAdapterRights
            )
            ->allow(
                null,
                [\Folksonomy\Entity\Tag::class],
                $publicEntityRights
            )

            ->allow(
                'researcher',
                [\Folksonomy\Controller\Admin\TagController::class],
                ['add', 'index', 'search', 'browse', 'show', 'show-details', 'sidebar-select']
            )
            ->allow(
                'researcher',
                [\Folksonomy\Api\Adapter\TagAdapter::class],
                ['create']
            )
            ->allow(
                'researcher',
                [\Folksonomy\Entity\Tag::class],
                ['create']
            )
            ->allow(
                'researcher',
                [\Folksonomy\Entity\Tag::class],
                ['read'],
                new OwnsEntityAssertion
            )

            ->allow(
                'author',
                [\Folksonomy\Controller\Admin\TagController::class],
                ['add', 'index', 'search', 'browse', 'show', 'show-details', 'sidebar-select']
            )
            ->allow(
                'author',
                [\Folksonomy\Api\Adapter\TagAdapter::class],
                ['create']
            )
            ->allow(
                'author',
                [\Folksonomy\Entity\Tag::class],
                ['create']
            )
            ->allow(
                'author',
                [\Folksonomy\Entity\Tag::class],
                ['read'],
                new OwnsEntityAssertion
            )

            ->allow(
                'reviewer',
                [\Folksonomy\Controller\Admin\TagController::class],
                ['add', 'edit', 'delete', 'index', 'search', 'browse', 'show', 'show-details', 'sidebar-select']
            )
            ->allow(
                'reviewer',
                [\Folksonomy\Api\Adapter\TagAdapter::class],
                ['create', 'update', 'delete']
            )
            ->allow(
                'reviewer',
                [\Folksonomy\Entity\Tag::class],
                ['create', 'update', 'delete', 'view-all']
            )

            ->allow(
                'editor',
                [\Folksonomy\Controller\Admin\TagController::class],
                ['add', 'edit', 'delete', 'index', 'search', 'browse', 'show', 'show-details', 'sidebar-select']
            )
            ->allow(
                'editor',
                [\Folksonomy\Api\Adapter\TagAdapter::class],
                ['create', 'update', 'delete']
            )
            ->allow(
                'editor',
                [\Folksonomy\Entity\Tag::class],
                ['create', 'update', 'delete', 'view-all']
            );
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        // TODO Add a setting to limit resources (see module Comment).

        // Add the Folksonomy term definition.
        $sharedEventManager->attach(
            '*',
            'api.context',
            [$this, 'handleApiContext']
        );

        // Add the visibility filters.
        $sharedEventManager->attach(
            '*',
            'sql_filter.resource_visibility',
            [$this, 'handleSqlResourceVisibility']
        );

        $representations = [
            \Omeka\Api\Representation\ItemRepresentation::class,
            \Omeka\Api\Representation\ItemSetRepresentation::class,
            \Omeka\Api\Representation\MediaRepresentation::class,
        ];
        foreach ($representations as $representation) {
            $sharedEventManager->attach(
                $representation,
                'rep.resource.json',
                [$this, 'filterJsonLd']
            );
        }

        $adapters = [
            \Omeka\Api\Adapter\ItemAdapter::class,
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            \Omeka\Api\Adapter\MediaAdapter::class,
        ];
        foreach ($adapters as $adapter) {
            // Add the tagging and tag filters to resource search.
            $sharedEventManager->attach(
                $adapter,
                'api.search.query',
                [$this, 'searchQuery']
            );

            // Cache some resources after a search.
            $sharedEventManager->attach(
                $adapter,
                'api.search.post',
                [$this, 'cacheData']
            );
            $sharedEventManager->attach(
                $adapter,
                'api.read.post',
                [$this, 'cacheData']
            );

            // Handle hydration after hydration of resource.
            $sharedEventManager->attach(
                $adapter,
                'api.hydrate.post',
                [$this, 'handleTagging']
            );
        }

        // Add the tag field to the admin and public advanced search page.
        $controllers = [
            'Omeka\Controller\Admin\Item',
            'Omeka\Controller\Admin\ItemSet',
            'Omeka\Controller\Admin\Media',
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\ItemSet',
            'Omeka\Controller\Site\Media',
            // TODO Add user.
        ];
        foreach ($controllers as $controller) {
            $sharedEventManager->attach(
                $controller,
                'view.advanced_search',
                [$this, 'displayAdvancedSearch']
            );
        }

        $controllers = [
            'Omeka\Controller\Admin\Item',
            'Omeka\Controller\Admin\ItemSet',
            'Omeka\Controller\Admin\Media',
        ];
        foreach ($controllers as $controller) {
            // Add a tab to the resource show admin pages.
            $sharedEventManager->attach(
                $controller,
                'view.show.before',
                [$this, 'addHeadersAdmin']
            );
            $sharedEventManager->attach(
                $controller,
                'view.show.section_nav',
                [$this, 'addTab']
            );
            $sharedEventManager->attach(
                $controller,
                'view.show.after',
                [$this, 'displayListAndForm']
            );

            // Add the details to the resource browse admin pages.
            $sharedEventManager->attach(
                $controller,
                'view.browse.before',
                [$this, 'addHeadersAdmin']
            );
            $sharedEventManager->attach(
                $controller,
                'view.details',
                [$this, 'viewDetails']
            );

            // Add the tab form to the resource add and edit admin pages.
            $sharedEventManager->attach(
                $controller,
                'view.add.section_nav',
                [$this, 'addTab']
            );
            $sharedEventManager->attach(
                $controller,
                'view.edit.section_nav',
                [$this, 'addTab']
            );
            $sharedEventManager->attach(
                $controller,
                'view.add.form.after',
                [$this, 'displayForm']
            );
            $sharedEventManager->attach(
                $controller,
                'view.edit.form.after',
                [$this, 'displayForm']
            );

            // Filter the search filters for the advanced search pages.
            $sharedEventManager->attach(
                $controller,
                'view.search.filters',
                [$this, 'filterSearchFilters']
            );
        }

        $controllers = [
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\ItemSet',
            'Omeka\Controller\Site\Media',
        ];
        foreach ($controllers as $controller) {
            // Filter the search filters for the advanced search pages.
            $sharedEventManager->attach(
                $controller,
                'view.search.filters',
                [$this, 'filterSearchFilters']
            );

            // Add the tags to the resource show public pages.
            $sharedEventManager->attach(
                $controller,
                'view.show.after',
                [$this, 'displayListAndFormPublic']
            );
        }
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ConfigForm::class);

        // TODO Find a better way to manage fieldset in config form.
        $data = [];
        $defaultSettings = $config['folksonomy']['config'];
        foreach ($defaultSettings as $name => $value) {
            $data['folksonomy_tag_page'][$name] = $settings->get($name, $value);
            $data['folksonomy_public_rights'][$name] = $settings->get($name, $value);
            $data['folksonomy_tagging_form'][$name] = $settings->get($name, $value);
        }

        $renderer->ckEditor();

        $form->init();
        $form->setData($data);
        $html = '<p>';
        $html .= $renderer->translate('It is recommended to create tag clouds with the blocks of the site pages.'); // @translate
        $html .= ' ' . $renderer->translate('So first options are used only to create global pages, that are not provided by Omeka yet.'); // @translate
        $html .= '</p>';
        $html .= $renderer->formCollection($form);
        return $html;
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');

        $params = $controller->getRequest()->getPost();

        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $params = $params->toArray();
        array_walk_recursive($params, function ($v, $k) use (&$params): void {
            $params[$k] = $v;
        });
        unset($params['folksonomy_tag_page']);
        unset($params['folksonomy_public_rights']);
        unset($params['folksonomy_tagging_form']);

        $defaultSettings = $config['folksonomy']['config'];
        foreach ($params as $name => $value) {
            if (array_key_exists($name, $defaultSettings)) {
                $settings->set($name, $value);
            }
        }
    }

    public function handleApiContext(Event $event): void
    {
        $context = $event->getParam('context');
        $context['o-module-folksonomy'] = 'http://omeka.org/s/vocabs/module/folksonomy#';
        $event->setParam('context', $context);
    }

    public function handleSqlResourceVisibility(Event $event): void
    {
        // Users can view taggings only if they have permission to view
        // the attached resource.
        $relatedEntities = $event->getParam('relatedEntities');
        $relatedEntities[Tagging::class] = 'resource_id';
        $event->setParam('relatedEntities', $relatedEntities);
    }

    /**
     * Cache taggings and tags for resource API search/read.
     *
     * The cache avoids self::filterItemJsonLd() to make multiple queries to the
     * database during one request.
     *
     * @param Event $event
     */
    public function cacheData(Event $event): void
    {
        $resource = $event->getParam('response')->getContent();
        // Check if this is an api search or api read to get the list of ids.
        $resourceIds = is_array($resource)
            ? array_map(function ($v) {
                return $v->getId();
            }, $resource)
            : [$resource->getId()];
        if (empty($resourceIds)) {
            return;
        }

        // TODO Use a unique direct scalar query to get all values to cache? Cache?
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $taggings = $api
            ->search('taggings', ['resource_id' => $resourceIds])
            ->getContent();
        foreach ($taggings as $tagging) {
            $resourceId = $tagging->resource()->id();
            // Cache tags.
            if (!is_null($tagging->tag())) {
                $this->cache['tags'][$resourceId][$tagging->tag()->id()] = $tagging->tag();
            }
            // Cache taggings.
            $this->cache['taggings'][$resourceId][$tagging->id()] = $tagging;
        }
    }

    /**
     * Add the taggings data to the resource JSON-LD.
     *
     * @todo Use tag and tagging reference, not representation.
     * @param Event $event
     */
    public function filterJsonLd(Event $event): void
    {
        $resourceId = $event->getTarget()->id();
        $jsonLd = $event->getParam('jsonLd');
        if (isset($this->cache['tags'][$resourceId])) {
            $jsonLd['o-module-folksonomy:tag'] = array_values($this->cache['tags'][$resourceId]);
        }
        if (isset($this->cache['taggings'][$resourceId])) {
            $jsonLd['o-module-folksonomy:tagging'] = array_values($this->cache['taggings'][$resourceId]);
        }
        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Helper to filter search queries.
     *
     * @internal The queries are optimized for big bases. See "Tagging and Folksonomy"
     * of Jay Pipes
     *
     * @param Event $event
     */
    public function searchQuery(Event $event): void
    {
        // TODO Add option for tagging status in admin search view.

        $adapter = $event->getTarget();

        $qb = $event->getParam('queryBuilder');
        $expr = $qb->expr();
        $query = $event->getParam('request')->getContent();

        if (!empty($query['has_tags'])) {
            $taggingAlias = $adapter->createAlias();
            $resourceName = $adapter->getResourceName() === 'users'
                ? 'owner'
                : 'resource';
            $qb
                ->innerJoin(
                    Tagging::class,
                    $taggingAlias,
                    'WITH',
                    $expr->andX(
                        $expr->eq($taggingAlias . '.' . $resourceName, 'omeka_root.id'),
                        $expr->isNotNull($taggingAlias . '.tag')
                    )
                );
        }

        if (!empty($query['tag'])) {
            $tags = $this->cleanStrings($query['tag']);
            if (empty($tags)) {
                return;
            }
            // All resources with any tag ("OR").
            // TODO The resquest is working, but it needs a format for querying.
            /*
            $tagAlias = $adapter->createAlias();
            $taggingAlias = $adapter->createAlias();
            $qb
                ->innerJoin(
                    Tagging::class,
                    $taggingAlias,
                    'WITH',
                    $expr->eq($taggingAlias . '.resource', $resourceAlias . '.id')
                )
                ->innerJoin(
                    Tag::class,
                    $tagAlias,
                    'WITH',
                    $expr->eq($tagAlias . '.id', $taggingAlias . '.tag')
                )
                ->andWhere($expr->in($tagAlias . '.name', $tags));
            */
            // All resources with all tags ("AND").
            foreach ($tags as $tag) {
                $tagAlias = $adapter->createAlias();
                $taggingAlias = $adapter->createAlias();
                $qb
                    // Simulate a cross join, not managed by doctrine.
                    ->innerJoin(
                        Tag::class,
                        $tagAlias,
                        'WITH',
                        '1 = 1'
                    )
                    ->innerJoin(
                        Tagging::class,
                        $taggingAlias,
                        'WITH',
                        $expr->andX(
                            $expr->eq($taggingAlias . '.resource', 'omeka_root.id'),
                            $expr->eq($taggingAlias . '.tag', $tagAlias . '.id')
                        )
                    )
                    ->andWhere($expr->eq(
                        $tagAlias . '.name',
                        $adapter->createNamedParameter($qb, $tag)
                    ));
            }
        }
    }

    /**
     * Handle hydration for tag and tagging data after hydration of resource.
     *
     * @todo Clarify and use acl only.
     * @param Event $event
     */
    public function handleTagging(Event $event): void
    {
        $resourceAdapter = $event->getTarget();
        /** @var \Omeka\Api\Request $request */
        $request = $event->getParam('request');

        if (!$resourceAdapter->shouldHydrate($request, 'o-module-folksonomy:tag')) {
            return;
        }

        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        if (!$acl->userIsAllowed(Tagging::class, 'create')) {
            return;
        }

        $api = $services->get('Omeka\ApiManager');
        $controllerPlugins = $services->get('ControllerPluginManager');

        $resourceId = $request->getId();
        $resource = $event->getParam('entity');
        // $errorStore = $event->getParam('errorStore');

        $submittedTags = $request->getValue('o-module-folksonomy:tag') ?: [];
        // Normalized new tags if any.
        $newTags = $request->getValue('o-module-folksonomy:tag-new', []);
        $newTags = array_filter(
            array_unique(
                array_map(
                    [$this, 'sanitizeString'],
                    is_array($newTags) ? $newTags : explode(',', $newTags)
                )
            ),
            function ($v) {
                return strlen($v);
            }
        );

        // Updated resource.
        if ($resourceId) {
            $representation = $resourceAdapter->getRepresentation($resource);
            $resourceTags = $this->listResourceTags($representation);
            $currentTags = array_map(function ($v) {
                return $v->name();
            }, $resourceTags);
            $addedTags = array_diff($submittedTags, $currentTags);
            $unchangedTags = array_intersect($currentTags, $submittedTags);
            $deletedTags = array_diff($currentTags, $unchangedTags);
        }
        // Added resource.
        else {
            $representation = null;
            $resourceTags = [];
            // $currentTaggings = [];
            $currentTags = [];
            $addedTags = $submittedTags;
            $unchangedTags = [];
            $deletedTags = [];
        }

        // TODO Create a query that returns new tags as key and formatted as
        // value, or that keeps order and returns each existing value or null.
        foreach ($newTags as $key => $newTag) {
            $tag = $api->search('tags', ['name' => $newTag])->getContent();
            if ($tag) {
                $tag = $tag[0]->name();
                $listed = array_search($tag, $deletedTags, true);
                if ($listed !== false) {
                    unset($deletedTags[$listed]);
                } else {
                    $listed = array_search($tag, $unchangedTags, true);
                    if ($listed === false) {
                        $addedTags[] = $tag;
                    }
                }
                unset($newTags[$key]);
            }
        }
        $addedTags = array_unique(array_merge($addedTags, $newTags));

        if ($addedTags) {
            $addTags = $controllerPlugins->get('addTags');
            $addTags($resource, $addedTags);
        }
        if ($deletedTags) {
            $deleteTags = $controllerPlugins->get('deleteTags');
            $deleteTags($resource, $deletedTags);
        }
    }

    /**
     * Add the headers for admin management.
     *
     * @param Event $event
     */
    public function addHeadersAdmin(Event $event): void
    {
        $view = $event->getTarget();
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/folksonomy-admin.css', 'Folksonomy'));
        $view->headScript()
            ->appendFile($assetUrl('js/folksonomy-admin.js', 'Folksonomy'), 'text/javascript', ['defer' => 'defer']);
    }

    /**
     * Add a tab to section navigation.
     *
     * @param Event $event
     */
    public function addTab(Event $event): void
    {
        $sectionNav = $event->getParam('section_nav');
        $sectionNav['tags'] = 'Tags'; // @translate
        $event->setParam('section_nav', $sectionNav);
    }

    /**
     * Display a partial for a resource.
     *
     * @param Event $event
     */
    public function displayListAndForm(Event $event): void
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $allowed = $acl->userIsAllowed(Tagging::class, 'create');

        echo '<div id="tags" class="section">';
        $resource = $event->getTarget()->resource;
        $this->displayResourceFolksonomy($event, $resource, false);
        if ($allowed) {
            $this->displayTaggingQuickForm($event);
        }
        echo '</div>';
    }

    /**
     * Display a partial for a resource in public.
     *
     * @param Event $event
     */
    public function displayListAndFormPublic(Event $event): void
    {
        $view = $event->getTarget();
        $resource = $view->resource;
        echo $view->showTags($resource);
        $this->displayTaggingQuickForm($event);
    }

    /**
     * Display the details for a resource.
     *
     * @param Event $event
     */
    public function viewDetails(Event $event): void
    {
        $representation = $event->getParam('entity');
        $this->displayResourceFolksonomy($event, $representation, true);
    }

    /**
     * Display a form.
     *
     * @param Event $event
     */
    public function displayForm(Event $event): void
    {
        $vars = $event->getTarget()->vars();
        // Manage add/edit form.
        if (isset($vars->item)) {
            $vars->offsetSet('resource', $vars->item);
        } elseif (isset($vars->itemSet)) {
            $vars->offsetSet('resource', $vars->itemSet);
        } elseif (isset($vars->media)) {
            $vars->offsetSet('resource', $vars->media);
        } else {
            $vars->offsetSet('resource', null);
            $vars->offsetSet('tags', []);
            $vars->offsetSet('taggings', []);
        }
        if ($vars->resource) {
            $vars->offsetSet('tags', $this->listResourceTagsByName($vars->resource));
            $vars->offsetSet('taggings', $this->listResourceTaggingsByName($vars->resource));
        }

        echo $event->getTarget()->partial(
            'common/admin/tagging-form'
        );
    }

    /**
     * Display the quick tagging form.
     *
     * @param Event $event
     */
    public function displayTaggingQuickForm(Event $event): void
    {
        $view = $event->getTarget();
        $resource = $event->getTarget()->resource;
        echo $view->showTaggingForm($resource);
    }

    /**
     * Helper to display the tags for a resource.
     *
     * @param Event $event
     * @param AbstractResourceEntityRepresentation $resource
     * @param bool $listAsDiv Return the list with div, not ul.
     */
    protected function displayResourceFolksonomy(
        Event $event,
        AbstractResourceEntityRepresentation $resource,
        $listAsDiv = false
    ): void {
        $tags = $this->listResourceTagsByName($resource);
        $taggings = $this->listResourceTaggingsByName($resource);
        $partial = $listAsDiv
            ? 'common/admin/tag-resource'
            : 'common/admin/tag-resource-list';
        echo $event->getTarget()->partial(
            $partial,
            [
                'resource' => $resource,
                'tags' => $tags,
                'taggings' => $taggings,
            ]
        );
    }

    /**
     * Display the advanced search form via partial.
     *
     * @param Event $event
     */
    public function displayAdvancedSearch(Event $event): void
    {
        $query = $event->getParam('query', []);
        $query['has_tags'] = !empty($query['tag']);
        $query['tag'] = isset($query['tag']) ? $this->cleanStrings($query['tag']) : [];
        $event->setParam('query', $query);

        $partials = $event->getParam('partials', []);
        $partials[] = 'common/tag-advanced-search';
        $event->setParam('partials', $partials);
    }

    /**
     * Filter search filters.
     *
     * @param Event $event
     */
    public function filterSearchFilters(Event $event): void
    {
        $translate = $event->getTarget()->plugin('translate');
        $filters = $event->getParam('filters');
        $query = $event->getParam('query', []);
        if (!empty($query['has_tags'])) {
            $filterLabel = $translate('Has tags'); // @translate
            $filterValue = $translate('true');
            $filters[$filterLabel][] = $filterValue;
        }
        if (!empty($query['tag'])) {
            $filterLabel = $translate('Tag'); // @translate
            $filterValue = $this->cleanStrings($query['tag']);
            $filters[$filterLabel] = $filterValue;
        }
        $event->setParam('filters', $filters);
    }

    /**
     * Helper to return tags of a resource.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array
     */
    protected function listResourceTags(AbstractResourceEntityRepresentation $resource)
    {
        if (empty($resource->id())) {
            return [];
        }
        $resourceJson = $resource->jsonSerialize();
        return empty($resourceJson['o-module-folksonomy:tag'])
            ? []
            : $resourceJson['o-module-folksonomy:tag'];
    }

    /**
     * Helper to return taggings of a resource.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array
     */
    protected function listResourceTaggings(AbstractResourceEntityRepresentation $resource)
    {
        if (empty($resource->id())) {
            return [];
        }
        $resourceJson = $resource->jsonSerialize();
        return empty($resourceJson['o-module-folksonomy:tagging'])
            ? []
            : $resourceJson['o-module-folksonomy:tagging'];
    }

    /**
     * Helper to return tags of a resource by name.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array
     */
    protected function listResourceTagsByName(AbstractResourceEntityRepresentation $resource)
    {
        $result = [];
        $tags = $this->listResourceTags($resource);
        foreach ($tags as $tag) {
            $result[$tag->name()] = $tag;
        }
        return $result;
    }

    /**
     * Helper to return a list of taggings of a resource by tag name.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array
     */
    protected function listResourceTaggingsByName(AbstractResourceEntityRepresentation $resource)
    {
        $result = [];
        $taggings = $this->listResourceTaggings($resource);
        foreach ($taggings as $tagging) {
            $tag = $tagging->tag();
            $result[$tag ? $tag->name() : ''] = $tagging;
        }
        return $result;
    }

    /**
     * Helper to return a flat list of tags by id.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array
     */
    protected function listFlatResourceTags(AbstractResourceEntityRepresentation $resource)
    {
        $result = [];
        $tags = $this->listResourceTags($resource);
        foreach ($tags as $tag) {
            $result[$tag->internalId()] = $tag->name();
        }
        return $result;
    }

    /**
     * Clean a list of alphanumeric strings, separated by a comma.
     *
     * @param array|string $strings
     * @return array
     */
    protected function cleanStrings($strings)
    {
        if (!is_array($strings)) {
            $strings = explode(',', $strings);
        }
        return array_filter(array_map('trim', $strings));
    }

    /**
     * Returns a sanitized string.
     *
     * @param string $string The string to sanitize.
     * @return string The sanitized string.
     */
    protected function sanitizeString($string)
    {
        // Quote is allowed.
        $string = strip_tags((string) $string);
        $string = preg_replace('~^[\p{Z}/\\?<>:*%|"`&;]+|[\p{Z}/\\?<>:*%|"`&;]+$~u', '', $string);
        $string = preg_replace('/[\(\{]/', '[', $string);
        $string = preg_replace('/[\)\}]/', ']', $string);
        $string = preg_replace('~[[:cntrl:]/\\\?<>\*\%\|\"`\&\;#+\^\$\s]~', ' ', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
