<?php

use App\Http\Middleware\SetPermissionsTeamContext;
use App\Mcp\Servers\CopyCompanyServer;
use Laravel\Mcp\Facades\Mcp;

// Web server for API clients (Claude Code, etc.)
Mcp::web('/mcp/copy-company', CopyCompanyServer::class)
    ->middleware(['auth:sanctum', SetPermissionsTeamContext::class]);

// Local server for Claude Desktop
Mcp::local('copy-company', CopyCompanyServer::class);
