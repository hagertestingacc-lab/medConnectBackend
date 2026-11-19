# MedConnect Backend rules
- Laravel 11, PHP 8.3, PSR-12, PascalCase class names.
- NEVER change API routes (URL, method, name) or JSON response keys.
- NEVER edit migrations, run migrate, or change DB tables/columns.
- Keep Fawaterk webhook/callback and cron routes untouched.
- Controllers stay thin: validation in Form Requests, logic in Services/Actions, JSON via API Resources.
- Do not change business logic when refactoring.
- After changes: composer dump-autoload and compare php artisan route:list.
- Keep answers short. End with a list of changed files and anything skipped.
