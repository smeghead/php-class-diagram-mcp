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
        $testDir = __DIR__ . '/../fixtures/TestProject';
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