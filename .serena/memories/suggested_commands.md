Setup: `composer run setup`
Start containers/services: `./vendor/bin/sail up -d`
Run app in development: `composer run dev`
Run full test suite: `composer run test`
Run a focused test: `php artisan test tests/Unit/AutoReplyServiceTest.php`
Format PHP: `./vendor/bin/pint`
Rebuild embeddings/vector index: `php artisan embeddings:generate --fresh`
Useful shell commands on Darwin: `git status`, `git diff`, `ls`, `find`, `rg`, `sed -n 'start,endp' file`