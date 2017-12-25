<?php
/**
 * Folksonomy
 *
 * Add tags and tagging form to any resource to create uncontrolled vocabularies
 * and tag clouds.
 *
 * @copyright Daniel Berthereau, 2013-2017
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

use Doctrine\DBAL\DBALException;
use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Folksonomy\Form\ConfigForm;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;
use Omeka\Stdlib\Message;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Form\Element\Checkbox;
use Zend\Form\Fieldset;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    /**
     * @var array Cache of tags and taggings by resource.
     */
    protected $cache = [
        'tags' => [],
        'taggings' => [],
    ];

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->addEntityManagerFilters();
        $this->addAclRules();
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $t = $serviceLocator->get('MvcTranslator');

        $sql = <<<'SQL'
CREATE TABLE tag (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(190) NOT NULL,
    UNIQUE INDEX UNIQ_389B7835E237E06 (name),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE tagging (
    id INT AUTO_INCREMENT NOT NULL,
    tag_id INT DEFAULT NULL,
    resource_id INT DEFAULT NULL,
    owner_id INT DEFAULT NULL,
    status VARCHAR(190) NOT NULL,
    created DATETIME NOT NULL,
    modified DATETIME DEFAULT NULL,
    INDEX IDX_A4AED123BAD26311 (tag_id),
    INDEX IDX_A4AED12389329D25 (resource_id),
    INDEX IDX_A4AED1237E3C61F9 (owner_id),
    INDEX IDX_A4AED1237B00651C (status),
    UNIQUE INDEX owner_tag_resource (owner_id, tag_id, resource_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE tagging ADD CONSTRAINT FK_A4AED123BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE SET NULL;
ALTER TABLE tagging ADD CONSTRAINT FK_A4AED12389329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE SET NULL;
ALTER TABLE tagging ADD CONSTRAINT FK_A4AED1237E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;
SQL;
        $connection = $serviceLocator->get('Omeka\Connection');
        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            try{
                $connection->exec($sql);
            } catch (DBALException $e) {
                $this->uninstall($serviceLocator);
                $logger = $serviceLocator->get('Omeka\Logger');
                $logger->err($e);
                throw new ModuleCannotInstallException(
                    new Message($t->translate('An error occured during sql query. See log for more details. Code: %s; Message: %s'), // @translate
                        $e->getCode(), $e->getMessage()));
            }
        }

        $settings = $serviceLocator->get('Omeka\Settings');
        $this->manageSettings($settings, 'install');

        $html = '<p>';
        $html .= sprintf($t->translate('I agree with %sterms of use%s and I accept to free my contribution under the licence %sCC BY-SA%s.'), // @translate
            '<a rel="licence" href="#" target="_blank">', '</a>',
            '<a rel="licence" href="https://creativecommons.org/licenses/by-sa/3.0/" target="_blank">', '</a>'
        );
        $html .= '</p>';
        $settings->set('folksonomy_legal_text', $html);
        $this->manageSiteSettings($serviceLocator, 'install');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $sql = <<<'SQL'
DROP TABLE IF EXISTS tagging;
DROP TABLE IF EXISTS tag;
SQL;
        $connection = $serviceLocator->get('Omeka\Connection');
        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }

        $this->manageSettings($serviceLocator->get('Omeka\Settings'), 'uninstall');
        $this->manageSiteSettings($serviceLocator, 'uninstall');
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        if (version_compare($oldVersion, '3.3.3', '<')) {
            $config = require __DIR__ . '/config/module.config.php';
            $defaultSettings = $config[strtolower(__NAMESPACE__)]['settings'];
            $settings = $serviceLocator->get('Omeka\Settings');
            $settings->set('folksonomy_append_item_set_show',
                $defaultSettings['folksonomy_append_item_set_show']);
            $settings->set('folksonomy_append_item_show',
                $defaultSettings['folksonomy_append_item_show']);
            $settings->set('folksonomy_append_media_show',
                $defaultSettings['folksonomy_append_media_show']);
        }

        if (version_compare($oldVersion, '3.3.7', '<')) {
            $config = require __DIR__ . '/config/module.config.php';
            $defaultSettings = $config[strtolower(__NAMESPACE__)]['site_settings'];
            $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
            $settings = $serviceLocator->get('Omeka\Settings');
            $api = $serviceLocator->get('Omeka\ApiManager');
            $sites = $api->search('sites')->getContent();
            foreach ($sites as $site) {
                $siteSettings->setTargetId($site->id());
                $siteSettings->set('folksonomy_append_item_set_show',
                    $settings->get('folksonomy_append_item_set_show',
                        $defaultSettings['folksonomy_append_item_set_show'])
                );
                $siteSettings->set('folksonomy_append_item_show',
                    $settings->get('folksonomy_append_item_show',
                        $defaultSettings['folksonomy_append_item_show'])
                );
                $siteSettings->set('folksonomy_append_media_show',
                    $settings->get('folksonomy_append_media_show',
                        $defaultSettings['folksonomy_append_media_show'])
                );
            }
            $settings->delete('folksonomy_append_item_set_show');
            $settings->delete('folksonomy_append_item_show');
            $settings->delete('folksonomy_append_media_show');
        }
    }

    protected function manageSettings($settings, $process, $key = 'settings')
    {
        $config = require __DIR__ . '/config/module.config.php';
        $defaultSettings = $config[strtolower(__NAMESPACE__)][$key];
        foreach ($defaultSettings as $name => $value) {
            switch ($process) {
                case 'install':
                    $settings->set($name, $value);
                    break;
                case 'uninstall':
                    $settings->delete($name);
                    break;
            }
        }
    }

    protected function manageSiteSettings(ServiceLocatorInterface $serviceLocator, $process)
    {
        $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
        $api = $serviceLocator->get('Omeka\ApiManager');
        $sites = $api->search('sites')->getContent();
        foreach ($sites as $site) {
            $siteSettings->setTargetId($site->id());
            $this->manageSettings($siteSettings, $process, 'site_settings');
        }
    }

    /**
     * Add tag and tagging visibility filters to the entity manager.
     */
    protected function addEntityManagerFilters()
    {
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $entityManagerFilters = $services->get('Omeka\EntityManager')->getFilters();
        $entityManagerFilters->enable('tagging_visibility');
        $entityManagerFilters->getFilter('tagging_visibility')->setAcl($acl);
    }

    /**
     * Add ACL rules for this module.
     *
     * @todo Simplify rules (see module Comment).
     */
    protected function addAclRules()
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
        $acl->allow(
            null,
            [
                Controller\Admin\TaggingController::class,
                Controller\Site\TaggingController::class,
            ]
        );
        $acl->allow(
            null,
            Api\Adapter\TaggingAdapter::class,
            $publicAdapterRights
        );
        $acl->allow(
            null,
            Tagging::class,
            $publicEntityRights
        );

        $acl->allow(
            'researcher',
            Controller\Admin\TaggingController::class,
            [
                'add',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'researcher',
            Api\Adapter\TaggingAdapter::class,
            [
                'create',
            ]
        );
        $acl->allow(
            'researcher',
            Tagging::class,
            [
                'create',
            ]
        );
        $acl->allow(
            'researcher',
            Tagging::class,
            [
                'read',
            ],
            new OwnsEntityAssertion
        );

        $acl->allow(
            'author',
            Controller\Admin\TaggingController::class,
            [
                'add',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'author',
            Api\Adapter\TaggingAdapter::class,
            [
                'create',
            ]
        );
        $acl->allow(
            'author',
            Tagging::class,
            [
                'create',
            ]
        );
        $acl->allow(
            'author',
            Tagging::class,
            [
                'read',
            ],
            new OwnsEntityAssertion
        );

        $acl->allow(
            'reviewer',
            Controller\Admin\TaggingController::class,
            [
                'add',
                'edit',
                'delete',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'reviewer',
            Api\Adapter\TaggingAdapter::class,
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'reviewer',
            Tagging::class,
            [
                'create',
                'update',
                'delete',
                'view-all',
            ]
        );

        $acl->allow(
            'editor',
            Controller\Admin\TaggingController::class,
            [
                'add',
                'edit',
                'delete',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'editor',
            Api\Adapter\TaggingAdapter::class,
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'editor',
            Tagging::class,
            [
                'create',
                'update',
                'delete',
                'view-all',
            ]
        );

        // Similar than items or item sets from Omeka\Service\AclFactory.
        $acl->allow(
            null,
            [
                Controller\Admin\TagController::class,
                Controller\Site\TagController::class,
            ]
        );
        $acl->allow(
            null,
            Api\Adapter\TagAdapter::class,
            $publicAdapterRights
        );
        $acl->allow(
            null,
            Tag::class,
            $publicEntityRights
        );

        $acl->allow(
            'researcher',
            Controller\Admin\TagController::class,
            [
                'add',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'researcher',
            Api\Adapter\TagAdapter::class,
            [
                'create',
            ]
        );
        $acl->allow(
            'researcher',
            Tag::class,
            [
                'create',
            ]
        );
        $acl->allow(
            'researcher',
            Tag::class,
            [
                'read',
            ],
            new OwnsEntityAssertion
        );

        $acl->allow(
            'author',
            Controller\Admin\TagController::class,
            [
                'add',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'author',
            Api\Adapter\TagAdapter::class,
            [
                'create',
            ]
        );
        $acl->allow(
            'author',
            Tag::class,
            [
                'create',
            ]
        );
        $acl->allow(
            'author',
            Tag::class,
            [
                'read',
            ],
            new OwnsEntityAssertion
        );

        $acl->allow(
            'reviewer',
            Controller\Admin\TagController::class,
            [
                'add',
                'edit',
                'delete',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'reviewer',
            Api\Adapter\TagAdapter::class,
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'reviewer',
            Tag::class,
            [
                'create',
                'update',
                'delete',
                'view-all',
            ]
        );

        $acl->allow(
            'editor',
            Controller\Admin\TagController::class,
            [
                'add',
                'edit',
                'delete',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'editor',
            Api\Adapter\TagAdapter::class,
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'editor',
            Tag::class,
            [
                'create',
                'update',
                'delete',
                'view-all',
            ]
        );
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // TODO Add a setting to limit resources (see module Comment).

        // Add the Folksonomy term definition.
        $sharedEventManager->attach(
            '*',
            'api.context',
            function (Event $event) {
                $context = $event->getParam('context');
                $context['o-module-folksonomy'] = 'http://omeka.org/s/vocabs/module/folksonomy#';
                $event->setParam('context', $context);
            }
        );

        // Add the visibility filters.
        $sharedEventManager->attach(
            '*',
            'sql_filter.resource_visibility',
            function (Event $event) {
                // Users can view taggings only if they have permission to view
                // the attached resource.
                $relatedEntities = $event->getParam('relatedEntities');
                $relatedEntities[Tagging::class] = 'resource_id';
                $event->setParam('relatedEntities', $relatedEntities);
            }
        );

        $representations = [
            \Omeka\Api\Representation\ItemRepresentation::class,
            \Omeka\Api\Representation\ItemSetRepresentation::class,
            \Omeka\Api\Representation\MediaRepresentation::class,
        ];
        foreach ($representations as $representation) {
            // Add the tagging part to the resource representation.
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
            // Add the tab tagging form to the resource add and edit admin pages.
            $sharedEventManager->attach(
                $controller,
                'view.add.section_nav',
                [$this, 'addTaggingTab']
            );
            $sharedEventManager->attach(
                $controller,
                'view.edit.section_nav',
                [$this, 'addTaggingTab']
            );
            $sharedEventManager->attach(
                $controller,
                'view.add.form.after',
                [$this, 'displayTaggingForm']
            );
            $sharedEventManager->attach(
                $controller,
                'view.edit.form.after',
                [$this, 'displayTaggingForm']
            );

            // Add the show tags to the resource show admin pages.
            $sharedEventManager->attach(
                $controller,
                'view.show.before',
                [$this, 'addHeadersAdmin']
            );
            $sharedEventManager->attach(
                $controller,
                'view.show.section_nav',
                [$this, 'addTaggingTab']
            );
            $sharedEventManager->attach(
                $controller,
                'view.show.after',
                [$this, 'viewShowAfter']
            );

            // Add the show tags to the resource browse admin pages (details).
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
                [$this, 'viewShowAfterPublic']
            );
        }

        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'addSiteSettingsFormElements']
        );
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $formElementManager = $services->get('FormElementManager');

        $data = [];
        $defaultSettings = $config[strtolower(__NAMESPACE__)]['settings'];
        foreach ($defaultSettings as $name => $value) {
            $data['folksonomy_public_rights'][$name] = $settings->get($name);
            $data['folksonomy_tagging_form'][$name] = $settings->get($name);
        }

        $renderer->ckEditor();

        $form = $formElementManager->get(ConfigForm::class);
        $form->init();
        $form->setData($data);
        $html = $renderer->formCollection($form);
        return $html;
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');

        $params = $controller->getRequest()->getPost();

        $form = $this->getServiceLocator()->get('FormElementManager')
            ->get(ConfigForm::class);
        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $params = $params->toArray();
        array_walk_recursive($params, function($v, $k) use (&$params) { $params[$k] = $v; });
        unset($params['folksonomy_public_rights']);
        unset($params['folksonomy_tagging_form']);

        $defaultSettings = $config[strtolower(__NAMESPACE__)]['settings'];
        foreach ($params as $name => $value) {
            if (isset($defaultSettings[$name])) {
                $settings->set($name, $value);
            }
        }
    }

    public function addSiteSettingsFormElements(Event $event)
    {
        $services = $this->getServiceLocator();
        $siteSettings = $services->get('Omeka\Settings\Site');
        $config = $services->get('Config');
        $form = $event->getTarget();

        $defaultSiteSettings = $config[strtolower(__NAMESPACE__)]['site_settings'];

        $fieldset = new Fieldset('folksonomy');
        $fieldset->setLabel('Folksonomy'); // @translate

        $fieldset->add([
            'name' => 'folksonomy_append_item_set_show',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item set page"', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'folksonomy_append_item_set_show',
                    $defaultSiteSettings['folksonomy_append_item_set_show']
                ),
            ],
        ]);

        $fieldset->add([
            'name' => 'folksonomy_append_item_show',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Append automatically to item page', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'folksonomy_append_item_show',
                    $defaultSiteSettings['folksonomy_append_item_show']
                ),
            ],
        ]);

        $fieldset->add([
            'name' => 'folksonomy_append_media_show',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Append automatically to media page"', // @translate
                'info' => 'If unchecked, the viewer can be added via the helper in the theme or the block in any page.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get(
                    'folksonomy_append_media_show',
                    $defaultSiteSettings['folksonomy_append_media_show']
                ),
            ],
        ]);

        $form->add($fieldset);
    }

    /**
     * Cache taggings and tags for resource API search/read.
     *
     * The cache avoids self::filterItemJsonLd() to make multiple queries to the
     * database during one request.
     *
     * @param Event $event
     */
    public function cacheData(Event $event)
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
    public function filterJsonLd(Event $event)
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
    public function searchQuery(Event $event)
    {
        // TODO Add option for tagging status in admin search view.

        $query = $event->getParam('request')->getContent();

        if (!empty($query['has_tags'])) {
            $qb = $event->getParam('queryBuilder');
            $adapter = $event->getTarget();
            $taggingAlias = $adapter->createAlias();
            $resourceAlias = $adapter->getEntityClass();
            $resourceName = $adapter->getResourceName() === 'users'
                ? 'owner'
                : 'resource';
            $qb
                ->innerJoin(
                    Tagging::class,
                    $taggingAlias,
                    'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq($taggingAlias . '.' . $resourceName, $resourceAlias . '.id'),
                        $qb->expr()->isNotNull($taggingAlias . '.tag')
                    )
                );
        }

        if (!empty($query['tag'])) {
            $tags = $this->cleanStrings($query['tag']);
            if (empty($tags)) {
                return;
            }
            $qb = $event->getParam('queryBuilder');
            $adapter = $event->getTarget();
            $resourceAlias = $adapter->getEntityClass();
            // All resources with any tag ("OR").
            // TODO The resquest is working, but it needs a format for querying.
            /*
            $qb
                ->innerJoin(
                    Tagging::class,
                    $taggingAlias,
                    'WITH',
                    $qb->expr()->eq($taggingAlias . '.resource', $resourceAlias . '.id')
                )
                ->innerJoin(
                    Tag::class,
                    $tagAlias,
                    'WITH',
                    $qb->expr()->eq($tagAlias . '.id', $taggingAlias . '.tag')
                )
                ->andWhere($qb->expr()->in($tagAlias . '.name', $tags));
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
                        'WITH', '1 = 1'
                    )
                    ->innerJoin(
                        Tagging::class,
                        $taggingAlias,
                        'WITH',
                        $qb->expr()->andX(
                            $qb->expr()->eq($taggingAlias . '.resource', $resourceAlias . '.id'),
                            $qb->expr()->eq($taggingAlias . '.tag', $tagAlias . '.id')
                        )
                    )
                    ->andWhere($qb->expr()->eq(
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
    public function handleTagging(Event $event)
    {
        $resourceAdapter = $event->getTarget();
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
        $errorStore = $event->getParam('errorStore');

        $submittedTags = $request->getValue('o-module-folksonomy:tag') ?: [];
        // Normalized new tags if any.
        $newTags = array_filter(
            array_unique(
                array_map(
                    [$this, 'sanitizeString'],
                    explode(',', $request->getValue('o-module-folksonomy:tag-new', ''))
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
    public function addHeadersAdmin(Event $event)
    {
        $view = $event->getTarget();
        $view->headLink()->appendStylesheet($view->assetUrl('css/folksonomy-admin.css', 'Folksonomy'));
        $view->headScript()->appendFile($view->assetUrl('js/folksonomy-admin.js', 'Folksonomy'));
    }

    /**
     * Add the tagging tab to section navigations.
     *
     * @param Event $event
     */
    public function addTaggingTab(Event $event)
    {
        $sectionNav = $event->getParam('section_nav');
        $sectionNav['tags'] = 'Tags'; // @translate
        $event->setParam('section_nav', $sectionNav);
    }

    /**
     * Display the tagging form.
     *
     * @param Event $event
     */
    public function displayTaggingForm(Event $event)
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
    public function displayTaggingQuickForm(Event $event)
    {
        $view = $event->getTarget();
        $resource = $event->getTarget()->resource;
        echo $view->showTaggingForm($resource);
    }

    /**
     * Display the tags for a resource.
     *
     * @param Event $event
     */
    public function viewShowAfter(Event $event)
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
    public function viewShowAfterPublic(Event $event)
    {
        $serviceLocator = $this->getServiceLocator();
        $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
        $view = $event->getTarget();
        $resource = $view->resource;
        $resourceName = $resource->resourceName();
        $appendMap = [
            'item_sets' => 'folksonomy_append_item_set_show',
            'items' => 'folksonomy_append_item_show',
            'media' => 'folksonomy_append_media_show',
        ];
        if (!$siteSettings->get($appendMap[$resourceName])) {
            return;
        }

        echo $view->showTags($resource);
        $this->displayTaggingQuickForm($event);
    }

    /**
     * Display the tags for a resource (details).
     *
     * @param Event $event
     */
    public function viewDetails(Event $event)
    {
        $representation = $event->getParam('entity');
        $this->displayResourceFolksonomy($event, $representation, true);
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
    ) {
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
    public function displayAdvancedSearch(Event $event)
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
    public function filterSearchFilters(Event $event)
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
        $string = strip_tags($string);
        // The first character is a space and the last one is a no-break space.
        $string = trim($string, ' /\\?<>:*%|"`&; ' . "\t\n\r");
        $string = preg_replace('/[\(\{]/', '[', $string);
        $string = preg_replace('/[\)\}]/', ']', $string);
        $string = preg_replace('/[[:cntrl:]\/\\\?<>\*\%\|\"`\&\;#+\^\$\s]/', ' ', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
