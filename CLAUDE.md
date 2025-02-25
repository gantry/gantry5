# Gantry5 Development Guide

## Build Commands
- `npm run build-assets` - Install/rebuild all assets
- `gulp` or `gulp all` - Compile all CSS and JS
- `gulp watch` - Watch for CSS/JS changes
- `gulp watch --css` or `gulp watch --js` - Watch specific files
- `gulp --prod` - Compile for production (compressed)
- `composer run build-dev` - Development build
- `composer run build-prod` - Production build

## Code Style
- PHP: PSR-2 with 4 space indentation 
- PHP 8.3 compatibility: Use nullable types, union types
- JS: 4 space indentation, camelCase for variables
- Error handling: Use exceptions with try/catch blocks
- Types: Use proper type declarations in method signatures
- Naming: Classes use PascalCase, methods use camelCase
- Imports: Group by source (core PHP, external libs, project)
- Comments: DocBlocks for classes/methods with descriptions
- Framework pattern: Event-driven architecture with subscribers

## Testing
Run tests with PHPUnit (see individual platform directories)