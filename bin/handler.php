<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * MCP Server for php-class-diagram
 * 
 * Provides a JSON-RPC 2.0 interface to generate PlantUML class diagrams
 * from PHP source directories using the php-class-diagram tool.
 */
class PhpClassDiagramMCPServer
{
    private const TIMEOUT_SECONDS = 90;
    private const DEFAULT_ID = 1;
    
    private array $config;
    
    public function __construct()
    {
        $this->config = [
            'timeout' => self::TIMEOUT_SECONDS,
            'safe_mode' => true
        ];
    }
    
    /**
     * Main entry point for handling MCP requests
     */
    public function handleRequest(): void
    {
        try {
            $input = $this->readStdinInput();
            $request = $this->parseJsonRpcRequest($input);
            $response = $this->processRequest($request);
            $this->sendJsonRpcResponse($response, $request['id'] ?? self::DEFAULT_ID);
        } catch (Exception $e) {
            $this->sendJsonRpcError($e, self::DEFAULT_ID);
        }
    }
    
    /**
     * Read input from STDIN
     */
    private function readStdinInput(): string
    {
        $input = '';
        while (($line = fgets(STDIN)) !== false) {
            $input .= $line;
        }
        return trim($input);
    }
    
    /**
     * Parse and validate JSON-RPC 2.0 request
     */
    private function parseJsonRpcRequest(string $input): array
    {
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON input: ' . json_last_error_msg());
        }
        
        // Validate JSON-RPC 2.0 format
        if (!isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
            throw new InvalidArgumentException('Invalid JSON-RPC version. Expected 2.0');
        }
        
        if (!isset($data['method'])) {
            throw new InvalidArgumentException('Missing required field: method');
        }
        
