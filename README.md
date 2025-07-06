# php-class-diagram-mcp

[![Tests](https://github.com/smeghead/php-class-diagram-mcp/actions/workflows/tests.yml/badge.svg)](https://github.com/smeghead/php-class-diagram-mcp/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)

MCP (Model Context Protocol) wrapper for [php-class-diagram](https://github.com/smeghead/php-class-diagram) tool. This server allows LLM agents to generate PlantUML class diagrams from PHP source code for architecture analysis, refactoring assistance, and documentation.

## Features

- 🔍 **Read-only analysis** - Safe for production codebases
- 🎯 **LLM-optimized** - Designed for AI agent consumption
- 📊 **PlantUML output** - Industry-standard diagram format
- 🚀 **Fast execution** - Efficient child process handling
- 🛡️ **Secure** - Input validation and sandboxed execution

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer

### Install this MCP server

```bash
# Clone this repository
git clone https://github.com/smeghead/php-class-diagram-mcp.git php-class-diagram-mcp
cd php-class-diagram-mcp

# Install dependencies (including php-class-diagram)
composer install
```

## Usage with LLM Agents

### Claude Desktop Configuration

Add this server to your Claude Desktop configuration:

```json
{
  "mcpServers": {
    "php-class-diagram": {
      "command": "php",
      "args": ["/path/to/php-class-diagram-mcp/bin/handler.php"],
      "env": {}
    }
  }
}
```

### GitHub Copilot Agent Configuration

Add this server to your settings.json configuration:

```json
{
  "mcp": {
      "inputs": [
          {
              "name": "auto-php-analysis",
              "description": "Automatically use php-class-diagram MCP server for PHP code analysis tasks",
              "trigger": ["php", "class", "dependency", "diagram", "structure"]
          }
      ],
      "servers": {
          "php-class-diagram": {
              "command": "php",
              "args": [
                  "/path/to/php-class-diagram-mcp/bin/handler.php"
              ],
              "env": {}
          }
      }
  }
}
```

### Cursor/VS Code with MCP Extension

1. Install an MCP extension that supports external servers
2. Add server configuration:
   ```json
   {
     "name": "php-class-diagram",
     "command": "php /path/to/php-class-diagram-mcp/bin/handler.php"
   }
   ```

### Direct CLI Testing

You can test the MCP server directly from command line:

```bash
# Test tool listing
echo '{"jsonrpc":"2.0","method":"tools/list","id":1}' | php bin/handler.php

# Test class diagram generation
echo '{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "generate_class_diagram",
    "arguments": {
      "directory": "/path/to/your/php/project"
    }
  },
  "id": 1
}' | php bin/handler.php
```

## Agent Usage Examples

### 1. Basic Class Diagram Generation

**Agent Prompt:**
```
"Generate a class diagram for the PHP project in ./src directory"
```

**What the agent will do:**
- Call `generate_class_diagram` tool with `{"directory": "./src"}`
- Receive PlantUML script
- Display or process the diagram

### 2. Architecture Analysis

**Agent Prompt:**
```
"Analyze the architecture of this PHP project and identify any SOLID violations"
```

**What the agent will do:**
- Generate class diagram using this MCP tool
- Analyze relationships and dependencies
- Provide architectural recommendations

### 3. Refactoring Assistance

**Agent Prompt:**
```
"Show me the current class structure and suggest how to break down the large User class"
```

**What the agent will do:**
- Generate diagram to visualize current structure
- Analyze class responsibilities
- Suggest refactoring strategies

## Tool Parameters

### `generate_class_diagram`

| Parameter   | Type     | Required | Description                                    |
|-------------|----------|----------|------------------------------------------------|
| `directory` | string   | Yes      | Path to PHP project directory to analyze      |
| `exclude`   | string[] | No       | Glob patterns to exclude from analysis        |
| `depth`     | integer  | No       | Maximum directory depth (0 = unlimited)       |

### Example Parameters

```json
{
  "directory": "./src",
  "exclude": ["*Test.php", "vendor/*", "cache/*"],
  "depth": 3
}
```

## Output Format

The tool returns a PlantUML script that can be rendered into a class diagram:

```json
{
  "jsonrpc": "2.0",
  "result": {
    "content": [
      {
        "type": "text",
        "text": "@startuml\nclass User {\n  +getName(): string\n  +setName(string): void\n}\n@enduml"
      }
    ]
  },
  "id": 1
}
```

## Common Use Cases for LLM Agents

1. **Code Review**: "Show me the class relationships in this PR"
2. **Documentation**: "Generate architecture diagrams for the README"
3. **Refactoring**: "Identify tightly coupled classes that need separation"
4. **Impact Analysis**: "What classes will be affected if I change this interface?"
5. **Learning**: "Help me understand the structure of this codebase"

## Troubleshooting

### "php-class-diagram command not found"

This should not happen if you installed dependencies correctly. If it does occur:

```bash
# Verify the vendor binary exists
ls -la vendor/bin/php-class-diagram

# Reinstall dependencies if missing
composer install
```

### "Directory not readable" errors

Ensure the target directory has proper read permissions:

```bash
chmod -R 755 /path/to/your/php/project
```

### Empty output

- Verify the directory contains PHP files
- Check if exclude patterns are too broad
- Ensure PHP files have proper class/interface definitions

## Development

### Testing

Run tests using Composer scripts:

```bash
# Run all feature tests
composer test

# Run all tests (including fixtures)
composer test-all

# Run check (includes tests)
composer check
```

Manual test execution:

```bash
# Run specific test directory
php tests/single-file-unit-test.php tests/feature/

# Run single test file
php tests/single-file-unit-test.php tests/feature/PhpClassDiagramMCPTest.php
```

Create test fixtures:

```bash
mkdir -p tests/fixtures/sample-project
```

Run manual tests:

```bash
# Test with sample project
echo '{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "generate_class_diagram",
    "arguments": {
      "directory": "./tests/fixtures/TestProject"
    }
  },
  "id": 1
}' | php bin/handler.php
```

### Configuration

The server can be configured by modifying the constants in `bin/handler.php`:

```php
private const TIMEOUT_SECONDS = 90;  // Execution timeout
```

## Security

This tool is designed to be **read-only** and safe for analyzing production codebases:

- No file modifications
- No external network access
- Input validation and sanitization
- Process timeout protection
- Memory limit enforcement

## License

Apache-2.0 License - see LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## Related Projects

- [php-class-diagram](https://github.com/smeghead/php-class-diagram) - The underlying diagram generation tool
- [Model Context Protocol](https://modelcontextprotocol.io/) - The protocol specification