<?php

require_once __DIR__ . '/../single-file-unit-test.php';
require_once __DIR__ . '/../../bin/handler.php';

use Smeghead\SingleFileUnitTest\TestCase;

class PhpClassDiagramMCPTest extends TestCase {
    
    public function testToolsListReturnsCorrectFormat() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1
        ];
        
        // Act
        $response = $this->invokePrivateMethod($server, 'processRequest', [$request]);
        
        // Assert
        $this->assertSame(true, isset($response['tools']), 'Response should contain tools array');
        $this->assertSame(true, is_array($response['tools']), 'Tools should be an array');
        $this->assertSame(1, count($response['tools']), 'Should have exactly one tool');
        
        $tool = $response['tools'][0];
        $this->assertSame('generate_class_diagram', $tool['name'], 'Tool name should be generate_class_diagram');
        $this->assertSame(true, isset($tool['description']), 'Tool should have description');
        $this->assertSame(true, isset($tool['inputSchema']), 'Tool should have inputSchema');
    }
    
    public function testToolCallWithValidDirectory() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $testDir = __DIR__ . '/../fixtures/exclude-patterns-test';
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'generate_class_diagram',
                'arguments' => [
                    'directory' => $testDir
                ]
            ],
            'id' => 2
        ];
        
        // Act
        $response = $this->invokePrivateMethod($server, 'processRequest', [$request]);
        
        // Assert
        $this->assertSame(true, isset($response['content']), 'Response should contain content');
        $this->assertSame(true, is_array($response['content']), 'Content should be an array');
        $this->assertSame(1, count($response['content']), 'Content should have one item');
        
        $content = $response['content'][0];
        $this->assertSame('text', $content['type'], 'Content type should be text');
        $this->assertSame(true, isset($content['text']), 'Content should have text field');
        $this->assertSame(true, strpos($content['text'], '@startuml') !== false, 'Output should contain PlantUML start tag');
        $this->assertSame(true, strpos($content['text'], '@enduml') !== false, 'Output should contain PlantUML end tag');
    }
    
    public function testInvalidDirectoryThrowsException() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $invalidDir = '/nonexistent/directory';
        
        // Act & Assert
        $this->expectExceptionMessage('directory does not exist');
        $this->invokePrivateMethod($server, 'validateParams', [[
            'directory' => $invalidDir
        ]]);
    }
    
    public function testMissingDirectoryParameterThrowsException() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $params = []; // Missing directory parameter
        
        // Act & Assert
        $this->expectExceptionMessage('directory parameter is required');
        $this->invokePrivateMethod($server, 'validateParams', [$params]);
    }
    
    public function testBuildCommandWithBasicParameters() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $directory = '/test/directory';
        $exclude = [];
        $depth = 0;
        
        // Act
        $command = $this->invokePrivateMethod($server, 'buildCommand', [$directory, $exclude, $depth]);
        
        // Assert
        $this->assertSame(true, is_array($command), 'Command should be an array');
        $this->assertSame(true, count($command) >= 2, 'Command should have at least binary and directory');
        $this->assertSame($directory, $command[1], 'Second argument should be the directory');
    }
    
    public function testBuildCommandWithExcludePatterns() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $directory = '/test/directory';
        $exclude = ['*.test.php'];
        $depth = 0;
        
        // Act
        $command = $this->invokePrivateMethod($server, 'buildCommand', [$directory, $exclude, $depth]);
        
        // Assert
        $this->assertSame(true, in_array('--exclude=*.test.php', $command), 'Command should contain exclude pattern');
    }
    
    public function testBuildCommandWithDepthParameter() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $directory = '/test/directory';
        $exclude = [];
        $depth = 3;
        
        // Act
        $command = $this->invokePrivateMethod($server, 'buildCommand', [$directory, $exclude, $depth]);
        
        // Assert
        $this->assertSame(true, in_array('--depth', $command), 'Command should contain depth option');
        $this->assertSame(true, in_array('3', $command), 'Command should contain depth value');
    }
    
    public function testJsonRpcRequestParsing() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $validJson = '{"jsonrpc":"2.0","method":"tools/list","id":1}';
        
        // Act
        $request = $this->invokePrivateMethod($server, 'parseJsonRpcRequest', [$validJson]);
        
        // Assert
        $this->assertSame('2.0', $request['jsonrpc'], 'Should parse jsonrpc version');
        $this->assertSame('tools/list', $request['method'], 'Should parse method');
        $this->assertSame(1, $request['id'], 'Should parse id');
    }
    
    public function testInvalidJsonThrowsException() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $invalidJson = '{"invalid": json}';
        
        // Act & Assert
        $this->expectExceptionMessage('Invalid JSON input');
        $this->invokePrivateMethod($server, 'parseJsonRpcRequest', [$invalidJson]);
    }
    
    public function testInvalidJsonRpcVersionThrowsException() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $invalidVersionJson = '{"jsonrpc":"1.0","method":"test","id":1}';
        
        // Act & Assert
        $this->expectExceptionMessage('Invalid JSON-RPC version');
        $this->invokePrivateMethod($server, 'parseJsonRpcRequest', [$invalidVersionJson]);
    }
    
    public function testUnknownMethodThrowsException() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'unknown/method',
            'id' => 1
        ];
        
        // Act & Assert
        $this->expectExceptionMessage('Unknown method: unknown/method');
        $this->invokePrivateMethod($server, 'processRequest', [$request]);
    }
    
    public function testUnknownToolThrowsException() {
        // Arrange
        $server = new PhpClassDiagramMCPServer();
        $params = [
            'name' => 'unknown_tool',
            'arguments' => []
        ];
        
        // Act & Assert
        $this->expectExceptionMessage('Unknown tool: unknown_tool');
        $this->invokePrivateMethod($server, 'callTool', [$params]);
    }
    
    public function testJsonlProcessing() {
        // Test JSONL processing with buffered messages
        $server = new PhpClassDiagramMCPServer();
        
        // Create a test buffer with multiple JSON messages
        $buffer = '{"jsonrpc":"2.0","method":"tools/list","id":1}' . "\n" .
                  '{"jsonrpc":"2.0","method":"tools/list","id":2}' . "\n";
        
        // Capture output
        ob_start();
        $this->invokePrivateMethod($server, 'processBufferedMessages', [&$buffer]);
        $output = ob_get_clean();
        
        // Should process both messages and send responses
        $this->assertSame(true, strpos($output, '"id":1') !== false, 'Should process first message');
        $this->assertSame(true, strpos($output, '"id":2') !== false, 'Should process second message');
        
        // Buffer should be empty after processing complete lines
        $this->assertSame('', $buffer, 'Buffer should be empty after processing complete messages');
    }
    
    public function testPartialJsonlBuffering() {
        // Test that incomplete JSON messages remain in buffer
        $server = new PhpClassDiagramMCPServer();
        
        // Create a buffer with incomplete message
        $buffer = '{"jsonrpc":"2.0","method":"tools/list","id":1}' . "\n" .
                  '{"jsonrpc":"2.0","method":"tools/list"';  // incomplete
        
        // Capture output
        ob_start();
        $this->invokePrivateMethod($server, 'processBufferedMessages', [&$buffer]);
        $output = ob_get_clean();
        
        // Should process complete message only
        $this->assertSame(true, strpos($output, '"id":1') !== false, 'Should process complete message');
        
        // Incomplete message should remain in buffer
        $this->assertSame('{"jsonrpc":"2.0","method":"tools/list"', $buffer, 'Incomplete message should remain in buffer');
    }
    
    public function testJsonlRegressionTestWithFixtures() {
        // Integration test using fixtures to prevent regression
        $jsonlInputFile = __DIR__ . '/../fixtures/test-jsonl-input.txt';
        $this->assertSame(true, file_exists($jsonlInputFile), 'JSONL test fixture must exist');
        
        // Read the JSONL input
        $jsonlInput = file_get_contents($jsonlInputFile);
        $this->assertSame(true, !empty($jsonlInput), 'JSONL input must not be empty');
        
        // Execute the MCP server with the JSONL input
        $command = 'cd ' . dirname(__DIR__, 2) . ' && echo ' . escapeshellarg($jsonlInput) . ' | php bin/handler.php';
        $output = shell_exec($command);
        
        // Parse the output lines
        $lines = array_filter(explode("\n", trim($output)));
        $this->assertSame(3, count($lines), 'Should return exactly 3 responses');
        
        // Verify each response
        $responses = array_map('json_decode', $lines);
        
        // Response 1: Initialize
        $this->assertSame('2.0', $responses[0]->jsonrpc, 'First response should be valid JSON-RPC 2.0');
        $this->assertSame(1, $responses[0]->id, 'First response should have ID 1');
        $this->assertSame('2024-11-05', $responses[0]->result->protocolVersion, 'Should return correct protocol version');
        
        // Response 2: Tools list
        $this->assertSame(2, $responses[1]->id, 'Second response should have ID 2');
        $this->assertSame(true, isset($responses[1]->result->tools), 'Should contain tools list');
        $this->assertSame('generate_class_diagram', $responses[1]->result->tools[0]->name, 'Should contain class diagram tool');
        
        // Response 3: Class diagram generation
        $this->assertSame(3, $responses[2]->id, 'Third response should have ID 3');
        $this->assertSame(true, isset($responses[2]->result->content), 'Should contain diagram content');
        $this->assertSame('text', $responses[2]->result->content[0]->type, 'Content should be text type');
        $this->assertSame(true, strpos($responses[2]->result->content[0]->text, '@startuml') !== false, 'Should contain PlantUML start tag');
    }
    
    public function testFixtureTestProjectExists() {
        // Ensure test project fixture exists for regression testing
        $testProjectDir = __DIR__ . '/../fixtures/jsonl-integration-test';
        $this->assertSame(true, is_dir($testProjectDir), 'JSONL integration test fixture directory must exist');
        
        $userFile = $testProjectDir . '/User.php';
        $repoFile = $testProjectDir . '/UserRepository.php';
        
        $this->assertSame(true, file_exists($userFile), 'User.php fixture must exist');
        $this->assertSame(true, file_exists($repoFile), 'UserRepository.php fixture must exist');
        
        // Verify file contents contain expected PHP code
        $userContent = file_get_contents($userFile);
        $this->assertSame(true, strpos($userContent, 'class User') !== false, 'User.php should contain User class');
        
        $repoContent = file_get_contents($repoFile);
        $this->assertSame(true, strpos($repoContent, 'class UserRepository') !== false, 'UserRepository.php should contain UserRepository class');
    }
    
    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = []) {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}