        return $data;
    }
    
    /**
     * Process the incoming request based on method
     */
    private function processRequest(array $request): array
    {
        $method = $request['method'];
        $params = $request['params'] ?? [];
        
        switch ($method) {
            case 'tools/list':
                return $this->listAvailableTools();
            case 'tools/call':
                return $this->callTool($params);
            default:
                throw new InvalidArgumentException("Unknown method: {$method}");
        }
    }
    
    /**
     * Handle tool call request
     */
    private function callTool(array $params): array
    {
        if (!isset($params['name'])) {
            throw new InvalidArgumentException('Missing required parameter: name');
        }
        
        $toolName = $params['name'];
        $arguments = $params['arguments'] ?? [];
        
        switch ($toolName) {
            case 'generate_class_diagram':
                return $this->generateClassDiagram($arguments);
            default:
                throw new InvalidArgumentException("Unknown tool: {$toolName}");
        }
    }
    
    /**
     * Generate PlantUML class diagram using php-class-diagram
     */
    private function generateClassDiagram(array $params): array
    {
        $this->validateParams($params);
        
        $directory = $params['directory'];
        $exclude = $params['exclude'] ?? [];
        $depth = $params['depth'] ?? 0;
        
        // Build command arguments for php-class-diagram
        $command = $this->buildCommand($directory, $exclude, $depth);
        
        // Execute php-class-diagram as child process
        $process = new Process($command);
        $process->setTimeout($this->config['timeout']);
        
        try {
            $process->mustRun();
            $plantumlOutput = $process->getOutput();
            
            // Validate output
            if (empty(trim($plantumlOutput))) {
                throw new RuntimeException('php-class-diagram produced empty output');
            }
            
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $plantumlOutput
                    ]
                ]
            ];
        } catch (ProcessFailedException $e) {
            $errorOutput = $process->getErrorOutput();
            throw new RuntimeException(
                'php-class-diagram execution failed: ' . $e->getMessage() . 
                ($errorOutput ? "\nError output: " . $errorOutput : '')
            );
        }
    }
    
    /**
     * Validate input parameters
     */
    private function validateParams(array $params): void
    {
        if (!isset($params['directory'])) {
            throw new InvalidArgumentException('directory parameter is required');
        }
        
        $directory = $params['directory'];
        if (!is_string($directory)) {
            throw new InvalidArgumentException('directory must be a string');
        }
        
        // Resolve relative paths
        if (!path_is_absolute($directory)) {
            $directory = getcwd() . DIRECTORY_SEPARATOR . ltrim($directory, DIRECTORY_SEPARATOR);
        }
        
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("directory does not exist: {$directory}");
        }
        
        if (!is_readable($directory)) {
            throw new InvalidArgumentException("directory is not readable: {$directory}");
        }
        
        // Update params with resolved path
        $params['directory'] = $directory;
        
        // Validate exclude patterns if provided
        if (isset($params['exclude']) && !is_array($params['exclude'])) {
            throw new InvalidArgumentException('exclude must be an array');
        }
        
        // Validate depth if provided
        if (isset($params['depth'])) {
            if (!is_int($params['depth']) || $params['depth'] < 0) {
                throw new InvalidArgumentException('depth must be a non-negative integer');
            }
        }
    }
    
    /**
     * Build command arguments for php-class-diagram
     */
    private function buildCommand(string $directory, array $exclude, int $depth): array
    {
        // Use the vendor binary path
        $binary = __DIR__ . '/../vendor/bin/php-class-diagram';
        
        // Fallback to global installation
        if (!file_exists($binary)) {
            $binary = 'php-class-diagram';
        }
        
        $command = [$binary];
        
        // Add directory argument
        $command[] = $directory;
        
        // Add exclude patterns
        foreach ($exclude as $pattern) {
            if (is_string($pattern) && !empty($pattern)) {
                $command[] = '--exclude=' . $pattern;
            }
        }
        
        // Add depth limitation (only if greater than 0)
        if ($depth > 0) {
            $command[] = '--depth';
            $command[] = (string)$depth;
        }
        
        return $command;
    }
    
    /**
     * List available tools
     */
    private function listAvailableTools(): array
    {
        return [
            'tools' => [
                [
                    'name' => 'generate_class_diagram',
                    'description' => 'Generate PlantUML class diagram from PHP source directories (read-only). Intended for LLM agents to analyse structure, detect design smells, or document architecture.',
                    'inputSchema' => [
                        'type' => 'object',
                        'required' => ['directory'],
                        'properties' => [
                            'directory' => [
                                'type' => 'string',
                                'description' => 'Absolute or workspace-relative path to the PHP project root to analyse.'
                            ],
                            'exclude' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Optional glob patterns to exclude paths.'
                            ],
                            'depth' => [
                                'type' => 'integer',
                                'minimum' => 0,
                                'description' => 'Optional maximum nesting depth (0 = unlimited).'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Send JSON-RPC 2.0 success response
     */
    private function sendJsonRpcResponse(array $result, $id): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'result' => $result,
            'id' => $id
        ];
        
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Send JSON-RPC 2.0 error response
     */
    private function sendJsonRpcError(Exception $e, $id): void
    {
        // Map exception types to JSON-RPC error codes
        $code = match (get_class($e)) {
            'InvalidArgumentException' => -32602, // Invalid params
            'RuntimeException' => -32603,         // Internal error
            default => -32603                     // Internal error
        };
        
        $error = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => $e->getMessage(),
                'data' => [
                    'type' => get_class($e),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ],
            'id' => $id
        ];
        
        echo json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Helper function to check if path is absolute
 */
function path_is_absolute(string $path): bool
{
    return $path[0] === DIRECTORY_SEPARATOR || 
           (PHP_OS_FAMILY === 'Windows' && preg_match('/^[A-Za-z]:/', $path));
}

// Entry point - only run when executed directly
if (php_sapi_name() === 'cli' && basename($_SERVER['SCRIPT_NAME']) === 'handler.php') {
    $server = new PhpClassDiagramMCPServer();
    $server->handleRequest();
} else {
    // Not CLI or not the main script
    if (php_sapi_name() !== 'cli') {
        http_response_code(400);
        echo json_encode(['error' => 'This script must be run from command line']);
    }
}
