<?php

namespace CultuurNet\ProjectAanvraag\ArticleLinker\EventListener;

use CultuurNet\ProjectAanvraag\ArticleLinker\ArticleLinkerClientInterface;
use CultuurNet\ProjectAanvraag\ArticleLinker\Event\ArticleLinkCreated;
use CultuurNet\ProjectAanvraag\Entity\Cache;
use Symfony\Component\Cache\Simple\DoctrineCache;

class ArticleLinkCreatedEventListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test the event listener handler with an existing / expired cache entity.
     */
    public function testExistingHandle()
    {

        $articleLinkerClient = $this
            ->getMockBuilder(ArticleLinkerClientInterface::class)
            ->getMock();
        $cacheBackend = $this
            ->getMockBuilder(DoctrineCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventListener = new ArticleLinkCreatedEventListener(
            $articleLinkerClient,
            $cacheBackend
        );

        $cacheBackend->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $articleLinkCreated = new ArticleLinkCreated('my-url', 'my-cdbid');
        $eventListener->handle($articleLinkCreated);
    }

    /**
     * Test the handler when a cache entity was not found.
     */
    public function testNonExistingHandle()
    {
        $articleLinkerClient = $this
            ->getMockBuilder(ArticleLinkerClientInterface::class)
            ->getMock();
        $cacheBackend = $this
            ->getMockBuilder(DoctrineCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventListener = new ArticleLinkCreatedEventListener(
            $articleLinkerClient,
            $cacheBackend
        );

        $cacheBackend->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $articleLinkerClient->expects($this->once())
            ->method('linkArticle')
            ->with('my-url', 'my-cdbid');

        $articleLinkCreated = new ArticleLinkCreated('my-url', 'my-cdbid');
        $eventListener->handle($articleLinkCreated);
    }

    /**
     * Test the event listener handler without cache.
     */
    public function testHandleNoCache()
    {
        $articleLinkerClient = $this
            ->getMockBuilder(ArticleLinkerClientInterface::class)
            ->getMock();

        $eventListener = new ArticleLinkCreatedEventListener(
            $articleLinkerClient
        );

        $articleLinkerClient->expects($this->once())
            ->method('linkArticle')
            ->with('my-url', 'my-cdbid');

        $articleLinkCreated = new ArticleLinkCreated('my-url', 'my-cdbid');
        $eventListener->handle($articleLinkCreated);
    }
}
