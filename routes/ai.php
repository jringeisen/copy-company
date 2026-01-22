<?php

use App\Http\Middleware\SetPermissionsTeamContext;
use App\Mcp\Servers\CopyCompanyServer;
use Laravel\Mcp\Facades\Mcp;

// Register OAuth 2.1 discovery and client registration routes
Mcp::oauthRoutes();

// Web server with OAuth authentication (for Claude Desktop browser flow)
Mcp::web('/mcp/copy-company', CopyCompanyServer::class)
    ->middleware(['auth:api', SetPermissionsTeamContext::class]);

// Local server for Claude Desktop (token-based via MCP_API_TOKEN env var)
Mcp::local('copy-company', CopyCompanyServer::class);
