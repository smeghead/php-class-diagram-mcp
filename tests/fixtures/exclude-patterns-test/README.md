# Exclude Patterns Test Fixture

This directory contains PHP files used for testing the `--exclude` parameter functionality of the php-class-diagram tool.

## Purpose
- Test exclusion of specific file patterns 
- Verify that excluded files don't appear in generated class diagrams
- Test various glob patterns for file exclusion

## Files
- `User.php` - Basic user class
- `UserRepository.php` - Repository class  
- `UserService.php` - Service class
- `Events.php` - Event class (in sub-namespace)
- `TestExclude.php` - File designed to be excluded from diagrams

## Usage in Tests
Used by test cases that verify the exclude parameter works correctly when generating class diagrams.
