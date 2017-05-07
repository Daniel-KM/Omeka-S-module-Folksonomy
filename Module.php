<?php
namespace Folksonomy;

/*
 * Folksonomy
 *
 * Add tags and tagging form to any resource to create uncontrolled vocabularies
 * and tag clouds.
 *
 * @copyright Daniel Berthereau, 2013-2017
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */

use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Folksonomy\Form\Config as ConfigForm;
use Folksonomy\Form\Search as SearchForm;
use Folksonomy\Form\Tagging as TaggingForm;
use Omeka\Api\Representation\AbstractResourceRepresentation;
use Omeka\Api\Request;
use Omeka\Module\AbstractModule;
use Omeka\Stdlib\ErrorStore;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

/**
 * Folksonomy
 *
 * Add tags and tagging form to any resource to create uncontrolled vocabularies
 * and tag clouds.
 *
 * @copyright Daniel Berthereau, 2013-2017
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    /**
     * @var array Cache of tags and taggings by resource.
     */
    protected $cache = ['tags' => [], 'taggings' => []];

    /**
     * Settings and their default values.
     *
     * @var array
     */
    protected $settings = [
        'folksonomy_public_allow_tag' => true,
        'folksonomy_public_require_moderation' => false,
        'folksonomy_max_length_tag' => 190,
        'folksonomy_max_length_total' => 1000,
        'folksonomy_message' => '+',
        'folksonomy_legal_text' => '',
    ];

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->addAclRules();
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $t = $serviceLocator->get('MvcTranslator');

        $sql = <<<'SQL'
