Tasks:
1. Delete these if unused (search the whole project for references first): php_quickstart.php, temp_extract_erd.py, public/text.html.
2. Remove commented-out code blocks in routes/api.php, routes/web.php, controllers, models and commands. Keep comments that explain why something is done.
3. Fix typos in class/file names and update every reference (imports, use statements, config):
   - Responces -> Responses (DoctorResponces -> DoctorResponses)
   - DoctorRegisteration -> DoctorRegistration
   - excutePayment -> executePayment (method name only if it is not a route name)
4. Split app/Enums/user_enum.php into correctly named files (UserRole.php, UserStatus.php) with matching namespaces, and update references.
5. Fix naming case on Linux-sensitive paths: folder app/Http/Controllers/api -> Api, and class/file names starting with lowercase (customRequestController, customRequest, customNotification, otpNotification, offerRequestRequest, cartItemRequest) to PascalCase. Update namespaces and imports. On Windows use `git mv` in two steps (old -> temp -> new) so git registers the case change.
6. Do NOT rename Kernal.php yet and do NOT touch scheduling.
7. After the changes run: composer dump-autoload, php artisan route:list (the output must be identical to before), and php artisan test if tests exist.

Before editing, save `php artisan route:list --json > routes_before.json` and compare it after. Report a short list of the files changed and anything you skipped or were unsure about.

