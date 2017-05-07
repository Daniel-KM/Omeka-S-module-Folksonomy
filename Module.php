<?php
namespace Folksonomy;

use Omeka\Module\AbstractModule;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;
use Folksonomy\Form\Config as ConfigForm;
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
     * @var array Cache of taggings and tags.
     */
    protected $cache = ['taggings' => [], 'tags' => []];

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

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // Add the tagging form to the item add and edit pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.after',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/admin/tagging-form.phtml');
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/admin/tagging-form.phtml');
            }
        );
        // Add the tagging form to the item set add and edit pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.add.form.after',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/admin/tagging-form.phtml');
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.edit.form.after',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/admin/tagging-form.phtml');
            }
        );
        // Add the tagging and tag to the item show pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/admin/show.phtml');
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/site/show.phtml');
            }
        );
        // Add the tagging and tag to the item set show pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\ItemSet',
            'view.show.after',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/admin/show.phtml');
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\ItemSet',
            'view.show.after',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/site/show.phtml');
            }
        );
        // Add the tag field to the site's browse page.
        $sharedEventManager->attach(
            'Folksonomy\Controller\Site\Index',
            'view.advanced_search',
            function (Event $event) {
                echo $event->getTarget()->partial('folksonomy/common/advanced-search.phtml');
            }
        );
        // Add the "has_tags" filter to item search.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            function (Event $event) {
                $query = $event->getParam('request')->getContent();
                if (isset($query['has_tags'])) {
                    $qb = $event->getParam('queryBuilder');
                    $itemAdapter = $event->getTarget();
                    $tagAlias = $itemAdapter->createAlias();
                    $itemAlias = $itemAdapter->getEntityClass();
                    $qb->innerJoin(
                        'Folksonomy\Entity\Tag', $tagAlias,
                        'WITH', "$tagAlias.resource_id = $itemAlias.id"
                    );
                }
            }
        );
        // Add the "has_tags" filter to item set search.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.search.query',
            function (Event $event) {
                $query = $event->getParam('request')->getContent();
                if (isset($query['has_tags'])) {
                    $qb = $event->getParam('queryBuilder');
                    $itemSetAdapter = $event->getTarget();
                    $tagAlias = $itemSetAdapter->createAlias();
                    $itemSetAlias = $itemSetAdapter->getEntityClass();
                    $qb->innerJoin(
                        'Folksonomy\Entity\Tag', $tagAlias,
                        'WITH', "$tagAlias.resource_id = $itemSetAlias.id"
                        );
                }
            }
        );
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
            'view.show.section_nav',
            [$this, 'displayResourceTags']
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
            'view.show.section_nav',
            [$this, 'displayResourceTags']
        );

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
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleTagging']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleTags']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.hydrate.post',
            [$this, 'handleTagging']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.hydrate.post',
            [$this, 'handleTags']
        );
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
     * Add ACL rules for this module.
     *
     * @todo To be finalized.
     */
    protected function addAclRules()
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        // Similar than items or item sets from Omeka\Service\AclFactory.
        $acl->allow(
            null,
            [
                'Folksonomy\Controller\Admin\Tagging',
                'Folksonomy\Controller\Site\Tagging',
                'Folksonomy\Controller\Tagging',
            ]
        );
        $acl->allow(
            null,
            'Folksonomy\Api\Adapter\TaggingAdapter',
            [
                'search',
                'read',
            ]
        );
        $acl->allow(
            null,
            'Folksonomy\Entity\Tagging',
            'read'
        );

        $acl->allow(
            'researcher',
            'Folksonomy\Controller\Admin\Tagging',
            [
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
            'author',
            'Folksonomy\Api\Adapter\TaggingAdapter',
            [
                'create',
                'update',
                'delete',
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
            'author',
            'Folksonomy\Entity\Tagging',
            [
                'update',
                'delete',
            ],
            new OwnsEntityAssertion
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
            ]
        );
        $acl->allow(
            'reviewer',
            'Folksonomy\Entity\Tagging',
            [
                'delete',
            ],
            new OwnsEntityAssertion
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
            ]
        );
    }

    /**
     * Add the tagging tab to section navigations.
     *
     * Event $event
     */
    public function addTaggingTab(Event $event)
    {
        $sectionNav = $event->getParam('section_nav');
        $sectionNav['tagging-section'] = 'Tagging';
        $event->setParam('section_nav', $sectionNav);
    }

    /**
     * Display the tags for a resource.
     *
     * Event $event
     */
    public function displayResourceTags(Event $event)
    {
        // Don't render the tagging tab if there is no tagging data.
        $resourceJson = $event->getParam('resource')->jsonSerialize();
        if (!isset($resourceJson['o-module-folksonomy:tag'])) {
            return;
        }

        $services = $this->getServiceLocator();
        $translator = $services->get('MvcTranslator');
        $getResourceTags = $services->get('ViewHelperManager')
            ->get('getResourceTags');
        $tags = $getResourceTags($resource);

        echo '<div class="property meta-group"><h4>'
            . $translator->translate('Tags')
            . '</h4><div class="value">'
            . ($tags ?: '<em>' . $translator->translate('[none]') . '</em>')
            . '</div></div>';
    }

    /**
     * Cache taggings and tags for item and item set API search/read.
     *
     * @internal The cache avoids self::filterItemJsonLd() to make multiple
     * queries to the database during one request.
     *
     * Event $event
     */
    public function cacheResourceTaggingData(Event $event)
    {
        $resourceIds = [];
        $content = $event->getParam('response')->getContent();
        if (is_array($content)) {
            // This is an API search.
            foreach ($content as $resource) {
                $resourceIds[] = $resource->getId();
            }
        } else {
            // This is an API read.
            $resourceIds[] = $content->getId();
        }
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        // Cache taggings.
        $response = $api->search('taggings', ['resource_id' => $resourceIds]);
        foreach ($response->getContent() as $tagging) {
            $this->cache['taggings'][$tagging->resource_id()][] = $tagging;
        }
        // Cache tags.
        $response = $api->search('tags', ['resource_id' => $resourceIds]);
        foreach ($response->getContent() as $tag) {
            $this->cache['tags'][$tag->resource_id()][] = $tag;
        }
    }

    /**
     * Add the taggings and tags data to the resource JSON-LD.
     *
     * Event $event
     */
    public function filterResourceJsonLd(Event $event)
    {
        $resource = $event->getTarget();
        $jsonLd = $event->getParam('jsonLd');
        if (isset($this->cache['taggings'][$resource->id()])) {
            $jsonLd['o-module-folksonomy:tagging'] = $this->cache['taggings'][$resource->id()];
        }
        if (isset($this->cache['tags'][$resource->id()])) {
            $jsonLd['o-module-folksonomy:tag'] = $this->cache['tags'][$resource->id()];
        }
        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Handle hydration for tagging data.
     *
     * @param Event $event
     */
    public function handleTagging(Event $event)
    {
        $resourceAdapter = $event->getTarget();
        $request = $event->getParam('request');

        if (!$resourceAdapter->shouldHydrate($request, 'o-module-folksonomy:tagging')) {
            return;
        }

        $taggingAdapter = $resourceAdapter->getAdapter('taggings');
        $taggingData = $request->getValue('o-module-folksonomy:tagging', []);

        $taggingId = null;
        $tags = null;

        if (isset($taggingData['o:id']) && is_numeric($taggingData['o:id'])) {
            $taggingId = $taggingData['o:id'];
        }
        if (isset($taggingData['o-module-folksonomy:tag'])
            && '' !== trim($taggingData['o-module-folksonomy:tag'])
        ) {
            $tags = $taggingData['o-module-folksonomy:tag'];
        }

        if (null === $tags) {
            // This request has no tagging data. If a tagging for this resource
            // exists, delete it. If no tagging for this resource exists, do nothing.
            if (null !== $taggingId) {
                // Delete tagging
                $subRequest = new \Omeka\Api\Request('delete', 'taggings');
                $subRequest->setId($taggingId);
                $taggingsAdapter->deleteEntity($subRequest);
            }
        } else {
            // This request has tagging data. If a tagging for this resource exists,
            // update it. If no tagging for this resource exists, create it.
            if ($taggingId) {
                // Update tagging
                $subRequest = new \Omeka\Api\Request('update', 'taggings');
                $subRequest->setId($taggingData['o:id']);
                $subRequest->setContent($taggingData);
                $tagging = $taggingsAdapter->findEntity($taggingData['o:id'], $subRequest);
                $taggingsAdapter->hydrateEntity($subRequest, $tagging, new \Omeka\Stdlib\ErrorStore);
            } else {
                // Create tagging
                $subRequest = new \Omeka\Api\Request('create', 'taggings');
                $subRequest->setContent($taggingData);
                $tagging = new \Folksonomy\Entity\Tagging;
                $tagging->setItem($event->getParam('entity'));
                $taggingsAdapter->hydrateEntity($subRequest, $tagging, new \Omeka\Stdlib\ErrorStore);
                $taggingsAdapter->getEntityManager()->persist($tagging);
            }
        }
    }

    /**
     * Handle hydration for tags data.
     *
     * @param Event $event
     */
    public function handleTags(Event $event)
    {
        $resourceAdapter = $event->getTarget();
        $request = $event->getParam('request');

        if (!$resourceAdapter->shouldHydrate($request, 'o-module-folksonomy:tag')) {
            return;
        }

        $resource = $event->getParam('entity');
        $entityManager = $resourceAdapter->getEntityManager();
        $tagAdapter = $resourceAdapter->getAdapter('tag');
        $retainTagIds = [];

        // Create/update tags passed in the request.
        foreach ($request->getValue('o-module-folksonomy:tag', []) as $tagData) {
            if (isset($tagData['o:id'])) {
                $subRequest = new \Omeka\Api\Request('update', 'tags');
                $subRequest->setId($tagData['o:id']);
                $subRequest->setContent($tagData);
                $tag = $tagAdapter->findEntity($tagData['o:id'], $subRequest);
                $tagAdapter->hydrateEntity($subRequest, $tag, new \Omeka\Stdlib\ErrorStore);
                $retainTagIds[] = $tag->getId();
            } else {
                $subRequest = new \Omeka\Api\Request('create', 'tags');
                $subRequest->setContent($tagData);
                $tag = new \Folksonomy\Entity\Tag;
                $tag->setResource($resource);
                $tagAdapter->hydrateEntity($subRequest, $tag, new \Omeka\Stdlib\ErrorStore);
                $entityManager->persist($tag);
            }
        }

        // Delete existing tags not passed in the request.
        $existingTags = [];
        if ($resource->getId()) {
            $dql = 'SELECT tags FROM Folksonomy\Entity\Tag tags INDEX BY tags.id WHERE tags.resource_id = ?1';
            $query = $entityManager->createQuery($dql)->setParameter(1, $resource->getId());
            $existingTags = $query->getResult();
        }
        foreach ($existingTags as $existingTagId => $existingTag) {
            if (!in_array($existingTagId, $retainTagIds)) {
                $entityManager->remove($existingTag);
            }
        }
    }
}
