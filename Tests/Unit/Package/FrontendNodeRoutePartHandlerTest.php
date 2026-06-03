<?php

declare(strict_types=1);

namespace Breadlesscode\NodeTypes\Folder\Tests\Unit\Package;

use Breadlesscode\NodeTypes\Folder\Package\FrontendNodeRoutePartHandler;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;

#[AllowMockObjectsWithoutExpectations]
class FrontendNodeRoutePartHandlerTest extends UnitTestCase
{
    private ExposedFrontendNodeRoutePartHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ExposedFrontendNodeRoutePartHandler();
    }

    #[Test]
    public function shouldHideNodeUriSegmentReturnsTrueWhenPropertyIsTrue(): void
    {
        $node = $this->createMock(NodeInterface::class);
        $node->method('hasProperty')->with('hideSegmentInUriPath')->willReturn(true);
        $node->method('getProperty')->with('hideSegmentInUriPath')->willReturn(true);

        self::assertTrue($this->subject->exposesShouldHideNodeUriSegment($node));
    }

    #[Test]
    public function shouldHideNodeUriSegmentReturnsFalseWhenPropertyIsFalse(): void
    {
        $node = $this->createMock(NodeInterface::class);
        $node->method('hasProperty')->with('hideSegmentInUriPath')->willReturn(true);
        $node->method('getProperty')->with('hideSegmentInUriPath')->willReturn(false);

        self::assertFalse($this->subject->exposesShouldHideNodeUriSegment($node));
    }

    #[Test]
    public function shouldHideNodeUriSegmentReturnsFalseWhenPropertyAbsent(): void
    {
        $node = $this->createMock(NodeInterface::class);
        $node->method('hasProperty')->with('hideSegmentInUriPath')->willReturn(false);

        self::assertFalse($this->subject->exposesShouldHideNodeUriSegment($node));
    }

    #[Test]
    public function hiddenNodeDoesNotMatchItsOwnUriPathSegment(): void
    {
        $hiddenFolder = $this->createMock(NodeInterface::class);
        $hiddenFolder->method('hasProperty')->willReturnMap([
            ['hideSegmentInUriPath', true],
            ['uriPathSegment', true],
        ]);
        $hiddenFolder->method('getProperty')->willReturnMap([
            ['hideSegmentInUriPath', true],
            ['uriPathSegment', 'hidden-folder'],
        ]);
        $hiddenFolder->method('getChildNodes')->willReturn([]);

        $siteNode = $this->createMock(NodeInterface::class);
        $siteNode->method('getChildNodes')->willReturn([$hiddenFolder]);

        $result = $this->subject->exposesGetRelativeNodePathByUriPathSegmentProperties($siteNode, 'hidden-folder');

        self::assertFalse($result);
    }

    #[Test]
    public function childBeneathHiddenNodeMatchesViaRecursion(): void
    {
        $child = $this->createMock(NodeInterface::class);
        $child->method('hasProperty')->willReturnMap([
            ['hideSegmentInUriPath', false],
            ['uriPathSegment', true],
        ]);
        $child->method('getProperty')->willReturnMap([
            ['hideSegmentInUriPath', false],
            ['uriPathSegment', 'child-page'],
        ]);
        $child->method('getName')->willReturn('child-page-node');
        $child->method('getChildNodes')->willReturn([]);

        $hiddenFolder = $this->createMock(NodeInterface::class);
        $hiddenFolder->method('hasProperty')->willReturnMap([
            ['hideSegmentInUriPath', true],
        ]);
        $hiddenFolder->method('getProperty')->willReturnMap([
            ['hideSegmentInUriPath', true],
        ]);
        $hiddenFolder->method('getName')->willReturn('hidden-folder-node');
        $hiddenFolder->method('getChildNodes')->willReturn([$child]);

        $siteNode = $this->createMock(NodeInterface::class);
        $siteNode->method('getChildNodes')->willReturn([$hiddenFolder]);

        $result = $this->subject->exposesGetRelativeNodePathByUriPathSegmentProperties($siteNode, 'child-page');

        self::assertIsString($result);
        self::assertStringContainsString('child-page-node', $result);
    }
}

/**
 * Exposes protected methods for testing without reflection.
 */
class ExposedFrontendNodeRoutePartHandler extends FrontendNodeRoutePartHandler
{
    public function exposesShouldHideNodeUriSegment(NodeInterface $node): bool
    {
        return $this->shouldHideNodeUriSegment($node);
    }

    public function exposesGetRelativeNodePathByUriPathSegmentProperties(NodeInterface $siteNode, string $relativeRequestPath): string|false
    {
        return $this->getRelativeNodePathByUriPathSegmentProperties($siteNode, $relativeRequestPath);
    }
}
