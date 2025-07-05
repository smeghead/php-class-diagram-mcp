# JSONL Integration Test Fixture

This directory contains PHP files used for end-to-end JSONL integration testing of the MCP server.

## Purpose
- Test the complete JSONL workflow: initialize → tools/list → tools/call
- Verify that the MCP server can process multiple JSON-RPC messages in sequence
- Generate actual class diagrams as part of regression testing

## Files
- `User.php` - Simple user class with properties and methods
- `UserRepository.php` - Repository class with relationship to User class

## Usage in Tests
Used by `testJsonlRegressionTestWithFixtures()` to verify:
1. JSONL message processing works correctly
2. Class diagram generation produces expected PlantUML output
3. Relationships between classes are detected properly

This serves as a regression test to ensure future changes don't break the core JSONL functionality.