CREATE TABLE `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_389B7835E237E06` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `tagging` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag_id` int(11) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `owner_tag_resource` (`owner_id`,`tag_id`,`resource_id`),
  KEY `IDX_A4AED1237B00651C` (`status`),
  KEY `IDX_A4AED123BAD26311` (`tag_id`),
  KEY `IDX_A4AED12389329D25` (`resource_id`),
  KEY `IDX_A4AED1237E3C61F9` (`owner_id`),
  CONSTRAINT `FK_A4AED1237E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_A4AED12389329D25` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_A4AED123BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec($sql);

        $html = '<p>';
        $html .= $t->translate(sprintf('I agree with %sterms of use%s and I accept to free my contribution under the licence %sCC BY-SA%s.', // @translate
            '<a rel="licence" href="#" target="_blank">', '</a>',
            '<a rel="licence" href="https://creativecommons.org/licenses/by-sa/3.0/" target="_blank">', '</a>'
        ));
        $html .= '</p>';
        $this->settings['folksonomy_legal_text'] = $html;

        $settings = $serviceLocator->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $settings->set($name, $value);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $sql = <<<'SQL'
DROP TABLE IF EXISTS tagging;
DROP TABLE IF EXISTS tag;
SQL;
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec($sql);

        $settings = $serviceLocator->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $settings->delete($name);
        }
    }

    /**
     * Add ACL rules for this module.
     */
    protected function addAclRules()
    {
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $settings = $services->get('Omeka\Settings');

        $publicAllowTag = $settings->get('folksonomy_public_allow_tag', $this->settings['folksonomy_public_allow_tag']);
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
                'Folksonomy\Controller\Admin\Tagging',
                'Folksonomy\Controller\Site\Tagging',
            ]
        );
        $acl->allow(
            null,
            'Folksonomy\Api\Adapter\TaggingAdapter',
            $publicAdapterRights
        );
        $acl->allow(
            null,
            'Folksonomy\Entity\Tagging',
            $publicEntityRights
        );

        $acl->allow(
            'researcher',
            'Folksonomy\Controller\Admin\Tagging',
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
            'Folksonomy\Api\Adapter\TaggingAdapter',
            [
                'create',
            ]
        );
        $acl->allow(
            'researcher',
            'Folksonomy\Entity\Tagging',
            [
                'create',
            ]
        );

        $acl->allow(
            'author',
            'Folksonomy\Controller\Admin\Tagging',
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
            'Folksonomy\Api\Adapter\TaggingAdapter',
            [
                'create',
            ]
        );
        $acl->allow(
            'author',
            'Folksonomy\Entity\Tagging',
            [
                'create',
            ]
        );

        $acl->allow(
            'reviewer',
            'Folksonomy\Controller\Admin\Tagging',
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
            'Folksonomy\Api\Adapter\TaggingAdapter',
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'reviewer',
            'Folksonomy\Entity\Tagging',
            [
                'create',
                'update',
                'delete',
                'view-all',
            ]
        );

        $acl->allow(
            'editor',
            'Folksonomy\Controller\Admin\Tagging',
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
            'Folksonomy\Api\Adapter\TaggingAdapter',
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'editor',
            'Folksonomy\Entity\Tagging',
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
                'Folksonomy\Controller\Admin\Tag',
                'Folksonomy\Controller\Site\Tag',
            ]
        );
        $acl->allow(
            null,
            'Folksonomy\Api\Adapter\TagAdapter',
            $publicAdapterRights
        );
        $acl->allow(
            null,
            'Folksonomy\Entity\Tag',
            $publicEntityRights
        );

        $acl->allow(
            'researcher',
            'Folksonomy\Controller\Admin\Tag',
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
            'Folksonomy\Api\Adapter\TagAdapter',
            [
                'create',
            ]
        );
        $acl->allow(
            'researcher',
            'Folksonomy\Entity\Tag',
            [
                'create',
            ]
        );

        $acl->allow(
            'author',
            'Folksonomy\Controller\Admin\Tag',
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
            'Folksonomy\Api\Adapter\TagAdapter',
            [
                'create',
            ]
        );
        $acl->allow(
            'author',
            'Folksonomy\Entity\Tag',
            [
                'create',
            ]
        );

        $acl->allow(
            'reviewer',
            'Folksonomy\Controller\Admin\Tag',
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
            'Folksonomy\Api\Adapter\TagAdapter',
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'reviewer',
            'Folksonomy\Entity\Tag',
            [
                'create',
                'update',
                'delete',
                'view-all',
            ]
        );

        $acl->allow(
            'editor',
            'Folksonomy\Controller\Admin\Tag',
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
            'Folksonomy\Api\Adapter\TagAdapter',
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'editor',
            'Folksonomy\Entity\Tag',
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
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');

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
                $relatedEntities['Folksonomy\Entity\Tagging'] = 'resource_id';
                $event->setParam('relatedEntities', $relatedEntities);
            }
        );

        $sharedEventManager->attach(
            'Omeka\Api\Representation\ItemRepresentation',
            'rep.resource.json',
            [$this, 'filterResourceJsonLd']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\ItemSetRepresentation',
            'rep.resource.json',
            [$this, 'filterResourceJsonLd']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\MediaRepresentation',
            'rep.resource.json',
            [$this, 'filterResourceJsonLd']
        );

        // Add the tag field to the admin and public advanced search page.
        $sharedEventManager->attach(
            '*',
            'view.advanced_search',
            [$this, 'displayAdvancedSearch']
        );

        // Add the tagging and tag filters to resource search.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'searchQuery']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.search.query',
            [$this, 'searchQuery']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.search.query',
            [$this, 'searchQuery']
        );

        // Cache some resources after a search.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.post',
            [$this, 'cacheResourceTaggingData']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.read.post',
            [$this, 'cacheResourceTaggingData']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.search.post',
            [$this, 'cacheResourceTaggingData']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.read.post',
            [$this, 'cacheResourceTaggingData']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.search.post',
            [$this, 'cacheResourceTaggingData']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.read.post',
            [$this, 'cacheResourceTaggingData']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleTagging']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.hydrate.post',
            [$this, 'handleTagging']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.hydrate.post',
            [$this, 'handleTagging']
        );

        // Add the tab tagging form to the resource add and edit admin pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.after',
            [$this, 'displayTaggingForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            [$this, 'displayTaggingForm']
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.add.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.edit.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.add.form.after',
            [$this, 'displayTaggingForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.edit.form.after',
            [$this, 'displayTaggingForm']
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.add.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.add.form.after',
            [$this, 'displayTaggingForm']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.form.after',
            [$this, 'displayTaggingForm']
        );

        // Add the show tags to the resource show admin pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.before',
            [$this, 'addHeadersAdmin']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            [$this, 'displayViewResourceTags']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.show.before',
            [$this, 'addHeadersAdmin']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.show.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.show.after',
            [$this, 'displayViewResourceTags']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.before',
            [$this, 'addHeadersAdmin']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.section_nav',
            [$this, 'addTaggingTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.after',
            [$this, 'displayViewResourceTags']
        );

        // Add the show tags to the resource browse admin pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.browse.before',
            [$this, 'addHeadersAdmin']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.details',
            [$this, 'displayViewEntityTags']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.browse.before',
            [$this, 'addHeadersAdmin']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.details',
            [$this, 'displayViewEntityTags']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.browse.before',
            [$this, 'addHeadersAdmin']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.details',
            [$this, 'displayViewEntityTags']
        );

        // Filter the search filters for the advanced search pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.search.filters',
            [$this, 'filterSearchFilters']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.search.filters',
            [$this, 'filterSearchFilters']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.search.filters',
            [$this, 'filterSearchFilters']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.search.filters',
            [$this, 'filterSearchFilters']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\ItemSet',
            'view.search.filters',
            [$this, 'filterSearchFilters']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Media',
            'view.search.filters',
            [$this, 'filterSearchFilters']
        );

        // Add the tags to the resource show public pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            [$this, 'displayViewResourceTagsPublic']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\ItemSet',
            'view.show.after',
            [$this, 'displayViewResourceTagsPublic']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Media',
            'view.show.after',
            [$this, 'displayViewResourceTagsPublic']
        );

        // Add the tagging form to the resource show public pages.
        if ($settings->get('folksonomy_public_allow_tag')) {
            $sharedEventManager->attach(
                'Omeka\Controller\Site\Item',
                'view.show.after',
                [$this, 'displayTaggingFormPublic']
            );
            $sharedEventManager->attach(
                'Omeka\Controller\Site\ItemSet',
                'view.show.after',
                [$this, 'displayTaggingFormPublic']
            );
            $sharedEventManager->attach(
                'Omeka\Controller\Site\Media',
                'view.show.after',
                [$this, 'displayTaggingFormPublic']
            );
        }
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $formElementManager = $services->get('FormElementManager');

        $data = [];
        foreach ($this->settings as $name => $value) {
            $data[$name] = $settings->get($name);
        }
        $formData = [];
        $formData['folksonomy_tagging_form'] = [
            'folksonomy_message' => $data['folksonomy_message'],
            'folksonomy_max_length_tag' => $data['folksonomy_max_length_tag'],
            'folksonomy_max_length_total' => $data['folksonomy_max_length_total'],
        ];
        $formData['folksonomy_public_rights'] = [
            'folksonomy_legal_text' => $data['folksonomy_legal_text'],
            'folksonomy_public_allow_tag' => $data['folksonomy_public_allow_tag'],
            'folksonomy_public_require_moderation' => $data['folksonomy_public_require_moderation'],
        ];

        $form = $formElementManager->get(ConfigForm::class);
        $form->init();
        $form->setData($formData);

        // Allow to display fieldsets in config form.
        $vars = [];
        $vars['form'] = $form;
        return $renderer->render('folksonomy/module/config.phtml', $vars);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        $params = $controller->getRequest()->getPost();

        // TODO Check ckeditor.
        // $form = new ConfigForm;
        // $form->init();
        // $form->setData($params);
        // if (!$form->isValid()) {
        //     $controller->messenger()->addErrors($form->getMessages());
        //     return false;
        // }

        // $params = $form->getData();

        // Manage fieldsets of params automatically (only used for the view).
        foreach ($params as $name => $value) {
            if (isset($this->settings[$name])) {
                $settings->set($name, $value);
            } elseif (is_array($value)) {
                foreach ($value as $subname => $subvalue) {
                    if (isset($this->settings[$subname])) {
                        $settings->set($subname, $subvalue);
                    }
                }
            }
        }
    }

    /**
     * Add the taggings data to the resource JSON-LD.
     *
     * @param Event $event
     */
    public function filterResourceJsonLd(Event $event)
    {
        $resource = $event->getTarget();
        $jsonLd = $event->getParam('jsonLd');
        if (isset($this->cache['tags'][$resource->id()])) {
            $jsonLd['o-module-folksonomy:tag'] = array_values($this->cache['tags'][$resource->id()]);
        }
        if (isset($this->cache['taggings'][$resource->id()])) {
            $jsonLd['o-module-folksonomy:tagging'] = array_values($this->cache['taggings'][$resource->id()]);
        }
        $event->setParam('jsonLd', $jsonLd);
    }

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
            'folksonomy/common/admin/tagging-form.phtml'
        );
    }

    /**
     * Display the tagging form for public.
     *
     * @param Event $event
     */
    public function displayTaggingFormPublic(Event $event)
    {
        $view = $event->getTarget();
        $services = $this->getServiceLocator();
        $user = $services->get('Omeka\AuthenticationService')->getIdentity();
        $viewHelperManager = $services->get('ViewHelperManager');

        //$form = $services->get('FormElementManager')->get(TaggingForm::class);
        $form = new TaggingForm;
        $form->setSettingHelper($viewHelperManager->get('setting'));
        $form->setUrlHelper($viewHelperManager->get('Url'));
        $form->setFormElementManager($services->get('FormElementManager'));
        $form->setOptions([
            'site-slug' => $view->params()->fromRoute('site-slug'),
            'resource_id' => $view->vars()->resource->id(),
            'is_identified' => !empty($user),
        ]);
        $form->init();
        $view->vars()->offsetSet('taggingForm', $form);
        echo $view->partial('folksonomy/common/site/tagging-form.phtml');
    }

    /**
     * Display the tags for a resource.
     *
     * @param Event $event
     */
    public function displayViewResourceTags(Event $event)
    {
        $resource = $event->getTarget()->resource;
        $this->displayResourceFolksonomy($event, $resource);
    }

    /**
     * Display the tags for a resource.
     *
     * @param Event $event
     */
    public function displayViewResourceTagsPublic(Event $event)
    {
        $resource = $event->getTarget()->resource;
        $tags = $this->listResourceTags($resource);
        $taggings = $this->listResourceTaggings($resource);
        echo $event->getTarget()->partial(
            'folksonomy/common/site/tags-resource.phtml',
            [
                'resource' => $resource,
                'tags' => $tags,
                'taggings' => $taggings,
            ]
        );
    }

    /**
     * Display the tags for a resource.
     *
     * @param Event $event
     */
    public function displayViewEntityTags(Event $event)
    {
        $representation = $event->getParam('entity');
        $this->displayResourceFolksonomy($event, $representation);
    }

    /**
     * Helper to display the tags for a resource.
     *
     * @param Event $event
     * @param AbstractResourceRepresentation $resource
     */
    protected function displayResourceFolksonomy(Event $event, AbstractResourceRepresentation $resource)
    {
        $isViewDetails = $event->getName() == 'view.details';
        $tags = $this->listResourceTagsByName($resource);
        $taggings = $this->listResourceTaggingsByName($resource);
        $partial = $isViewDetails
            ? 'folksonomy/common/admin/tags-resource.phtml'
            : 'folksonomy/common/admin/tags-resource-list.phtml';
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
     * Helper to return tags of a resource.
     *
     * @param AbstractResourceRepresentation $resource
     * @return array
     */
    protected function listResourceTags(AbstractResourceRepresentation $resource)
    {
        $resourceJson = $resource->jsonSerialize();
        return empty($resourceJson['o-module-folksonomy:tag'])
            ? []
            : $resourceJson['o-module-folksonomy:tag'];
    }

    /**
     * Helper to return taggings of a resource.
     *
     * @param AbstractResourceRepresentation $resource
     * @return array
     */
    protected function listResourceTaggings(AbstractResourceRepresentation $resource)
    {
        $resourceJson = $resource->jsonSerialize();
        return empty($resourceJson['o-module-folksonomy:tagging'])
            ? []
            : $resourceJson['o-module-folksonomy:tagging'];
    }

    /**
     * Helper to return tags of a resource by name.
     *
     * @param AbstractResourceRepresentation $resource
     * @return array
     */
    protected function listResourceTagsByName(AbstractResourceRepresentation $resource)
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
     * @param AbstractResourceRepresentation $resource
     * @return array
     */
    protected function listResourceTaggingsByName(AbstractResourceRepresentation $resource)
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
     * @param AbstractResourceRepresentation $resource
     * @return array
     */
    protected function listFlatResourceTags(AbstractResourceRepresentation $resource)
    {
        $result = [];
        $tags = $this->listResourceTags($resource);
        foreach ($tags as $tag) {
            $result[$tag->internalId()] = $tag->name();
        }
        return $result;
    }

    public function displayAdvancedSearch(Event $event)
    {
        $services = $this->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $form = $formElementManager->get(SearchForm::class);
        $form->init();

        $view = $event->getTarget();
        $query = $event->getParam('query');
        $resourceType = $event->getParam('resourceType');

        $hasTags = !empty($query['has_tags']);
        $tags = array_key_exists('tag', $query)
            ? (is_array($query['tag']) ? implode(', ', $query['tag']) : $query['tag'])
            : '';

        $formData = [];
        $formData['has_tags'] = $hasTags;
        $formData['tag'] = $tags;
        $form->setData($formData);

        $vars = $event->getTarget()->vars();
        $vars->offsetSet('searchTagForm', $form);

        echo $event->getTarget()
            ->partial('folksonomy/common/advanced-search.phtml');
    }

    public function filterSearchFilters(Event $event)
    {
        $translate = $event->getTarget()->plugin('translate');
        $filters = $event->getParam('filters');
        $query = $event->getParam('query');
        if (!empty($query['has_tags'])) {
            $filterLabel = $translate('Has tags');
            $filterValue = $translate('true');
            $filters[$filterLabel][] = $filterValue;
        }
        if (!empty($query['tag'])) {
            $tags = is_array($query['tag']) ? $query['tag'] : explode(',', $query['tag']);
            $filterLabel = $translate('Tag');
            foreach ($tags as $tag) {
                $filters[$filterLabel][] = trim($tag);
            }
        }
        $event->setParam('filters', $filters);
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
            $qb->innerJoin(
                'Folksonomy\Entity\Tagging',
                $taggingAlias,
                'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq($taggingAlias . '.resource', $resourceAlias . '.id'),
                    $qb->expr()->isNotNull($taggingAlias . '.tag')
                )
            );
        }

        if (array_key_exists('tag', $query)) {
            $tags = is_array($query['tag']) ? $query['tag'] : [$query['tag']];
            $qb = $event->getParam('queryBuilder');
            $adapter = $event->getTarget();
            $resourceAlias = $adapter->getEntityClass();
            // All resources with any tag ("OR").
            // TODO The resquest is working, but it needs a format for querying.
            /*
            $qb
                ->innerJoin(
                    'Folksonomy\Entity\Tagging',
                    $taggingAlias,
                    'WITH',
                    $qb->expr()->eq( $taggingAlias . '.resource', $resourceAlias . '.id')
                )
                ->innerJoin(
                    'Folksonomy\Entity\Tag',
                    $tagAlias,
                    'WITH',
                    $qb->expr()->eq($tagAlias . '.id', $taggingAlias . '.tag')
                )
                ->andWhere($qb->expr()->in($tagAlias . '.name', $tags));
            */
            // All resources with all tag ("AND").
            foreach ($tags as $key => $tag) {
                $tagAlias = $adapter->createAlias();
                $taggingAlias = $adapter->createAlias();
                $qb
                    // Simulate a cross join, not managed by doctrine.
                    ->innerJoin(
                        'Folksonomy\Entity\Tag', $tagAlias,
                        'WITH', '1 = 1'
                    )
                    ->innerJoin(
                        'Folksonomy\Entity\Tagging',
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
     * Cache taggings and tags for resource API search/read.
     *
     * @internal The cache avoids self::filterItemJsonLd() to make multiple
     * queries to the database during one request.
     *
     * @param Event $event
     */
    public function cacheResourceTaggingData(Event $event)
    {
        $content = $event->getParam('response')->getContent();
        // Check if this is an api search or api read to get the list of ids.
        $resourceIds = is_array($content)
            ? array_map(function ($v) { return $v->getId(); }, $content)
            : [$content->getId()];
        if (empty($resourceIds)) {
            return;
        }

        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $entityManagerFilters = $services->get('Omeka\EntityManager')->getFilters();

        $entityManagerFilters->enable('tagging_visibility');
        $entityManagerFilters->getFilter('tagging_visibility')->setServiceLocator($services);
        $taggings = $api
            ->search('taggings', ['resource_id' => $resourceIds])
            ->getContent();
        $entityManagerFilters->disable('tagging_visibility');
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

        $submittedTags = $request->getValue('o-module-folksonomy:tag', []);
        // Normalized new tags if any.
        $newTags = array_filter(
            array_unique(
                array_map(
                    [$this, 'sanitizeString'],
                    explode(',', $request->getValue('o-module-folksonomy:tag-new', ''))
                )
            ),
            function ($v) { return strlen($v); }
        );

        // Updated resource.
        if ($resourceId) {
            $representation = $resourceAdapter->getRepresentation($resource);
            $resourceTags = $this->listResourceTags($representation);
            $currentTaggings = $this->listResourceTaggings($representation);
            $currentTags = array_map(function ($v) { return $v->name(); }, $resourceTags);
            $addedTags = array_diff($submittedTags, $currentTags);
            $unchangedTags = array_intersect($currentTags, $submittedTags);
            $deletedTags = array_diff($currentTags, $unchangedTags);
        }
        // Added resource.
        else {
            $representation = null;
            $resourceTags = [];
            $currentTaggings = [];
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
