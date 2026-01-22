<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\BrandResource;
use App\Mcp\Tools\ContentSprints\AcceptSprintIdeasTool;
use App\Mcp\Tools\ContentSprints\GetSprintTool;
use App\Mcp\Tools\ContentSprints\ListSprintsTool;
use App\Mcp\Tools\ContentSprints\TriggerContentSprintTool;
use App\Mcp\Tools\Posts\CreatePostTool;
use App\Mcp\Tools\Posts\GetPostTool;
use App\Mcp\Tools\Posts\ListPostsTool;
use App\Mcp\Tools\Posts\PublishPostTool;
use App\Mcp\Tools\Posts\UpdatePostTool;
use Laravel\Mcp\Server;

class CopyCompanyServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Copy Company';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        Copy Company is a content platform for creators to manage blogs, newsletters, and social media.

        ## Key Concepts
        - **Brand**: The central entity. All content belongs to a brand.
        - **Post**: Blog posts with TipTap JSON content. Can be published to blog and/or sent as newsletter.
        - **Content Sprint**: AI-generated content ideas that can be converted to draft posts.

        ## Workflow Tips
        1. Use the brand resource to understand the current brand context.
        2. Use content sprints to generate post ideas based on topics.
        3. Accept sprint ideas to convert them to draft posts.
        4. Edit drafts with UpdatePostTool to refine content.
        5. Use PublishPostTool to publish or schedule posts.

        ## Post Content Format
        Posts use TipTap JSON format for content. When creating/updating posts, you can provide:
        - `content`: TipTap JSON structure (array)
        - `content_html`: Pre-rendered HTML version

        ## Status Values
        - Posts: draft, scheduled, published, archived
        - Sprints: pending, generating, completed, failed
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        // Post Tools
        ListPostsTool::class,
        GetPostTool::class,
        CreatePostTool::class,
        UpdatePostTool::class,
        PublishPostTool::class,
        // Content Sprint Tools
        TriggerContentSprintTool::class,
        ListSprintsTool::class,
        GetSprintTool::class,
        AcceptSprintIdeasTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        BrandResource::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
